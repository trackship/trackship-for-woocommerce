<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WC_TrackShip_Api_Call {
	
	public function __construct() {
		
	}
	
	/*
	* check if string is json or not
	*/
	public function isJson( $string ) {
		json_decode( $string );
		return ( json_last_error() == JSON_ERROR_NONE );
	}
	
	/*
	* get trackship shipment status and update in order meta
	*/
	public function get_trackship_apicall( $order_id ) {
		
		$logger = wc_get_logger();
		$context = array( 'source' => 'wc_ast_trackship' );
		$array = array();
		$order = wc_get_order( $order_id );
		$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
		$shipment_status = $order->get_meta( 'shipment_status', true );
		
		if ( $tracking_items ) {
			
			foreach ( ( array ) $tracking_items as $key => $val ) {				
				
				$tracking_number = trim( $val['tracking_number'] );
				if ( ! isset( $tracking_number ) ) {
					continue;
				}
				
				if ( isset( $shipment_status[$key]['status'] ) && 'delivered' == $shipment_status[$key]['status'] ) {
					continue;
				}
				
				if ( isset( $val['tracking_provider'] ) && '' != $val['tracking_provider'] ) {
					$tracking_provider = $val['tracking_provider'];
				} else {
					$tracking_provider = $val['custom_tracking_provider'];
				}
				$tracking_provider = apply_filters( 'convert_provider_name_to_slug', $tracking_provider );
				
				$bool = apply_filters( 'exclude_to_send_data_for_provider', true, $tracking_provider );
				if ( !$bool ) {
					continue;
				}

				//do api call to TrackShip
				$response = $this->get_trackship_data( $order, $tracking_number, $tracking_provider );
				
				if ( is_wp_error( $response ) ) {
					$error_message = $response->get_error_message();
					
					$logger = wc_get_logger();
					$context = array( 'source' => 'Trackship_apicall_is_wp_error' );
					$logger->error( "Something went wrong: {$error_message} For Order id :" . $order->get_id(), $context );
					
					//error like 403 500 502 
					$timestamp = time() + 5*60;
					$args = array( $order->get_id() );
					$hook = 'wcast_retry_trackship_apicall';
					wp_schedule_single_event( $timestamp, $hook, $args );
					
					$shipment_status = $order->get_meta( 'shipment_status', true );
					if ( is_string( $shipment_status ) ) {
						$shipment_status = array();
					}
					$shipment_status[$key]['status'] = "Something went wrong";
					$shipment_status[$key]['status_date'] = gmdate('Y-m-d H:i:s');
					$order->update_meta_data( 'shipment_status', $shipment_status );
					$order->save();

				} else {
					
					$code = $response['response']['code'];

					if ( 200 == $code ) {
						//update trackers_balance, status_msg
						if ( !$this->isJson($response['body']) ) {
							return;
						}
						$body = json_decode($response['body'], true);
						
						$shipment_status = $order->get_meta( 'shipment_status', true );
						
						if ( is_string($shipment_status) ) {
							$shipment_status = array();
						}
						
						$shipment_status[$key]['pending_status'] = $body['status_msg'];
						
						$shipment_status[$key]['status_date'] = current_time('Y-m-d H:i:s');
						$shipment_status[$key]['est_delivery_date'] = '';														
						
						$order->update_meta_data( 'shipment_status', $shipment_status );
						
						if ( isset( $body['trackers_balance'] ) ) {
							update_option( 'trackers_balance', $body['trackers_balance'] );
						}
						if ( isset( $body['user_plan'] ) ) {
							update_option( 'user_plan', $body['user_plan'] );
						}
						// The text for the note
						$note = sprintf( __( 'Shipping information (%s - %s) was sent to TrackShip.', 'trackship-for-woocommerce' ), $tracking_provider, $tracking_number );
						// Add the note
						$order->add_order_note( $note );
						
						$ts_shipment_status = $order->get_meta( 'ts_shipment_status', true );
						if ( is_string( $ts_shipment_status ) ) {
							$ts_shipment_status = array();
						}
						$ts_shipment_status[$key]['status'] = $shipment_status[$key]['pending_status'];
						$order->update_meta_data( 'ts_shipment_status', $ts_shipment_status );
						$args = array(
							'shipment_status'	=> $shipment_status[$key]['pending_status'],
							'shipping_provider'	=> $tracking_provider,
							'shipping_date'		=> date_i18n('Y-m-d', $val['date_shipped'] ),
							'shipping_country'	=> $order->get_shipping_country() ? WC()->countries->countries[ $order->get_shipping_country() ] : '',
							'est_delivery_date' => null,
						);
						$order->save();
						trackship_for_woocommerce()->actions->update_shipment_data( $order_id, $val['tracking_number'], $args );
						
					} else {
						//error like 400
						$body = json_decode($response['body'], true);
						$shipment_status = $order->get_meta( 'shipment_status', true );
						if ( is_string($shipment_status) ) {
							$shipment_status = array();
						}
						$shipment_status[$key]['pending_status'] = isset( $body['status_msg'] ) ? $body['status_msg'] : 'Error message : ' . $body['message'];
						$shipment_status[$key]['status_date'] = gmdate('Y-m-d H:i:s');
						$shipment_status[$key]['est_delivery_date'] = '';
						$order->update_meta_data( 'shipment_status', $shipment_status );
						$order->save();
						$args = array(
							'shipment_status'	=> $shipment_status[$key]['pending_status'],
							'shipping_provider'	=> $tracking_provider,
							'shipping_date'		=> date_i18n('Y-m-d', $val['date_shipped'] ),
							'est_delivery_date' => null,
						);
						trackship_for_woocommerce()->actions->update_shipment_data( $order_id, $val['tracking_number'], $args );
						
						$logger = wc_get_logger();
						$context = array( 'source' => 'Trackship_apicall_error' );
						$logger->error( 'Error code : ' . $code . ' For Order id :' . $order->get_id(), $context );
						$logger->error( 'Body : ' . $response['body'], $context );
					}
				}
			}
		}
		return $array;
	}
	
	/*
	* Get trackship shipment data
	*/
	public function get_trackship_data( $order, $tracking_number, $tracking_provider ) {
		$user_key = get_option('wc_ast_api_key');
		$domain = get_home_url();
		$order_id = $order->get_id();
		$custom_order_number = $order->get_order_number();
		
		if ( $order->get_shipping_country() != null ) {
			$shipping_country = $order->get_shipping_country();	
		} else {
			$shipping_country = $order->get_billing_country();	
		}
		
		if ( $order->get_shipping_postcode() != null ) {
			$shipping_postal_code = $order->get_shipping_postcode();	
		} else {
			$shipping_postal_code = $order->get_billing_postcode();
		}
		
		$url = 'https://my.trackship.com/api/create-tracker/ts4wc';
		
		$args['body'] = array(
			'user_key'				=> $user_key, // Deprecated since 19-Aug-2022
			'domain'				=> $domain, // Deprecated since 19-Aug-2022
			'order_id'				=> $order_id,
			'custom_order_id'		=> $custom_order_number,
			'tracking_number'		=> $tracking_number,
			'tracking_provider'		=> $tracking_provider,
			'postal_code'			=> $shipping_postal_code,
			'destination_country'	=> $shipping_country,
		);

		$args['headers'] = array(
			'trackship-api-key'	=> $user_key,
			'store'	=> $domain,
		);	
		$args['timeout'] = 10;
		$response = wp_remote_post( $url, $args );
		return $response;
	}
	
	/*
	* delete tracking number from trackship
	*/
	public function delete_tracking_number_from_trackship( $order_id, $tracking_number, $tracking_provider ) {
		$user_key = get_option('wc_ast_api_key');
		$domain = get_site_url();		
		
		$url = 'https://my.trackship.com/api/tracking/delete';
		
		$args['body'] = array(
			'user_key'			=> $user_key, // Deprecated since 19-Aug-2022
			'domain'			=> $domain, // Deprecated since 19-Aug-2022
			'order_id'			=> $order_id,
			'tracking_number'	=> $tracking_number,
			'tracking_provider'	=> $tracking_provider,
		);

		$args['headers'] = array(
			'trackship-api-key'	=> $user_key,
			'store'	=> $domain,
		);	
		$args['timeout'] = 10;
		$response = wp_remote_post( $url, $args );		
		return $response;
	}
}

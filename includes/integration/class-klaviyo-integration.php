<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_Klaviyo_TS4WC {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		$this->init();	
	}
	
	/**
	 * Get the class instance
	 *
	 * @return WOO_Klaviyo_TS4WC
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/*
	* init from parent mail class
	*/
	public function init() {
		add_action( 'trackship_shipment_status_trigger', array( $this, 'ts_status_change_callback'), 10, 4 );
		add_action( 'klaviyo_hoook', array( $this, 'klaviyo_callback'), 10, 4 );
	}

	public function ts_status_change_callback ( $order_id, $old_status, $new_status, $tracking_number ) {
		$timestamp = time() + 3;
		$hook = 'klaviyo_hoook';
		$args = array(
			'order_id'			=> $order_id,
			'old_status'		=> $old_status,
			'new_status'		=> $new_status,
			'tracking_number'	=> $tracking_number
		);
		as_schedule_single_action( $timestamp, $hook, $args, 'TrackShip klaviyo' );
	}
	
	/**
	 * Schedule action callback for integrately_callback
	 */
	public function klaviyo_callback( $order_id, $old_status, $new_status, $tracking_number ) {

		if ( !get_trackship_settings( 'klaviyo', '') ) {
			return;
		}

		$klaviyo_settings = get_option('klaviyo_settings');
		$api_key = isset($klaviyo_settings['klaviyo_public_api_key']) ? $klaviyo_settings['klaviyo_public_api_key'] : '';

		if ( !$api_key ) {
			return;
		}

		// API execution url
		$url = 'https://a.klaviyo.com/client/events/?company_id=' . $api_key;

		$row = trackship_for_woocommerce()->actions->get_shipment_row( $order_id , $tracking_number );

		$order = wc_get_order( $order_id );
		$phone = $order ? $order->get_billing_phone() : '';

		$body = array(
			'data' => array(
				'type' => 'event',
				'attributes' => array(
					'properties' => array(
						'order_id'						=> $order_id,
						'order_number'					=> $order ? $order->get_order_number() : $order_id,
						'tracking_number'				=> $tracking_number,
						'tracking_provider'				=> $row->shipping_provider,
						'tracking_event_status'			=> $row->shipment_status,
						'tracking_est_delivery_date'	=> $row->est_delivery_date,
						'tracking_link'					=> trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id, $tracking_number ),
						'latest_event' 					=> $row->last_event,
						'origin_country'				=> $row->origin_country,
						'destination_country'			=> $row->destination_country,
						'delivery_number'				=> $row->delivery_number,
						'delivery_provider'				=> $row->delivery_provider,
						'shipping_service'				=> $row->shipping_service,
						'last_event_time'				=> $row->last_event_time,
					),
					'metric' => array(
						'data' => array(
							'type' => 'metric',
							'attributes' => array(
								'name' => 'TrackShip Shipments events',
							)
						)
					),
					'profile' => array(
						'data' => array(
							'type' => 'profile',
							'attributes' => array(
								'email'			=> $order ? $order->get_billing_email() : '',
								'phone_number'	=> $this->get_formated_number($phone, $order),
								'first_name'	=> $order ? $order->get_billing_first_name() : '',
								'last_name'		=> $order ? $order->get_billing_last_name() : '',
								'location'		=> array(
									'address1'		=> $order ? $order->get_billing_address_1() : '',
									'address2'		=> $order ? $order->get_billing_address_2() : '',
									'city'			=> $order ? $order->get_billing_city() : '',
									'country'		=> $order ? $order->get_billing_country() : '',
									'region'		=> $order ? $order->get_billing_state() : '',
									'zip'			=> $order ? $order->get_billing_postcode() : '',
								),
							)
						)
					),
				)
			)
		);

		// Add requirements header parameters in below array
		$args = array(
			'body'		=> wp_json_encode($body),
			'headers'	=> array(
				'accept'		=> 'application/json',
				'Content-Type'	=> 'application/json',
				'revision'		=> '2024-02-15',
			),
		);

		// Example API call on integrately
		$response = wp_remote_post( $url, $args );

		$content = print_r($response, true);
		$logger = wc_get_logger();
		$context = array( 'source' => 'trackship-klaviyo-response' );
		$logger->info( "Response \n" . $content . "\n", $context );

	}

	public function get_formated_number($phone, $order) {
		
		// Check if number do not starts with '+'
		if ( '+' != substr( $phone, 0, 1 ) ) {
			$customer_country = ! empty( $order ) ? $order->get_billing_country() : '';
			$shop_country = substr( get_option( 'woocommerce_default_country' ), 0, 2 );
			$country_code = $customer_country ?? $shop_country;

			$WC_Countries = new WC_Countries();
			$calling_code = $WC_Countries->get_country_calling_code( $country_code );

			// remove leading zero
			$phone = preg_replace( '/^0/', '', $phone );

			$phone = $this->country_special_cases( $phone, $country_code, $calling_code );

			// Check if number has country code
			if ( substr( $phone, 0, strlen( substr($calling_code, 1) ) ) != $calling_code ) {
				$phone = $calling_code . $phone;
			}
		}
		return $phone;
	}

	public function country_special_cases( $phone, $country_code, $calling_code ) {

		switch ( $country_code ) {
			case 'IT':
				if ( strlen( $phone ) <= apply_filters( 'smswoo_italian_numbers_length', 10 ) ) {
					$mobile_prefixes = apply_filters( 'smswoo_italian_prefixes', array( '390', '391', '392', '393', '397' ) );
					if ( in_array( substr( $phone, 0, 3 ), $mobile_prefixes ) ) {
						$phone = $calling_code . $phone;
					}
				}
				break;
			case 'NO':
				if ( strlen( $phone ) <= apply_filters( 'smswoo_norwegian_numbers_length', 8 ) ) {
					$mobile_prefixes = apply_filters( 'smswoo_norwegian_prefixes', array( '47' ) );
					if ( in_array( substr( $phone, 0, 2 ), $mobile_prefixes ) ) {
						$phone = $calling_code . $phone;
					}
				}
				break;
		}
		return $phone;
	}
}

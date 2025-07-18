<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API shipment tracking controller.
 *
 * Handles requests to /tracking-webhook endpoint.
 *
 * @since 1.0.0
 */

class TrackShip_REST_API_Controller extends WC_REST_Controller {

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';
	
	/**
	 * Register the routes for trackings.
	 */
	public function register_routes() {
		
		//disconnect_from_trackship
		register_rest_route( 'wc/v1', '/disconnect_from_trackship', array(
			array(
				'methods'				=> WP_REST_Server::CREATABLE,
				'callback'				=> array( $this, 'disconnect_from_trackship_fun' ),
				'permission_callback'	=> array( $this, 'get_item_permissions_check' ),
				'args'					=> array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'user_key' => array(
						'required' => true,
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		//tracking webhook
		register_rest_route( 'wc/v1', '/tracking-webhook', array(
			array(
				'methods'				=> 'POST',
				'callback'				=> array( $this, 'tracking_webhook' ),
				'permission_callback'	=> array( $this, 'create_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		//check_ts4wc_installed
		register_rest_route( 'wc/v1', '/check_ts4wc_installed', array(
			array(
				'methods'				=> 'POST',
				'callback'				=> array( $this, 'check_ts4wc_installed' ),
				'permission_callback'	=> array( $this, 'get_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
	}

	public function check_ts4wc_installed( $request ) {

		$start_time = microtime(true);
		if ( $request['user_key'] ) {
			update_option('trackship_apikey', $request['user_key']);
		}
		
		if ( $request['trackers_balance'] ) {
			update_option( 'trackers_balance', $request['trackers_balance'] );
		}			
		
		$trackship = new WC_Trackship_Actions();
		$trackship->create_tracking_page();

		$hooks = array(
			'trackship_late_shipments_hook',
			'trackship_exception_shipments_hook',
			'trackship_on_hold_shipments_hook',
			'scheduled_cron_shipment_length'
		);
		
		$ts_cron = array();
		$ts_cron['current_time'] = gmdate('Y-m-d H:i:s', time() + wc_timezone_offset());
		
		foreach ($hooks as $hook) {
			$timestamp = wp_next_scheduled($hook);
			if ($timestamp) {
				$next_run = gmdate('Y-m-d H:i:s', $timestamp + wc_timezone_offset());
				$ts_cron[$hook] = $next_run;
			} else {
				$ts_cron[$hook] = 'Not scheduled';
			}
		}
		
		//check which shipment tracking plugin active
		$plugin = 'tswc';
		$version_info = [];
		$version_info['ts4wc'] = trackship_for_woocommerce()->version;
		$version_info['wc'] = WC_VERSION;
		$version_info['site_url'] = get_site_url();
		$version_info['home_url'] = get_home_url();
		$version_info['trackship_db'] = get_option( 'trackship_db' );
		$version_info['trackship_key'] = get_trackship_key();
		
		if ( function_exists( 'wc_advanced_shipment_tracking' ) ) {
			$plugin.= '-ast-free';
			$version_info['ast-free'] = wc_advanced_shipment_tracking()->version;
		}

		if ( is_plugin_active( 'ast-pro/ast-pro.php' ) || is_plugin_active( 'advanced-shipment-tracking-pro/advanced-shipment-tracking-pro.php' ) ) {
			$plugin.= '-ast-pro';
			$version_info['ast-pro'] = ast_pro()->version;
		}
		
		if ( trackship_for_woocommerce()->is_st_active() ) {
			$plugin.= '-st';
			$version_info['st'] = defined('WC_SHIPMENT_TRACKING_VERSION') ? WC_SHIPMENT_TRACKING_VERSION : null;
		}

		if ( is_plugin_active( 'yith-woocommerce-order-tracking/init.php' ) ) {
			$plugin.= '-yith-free';
			$version_info['yith-free'] = YITH_YWOT_VERSION;
		}

		if ( is_plugin_active( 'yith-woocommerce-order-tracking-premium/init.php' ) ) {
			$plugin.= '-yith-pro';
			$version_info['yith-pro'] = YITH_YWOT_VERSION;
		}

		if ( is_plugin_active( 'woo-orders-tracking/woo-orders-tracking.php' ) ) {
			$plugin.= '-wot-free';
			$version_info['wot-free'] = VI_WOO_ORDERS_TRACKING_VERSION;
		}

		if ( is_plugin_active( 'woocommerce-orders-tracking/woocommerce-orders-tracking.php' ) ) {
			$plugin.= '-wot-pro';
			$version_info['wot-pro'] = VI_WOOCOMMERCE_ORDERS_TRACKING_VERSION;
		}
		$version_info['trackship_settings'] = get_option( 'trackship_settings' );
		$version_info['trackship_email_settings'] = get_option( 'trackship_email_settings' );
		$version_info['old_settings'] = [
			'wcast_pickupreminder_email_settings' => get_option( 'wcast_pickupreminder_email_settings' ),
			'wcast_intransit_email_settings' => get_option( 'wcast_intransit_email_settings' ),
			'wcast_returntosender_email_settings' => get_option( 'wcast_returntosender_email_settings' ),
			'wcast_availableforpickup_email_settings' => get_option( 'wcast_availableforpickup_email_settings' ),
			'wcast_exception_email_settings' => get_option( 'wcast_exception_email_settings' ),
			'wcast_onhold_email_settings' => get_option( 'wcast_onhold_email_settings' ),
			'wcast_failure_email_settings' => get_option( 'wcast_failure_email_settings' ),
			'wcast_delivered_status_email_settings' => get_option( 'wcast_delivered_status_email_settings' ),
			'wcast_outfordelivery_email_settings' => get_option( 'wcast_outfordelivery_email_settings' ),
			'shipment_email_settings' => get_option( 'shipment_email_settings' ),
		];
		
		$database_version	= wc_get_server_database_version();

		global $wpdb;
		$shipment_structure = $wpdb->get_results("DESCRIBE {$wpdb->prefix}trackship_shipment");
		$shipment_meta_structure = $wpdb->get_results("DESCRIBE {$wpdb->prefix}trackship_shipment_meta");
		$shipping_provider_structure = $wpdb->get_results("DESCRIBE {$wpdb->prefix}trackship_shipping_provider");

		$server_info = array(
			'phpversion' => PHP_VERSION,
			'SERVER_SOFTWARE' => isset($_SERVER['SERVER_SOFTWARE']) ? sanitize_text_field($_SERVER['SERVER_SOFTWARE']) : '',
			'mysql_version' => $database_version['number'],
			'shipment_count' => $shipment_structure ? count($shipment_structure) : 0,
			'shipment_meta_count' => $shipment_meta_structure ? count($shipment_meta_structure) : 0,
			'shipping_provider_count' => $shipping_provider_structure ? count($shipping_provider_structure) : 0,
			'trackship_shipment' => $shipment_structure ? $shipment_structure : 'Table does not exist',
			'trackship_shipment_meta' => $shipment_meta_structure ? $shipment_meta_structure : 'Table does not exist',
			'trackship_shipping_provider' => $shipping_provider_structure ? $shipping_provider_structure : 'Table does not exist',
		);

		$REQUEST_TIME_FLOAT = isset($_SERVER['REQUEST_TIME_FLOAT']) ? sanitize_text_field($_SERVER['REQUEST_TIME_FLOAT']) : 0.0;
		$data = array(
			'status'		=> 'installed',
			'plugin'		=> $plugin,
			'ts_cron'		=> $ts_cron,
			'code_exec_time'=> microtime(true) - $REQUEST_TIME_FLOAT,
			'fun_exe_time'	=> microtime(true) - $start_time,
			'version_info'	=> $version_info,
			'server_info'	=> $server_info,
		);
		return rest_ensure_response( $data );
	}
	
	public function tracking_webhook( $request ) {
		
		$start_time = microtime(true);
		$order_id = $request['order_id'];
		$tracking_number = $request['tracking_number'];
		$tracking_event_status = $request['tracking_event_status'];
		$last_event_time = $request['last_event_time'];
		$first_event_time = $request['first_event_time'] ?? null;
		$tracking_est_delivery_date = $request['tracking_est_delivery_date'];
		$tracking_events = $request['events'];
		$tracking_destination_events = $request['destination_events'];
		
		$trackship = WC_Trackship_Actions::get_instance();
		
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		$order = wc_get_order( $order_id );
		if ( !$order ) {
			return rest_ensure_response( ['status' => 'success'] );
		}
		$query = [];
		
		foreach ( ( array ) $tracking_items as $key => $tracking_item ) {
			if ( trim( $tracking_item['tracking_number'] ) != trim($tracking_number) ) {
				continue;
			}
			$row = trackship_for_woocommerce()->actions->get_shipment_row( $order_id , $tracking_number );
			$previous_status = isset( $row->shipment_status ) ? $row->shipment_status : '';	

			$order = wc_get_order( $order_id );
			
			$ts_shipment_status[$key]['status'] = $tracking_event_status;
			
			$last_event = '';
			$last_event = $this->get_last_event( $tracking_events, $tracking_destination_events );

			$args = array(
				'pending_status'		=> null,
				'shipment_status'		=> $tracking_event_status,
				'last_event'			=> $last_event,
				'updated_at'			=> $request['updated_at'],
				'last_event_time'		=> $last_event_time ? $last_event_time : gmdate( 'Y-m-d H:i:s' ),
				'first_event_time'		=> $first_event_time,
				'est_delivery_date'		=> $tracking_est_delivery_date ? gmdate('Y-m-d', strtotime($tracking_est_delivery_date)) : null,
			);
			$args2 = array(
				'origin_country'		=> $request['origin_country'],
				'destination_country'	=> $request['destination_country'],
				'destination_state'		=> $request['destination_state'],
				'destination_city'		=> $request['destination_city'],
				'delivery_number'		=> $request['delivery_number'],
				'delivery_provider'		=> $request['delivery_provider'],
				'shipping_service'		=> $request['shipping_service'],
				'tracking_events'		=> json_encode($tracking_events),
				'destination_events'	=> json_encode($tracking_destination_events),
			);
			$query = trackship_for_woocommerce()->actions->update_shipment_data( $order_id, $tracking_number, $args, $args2 );
			
			$order->update_meta_data( 'ts_shipment_status', $ts_shipment_status );
			$order->save();

			if ( $previous_status != $tracking_event_status && 'delivered' != $order->get_status() ) {
				// Schedule action for send Shipment status notifiations
				if ( in_array( $tracking_event_status, array( 'in_transit', 'available_for_pickup', 'out_for_delivery', 'failure', 'on_hold', 'exception', 'return_to_sender', 'delivered' ) ) ) {
					as_schedule_single_action( time(), 'ts_status_change_trigger', array( $order_id, $previous_status, $tracking_event_status, $tracking_number ), 'TrackShip' );
				}

				// Schedule action for send Pickup reminder notifiations
				$enable = get_trackship_email_settings( 'pickup_reminder', 'enable' );
				if ( 'available_for_pickup' == $tracking_event_status && $enable ) {
					$time = get_trackship_email_settings( 'pickup_reminder', 'days' );
					$time = 24*60*60*intval($time);
					as_schedule_single_action( time() + $time, 'trigger_pickup_reminder_email', array( $order_id, $previous_status, $tracking_event_status, $tracking_number ), 'TrackShip' );
				}

				// hook for send data to paypal in AST PRO
				do_action( 'ast_trigger_ts_status_change', $order_id, $previous_status, $tracking_event_status, $tracking_item, [] );

				do_action( 'trackship_shipment_status_trigger', $order_id, $previous_status, $tracking_event_status, $tracking_number );
			}

		}
		
		$trackship->check_tracking_delivered( $order_id );
		
		$REQUEST_TIME_FLOAT = isset($_SERVER['REQUEST_TIME_FLOAT']) ? sanitize_text_field($_SERVER['REQUEST_TIME_FLOAT']) : 0.0;
		$data = array(
			'status' => 'success',
			'code_exec_time'=> microtime(true) - $REQUEST_TIME_FLOAT,
			'fun_exe_time'	=> microtime(true) - $start_time,
			'data' => $query,
			'version' => trackship_for_woocommerce()->version
		);
		
		return rest_ensure_response( $data );
	}

	public function get_last_event( $events, $destination_events ) {
		$last_event = '';
		$tracking_events = $destination_events ? $destination_events : $events;

		if ( $tracking_events ) {
			$tracking_events = array_reverse($tracking_events);
			$tracking_event = $tracking_events[0];
			// print_r($tracking_event);
			$last_event = $tracking_event['message'];
		}
		return $last_event;
	}

	/*
	* disconnect store from TS
	*/
	public function disconnect_from_trackship_fun( $request ) {
		update_option( 'trackship_apikey', '' );
		delete_option( 'trackers_balance' );
	}
	
	/**
	 * Check if a given request has access create order shipment-tracking.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'trackship-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to read a order shipment-tracking.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', (int) $request['order_id'] ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'trackship-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	public function get_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'trackship-for-woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}
}

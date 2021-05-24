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
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v1';

	/**
	 * Post type.
	 *
	 * @var string
	 */
	protected $post_type = 'shop_order';
	
	/**
	 * Name Space function
	 *
	 * @param $namespace
	 *
	 * @return TrackShip_REST_API_Controller
	 */
	public function set_namespace( $namespace ) {
		$this->namespace = $namespace;
		return $this;
	}

	/**
	 * Register the routes for trackings.
	 */
	public function register_routes() {						
		
		//disconnect_from_trackship
		register_rest_route( $this->namespace, '/disconnect_from_trackship', array(
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( $this, 'disconnect_from_trackship_fun' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
				'args'                => array_merge( $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ), array(
					'user_key' => array(
						'required' => true,
					),
				) ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		//tracking webhook
		register_rest_route( $this->namespace, '/tracking-webhook', array(
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'tracking_webhook' ),
				'permission_callback' => array( $this, 'create_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		//check_wcast_installed
		register_rest_route( $this->namespace, '/check_wcast_installed', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'check_wcast_installed' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		//tswc_status
		register_rest_route( $this->namespace, '/tswc_status', array(			
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'tswc_status' ),
				'permission_callback' => '__return_true',
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
	}
	
	/*
	* TSWC installed?
	*/
	public function tswc_status() {
		$plugin = 'tswc';
		
		if ( trackship_for_woocommerce()->is_ast_active() ) {
			$plugin.= '-ast';
		}
		
		if ( trackship_for_woocommerce()->is_st_active() ) {
			$plugin.= '-st';
		}
		
		$data = array(
			'status' => 'installed',
			'plugin' => $plugin
		);
		return rest_ensure_response( $data );
	}

	/*
	* check_wcast_installed
	*/
	public function check_wcast_installed( $request ) {
		$wc_ast_api_key = get_option('wc_ast_api_key');
		$wc_ast_api_enabled = get_option('wc_ast_api_enabled');		
		if ( empty( $wc_ast_api_key ) ) {
			update_option('wc_ast_api_key', $request['user_key']);
		}
		
		if ( '' == $wc_ast_api_enabled ) {
			update_option('wc_ast_api_enabled', 1);
		}
		
		if ( $request['trackers_balance'] ) {
			update_option( 'trackers_balance', $request['trackers_balance'] );
		}			
		
		$trackship = new WC_Trackship_Actions();
		$trackship->create_tracking_page();
		
		$data = array(
			'status' => 'installed'
		);
		return rest_ensure_response( $data );
	}
	
	public function tracking_webhook( $request ) {
		$content = print_r($request, true);
		$logger = wc_get_logger();
		$context = array( 'source' => 'trackship_log' );
		$logger->error( "New tracking_webhook \n\n" . $content . "\n\n", $context );
		//error_log("New tracking_webhook \n\n".$content."\n\n", 3, ABSPATH . "trackship.log");
		
		//validation
		
		$user_key = $request['user_key'];
		$order_id = $request['order_id'];
		$tracking_number = $request['tracking_number'];
		$tracking_provider = $request['tracking_provider'];
		$tracking_event_status = $request['tracking_event_status'];
		$tracking_event_date = $request['tracking_event_date'];
		$tracking_est_delivery_date = $request['tracking_est_delivery_date'];
		$tracking_events = $request['tracking_events'];
		$tracking_destination_events = $request['tracking_destination_events'];
		$previous_status = '';
		
		$trackship = WC_Trackship_Actions::get_instance();
		
		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
		
		foreach ( ( array ) $tracking_items as $key => $tracking_item ) {
			if ( trim( $tracking_item['tracking_number'] ) != trim($tracking_number) ) {
				continue;
			}
			
			$shipment_status = get_post_meta( $order_id, 'shipment_status', true);
			
			if ( is_string($shipment_status) ) {
				$shipment_status = array();			
			}
			
			if ( isset( $shipment_status[$key]['status'] ) ) {
				$previous_status = $shipment_status[$key]['status'];	
			}			
			
			unset($shipment_status[$key]['pending_status']);
			
			$shipment_status[$key]['status'] = $tracking_event_status;
			$shipment_status[$key]['tracking_events'] = json_decode($tracking_events);
			$shipment_status[$key]['tracking_destination_events'] = json_decode($tracking_destination_events);
			
			$shipment_status[$key]['status_date'] = $tracking_event_date;
			if ( $tracking_est_delivery_date ) {
				$shipment_status[$key]['est_delivery_date'] = gmdate('Y-m-d', strtotime($tracking_est_delivery_date));
			}
						
			update_post_meta( $order_id, 'shipment_status', $shipment_status );
			
			$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $order_id );
			
			$trackship->trigger_tracking_email( $order_id, $previous_status, $tracking_event_status, $tracking_item, $shipment_status[$key] );
		}
		
		$trackship->check_tracking_delivered( $order_id );
		
		$data = array(
			'status' => 'success'
		);
		
		return rest_ensure_response( $data );
	}

	/*
	* disconnect store from TS
	*/
	public function disconnect_from_trackship_fun( $request ) {
		$add_key = update_option( 'wc_ast_api_key', '' );
		$wc_ast_api_enabled = update_option( 'wc_ast_api_enabled', 0 );
		delete_option( 'wc_ast_api_enabled' );
		delete_option( 'trackers_balance' );
	}
	
	/**
	 * Check if a given request has access create order shipment-tracking.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return boolean
	 */
	public function create_item_permissions_check( $request ) {
		
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'create' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_create', __( 'Sorry, you are not allowed to create resources.', 'woocommerce-shipment-tracking' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}

	/**
	 * Check if a given request has access to read a order shipment-tracking.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_item_permissions_check( $request ) {
		if ( ! wc_rest_check_post_permissions( $this->post_type, 'read', (int) $request['order_id'] ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot view this resource.', 'woocommerce-shipment-tracking' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}
}

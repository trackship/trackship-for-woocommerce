<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST API shipment tracking controller.
 *
 * Handles requests to /orders/shipment-tracking endpoint.
 *
 * @since 1.5.0
 */

class WC_Ts_Analytics_REST_API_Controller extends WC_REST_Controller {

	/**
	 * Endpoint namespace.
	 *
	 * @var string
	 */
	protected $namespace = 'wc/v3';

	/**
	 * Route base.
	 *
	 * @var string
	 */
	protected $rest_base = 'ts-analytics';	
	
	/**
	 * Initialize the main plugin function
	*/
    public function __construct() {
		global $wpdb;
		if( is_multisite() ){			
			if ( ! function_exists( 'is_plugin_active_for_network' ) ) {
				require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
			}
			if ( is_plugin_active_for_network( 'trackship-for-woocommerce/trackship-for-woocommerce.php' ) ) {
				$main_blog_prefix = $wpdb->get_blog_prefix(BLOG_ID_CURRENT_SITE);			
				$this->shipment_table = $main_blog_prefix . 'trackship_shipment';	
			} else{
				$this->shipment_table = $wpdb->prefix . 'trackship_shipment';
			}
		} else{
			$this->shipment_table = $wpdb->prefix . 'trackship_shipment';	
		}			
	}
	
	/**
	 * Set namespace
	 *
	 * @return WC_Advanced_Shipment_Tracking_REST_API_Controller
	 */
	public function set_namespace( $namespace ) {
		$this->namespace = $namespace;
		return $this;
	}
	
	/**
	 * Register the routes for trackings.
	 */
	public function register_routes() {
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/get_shipments_providers', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_providers' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),						
		) );
		
		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipments_count', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_count' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),						
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipments_by_status', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_by_status' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),						
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipments_by_providers', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_by_providers' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),						
		) );

		register_rest_route( $this->namespace, '/' . $this->rest_base . '/shipments_by_date', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_by_date' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),						
		) );		
	}		
		

	/**
	 * Check whether a given request has permission to read order shipment-trackings.
	 *
	 * @param  WP_REST_Request $request Full details about the request.
	 * @return WP_Error|boolean
	 */
	public function get_items_permissions_check( $request ) {		
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}
	
	/**
	 * Maps query arguments from the REST request.
	 *
	 * @param array $request Request array.
	 * @return array
	 */
	protected function prepare_reports_query( $request ) {
		$args                       = array();
		$args['before']             = $request['before'];
		$args['after']              = $request['after'];
		$args['page']               = $request['page'];
		$args['per_page']           = $request['per_page'];
		$args['orderby']            = $request['orderby'];
		$args['order']              = $request['order'];
		$args['shipment_status']   	= $request['shipment_status'];
		$args['shipment_provider']  = $request['shipment_provider'];
		$args['shipment_type']		= $request['shipment_type'];
		
		return $args;
	}
	
	
	/**
	 * Get shipments count.	 
	 */
	public function get_shipments_providers( $request ) {
				
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		
		$all_providers = $wpdb->get_results("SELECT shipping_provider FROM {$woo_trackship_shipment} WHERE shipping_provider NOT LIKE ( '%NULL%') GROUP BY shipping_provider;");
		
		$response = array();
		$response[0]['label'] = 'All';
		$response[0]['value'] = 'all';
		$count = 1;
		foreach ( $all_providers as $provider ) {
			$results = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woo_shippment_provider WHERE ts_slug = %s', $provider->shipping_provider ) );		
			$response[$count]['label'] = isset( $results->provider_name ) ? $results->provider_name : $provider->shipping_provider;
			$response[$count]['value'] = $provider->shipping_provider;
			$count++;		
		}	
		
		return rest_ensure_response( $response );		
	}
	
	/**
	 * Get shipments count.	 
	 */
	public function get_shipments_count( $request ) {
		
		$query_args   = $this->prepare_reports_query( $request );
		$data = array();
		$after_date = date( 'Y-m-d', strtotime( $query_args['after'] ) );
		$before_date = date( 'Y-m-d', strtotime( $query_args['before'] ) );
		$shipment_provider = ( isset( $query_args['shipment_provider'] ) &&  '' != $query_args['shipment_provider'] ? $query_args['shipment_provider'] : '' );
		
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		
		$shipmen_provider_query = '';
		if ( '' != $shipment_provider ) {
			$shipmen_provider_query = "AND shipping_provider LIKE ( '%" . $shipment_provider . "%')";
		}				
		
		$total_count = $wpdb->get_var("SELECT COUNT(*) FROM {$woo_trackship_shipment} WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_provider_query}");
		$active_count = $wpdb->get_var("SELECT COUNT(*) FROM {$woo_trackship_shipment} WHERE shipment_status NOT LIKE ( '%delivered%') AND shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_provider_query}");
		$delivered_count = $wpdb->get_var("SELECT COUNT(*) FROM {$woo_trackship_shipment} WHERE shipment_status LIKE ( '%delivered%') AND shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_provider_query}");
		$avg_shipping_length = $wpdb->get_var("SELECT AVG(shipping_length) FROM {$woo_trackship_shipment} WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_provider_query}");				
		
		$response['total_count'] = (int) $total_count;
		$response['active_count'] = (int) $active_count;
		$response['delivered_count'] = (int) $delivered_count;
		$response['avg_shipping_length'] = round( $avg_shipping_length );
		
		return rest_ensure_response( $response );		
	}

	/**
	 * Get shipments by status.	 
	 */
	public function get_shipments_by_status( $request ) {
		
		$query_args   = $this->prepare_reports_query( $request );
		$data = array();
		$after_date = date( 'Y-m-d', strtotime( $query_args['after'] ) );
		$before_date = date( 'Y-m-d', strtotime( $query_args['before'] ) );
		$shipment_status = ( isset( $query_args['shipment_status'] ) && '' != $query_args['shipment_status'] ? $query_args['shipment_status'] : '' );
		$shipment_provider = ( isset( $query_args['shipment_provider'] ) &&  '' != $query_args['shipment_provider'] ? $query_args['shipment_provider'] : '' );		
		
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		
		$shipmen_status_query = '';
		if ( '' != $shipment_status && 'all' != $shipment_status ) {
			$shipmen_status_query = "AND shipment_status LIKE ( '%" . $shipment_status . "%')";
		}
		
		$shipmen_provider_query = '';
		if ( '' != $shipment_provider ) {
			$shipmen_provider_query = "AND shipping_provider LIKE ( '%" . $shipment_provider . "%')";
		}
		
		$status_data = $wpdb->get_results("SELECT ts.shipment_status , COUNT(1) AS total , ROUND( COUNT(1) / t.cnt * 100 ) AS percentage FROM {$woo_trackship_shipment} ts CROSS JOIN (SELECT COUNT(1) AS cnt FROM {$woo_trackship_shipment} WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipmen_provider_query}) t WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipmen_provider_query} GROUP BY ts.shipment_status");
		
		$response = array();
		$count = 0;
		foreach ( $status_data as $data ) {
			$response[$count]['shipment_status'] = apply_filters( 'trackship_status_filter', $data->shipment_status );
			$response[$count]['total'] = $data->total;
			$response[$count]['percentage'] = $data->percentage;
			$response[$count]['href'] = admin_url( 'admin.php?page=trackship-shipments&status=' . $data->shipment_status );
			$count++;
		}
		
		return rest_ensure_response( $response );		
	}

	/**
	 * Get shipments by providers.	 
	 */
	public function get_shipments_by_providers( $request ) {
		
		$query_args   = $this->prepare_reports_query( $request );
		$data = array();
		$after_date = date( 'Y-m-d', strtotime( $query_args['after'] ) );
		$before_date = date( 'Y-m-d', strtotime( $query_args['before'] ) );
		$shipment_status = ( isset( $query_args['shipment_status'] ) && '' != $query_args['shipment_status'] ? $query_args['shipment_status'] : '' );
		$shipment_provider = ( isset( $query_args['shipment_provider'] ) &&  '' != $query_args['shipment_provider'] ? $query_args['shipment_provider'] : '' );		
		
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		
		$shipmen_status_query = '';
		if ( '' != $shipment_status && 'all' != $shipment_status ) {
			$shipmen_status_query = "AND shipment_status LIKE ( '%" . $shipment_status . "%')";
		}
		
		$shipmen_provider_query = '';
		if ( '' != $shipment_provider ) {
			$shipmen_provider_query = "AND shipping_provider LIKE ( '%" . $shipment_provider . "%')";
		}
		
		$providers_data = $wpdb->get_results("SELECT ts.shipping_provider , COUNT(1) AS total , ROUND( COUNT(1) / t.cnt * 100 ) AS percentage, AVG(shipping_length) as average FROM {$woo_trackship_shipment} ts CROSS JOIN (SELECT COUNT(1) AS cnt FROM {$woo_trackship_shipment} WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipmen_provider_query}) t WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipmen_provider_query} GROUP BY ts.shipping_provider");

		$response = array();
		$count = 0;
		foreach ( $providers_data as $data ) {
			$results = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woo_shippment_provider WHERE ts_slug = %s', $data->shipping_provider ) );
			$provider_name = isset( $results->provider_name ) ? $results->provider_name : $data->shipping_provider;

			$response[$count]['shipping_provider'] = $provider_name;
			$response[$count]['total'] = $data->total;
			$response[$count]['percentage'] = $data->percentage;
			$response[$count]['average'] = round( $data->average ) . ' days';
			$response[$count]['href'] = admin_url( 'admin.php?page=trackship-shipments&provider=' . $data->shipping_provider );
			$count++;
		}		
		return rest_ensure_response( $response );		
	}
	
	/**
	 * Get shipments by date.	 
	 */
	public function get_shipments_by_date( $request ) {
		$query_args   = $this->prepare_reports_query( $request );
		$data = array();
		$after_date = date( 'Y-m-d', strtotime( $query_args['after'] ) );
		$before_date = date( 'Y-m-d', strtotime( $query_args['before'] ) );	
		$shipment_status = ( isset( $query_args['shipment_status'] ) && '' != $query_args['shipment_status'] ? $query_args['shipment_status'] : '' );
		$shipment_provider = ( isset( $query_args['shipment_provider'] ) &&  '' != $query_args['shipment_provider'] ? $query_args['shipment_provider'] : '' );	
		$shipment_type = isset( $query_args['shipment_type'] ) && '' != $query_args['shipment_type'] ? $query_args['shipment_type'] : '';
		
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		
		$shipmen_status_query = '';
		if ( '' != $shipment_status && 'all' != $shipment_status ) {
			$shipmen_status_query = "AND shipment_status = ( '{$shipment_status}')";
		}
		
		$shipmen_type_query = '';
		if ( $shipment_type == 'delivered' ) {
			$shipmen_type_query = "AND shipment_status = ( 'delivered')";
		} elseif ( $shipment_type == 'active' ) {
			$shipmen_type_query = "AND shipment_status != ( 'delivered')";
		}

		$shipmen_provider_query = '';
		if ( '' != $shipment_provider ) {
			$shipmen_provider_query = "AND shipping_provider LIKE ( '%" . $shipment_provider . "%')";
		}
		
		$date_data = $wpdb->get_results("SELECT shipping_date as date, COUNT(*) as value FROM {$woo_trackship_shipment} WHERE shipping_date NOT LIKE ( '%NULL%') AND shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipmen_provider_query} {$shipmen_type_query} GROUP By shipping_date");
		
		$interval = new DateInterval('P1D');
		$realEnd = new DateTime($before_date);
		$realEnd->add($interval);
		$period = new DatePeriod(new DateTime($after_date), $interval, $realEnd);

		$response = array();
		$i = 0;
		foreach ( $period as $date ) {                 
			$response[$i]['date'] = $date->format('Y-m-d');
			$response[$i]['Shipments']['label'] = 'Shipments';
			$response[$i]['Shipments']['value'] = 0;
			foreach ( $date_data as $key => $data ) {
				if ( $date->format('Y-m-d') == $data->date ) {
					$response[$i]['Shipments']['value'] = (int) $data->value;
					unset( $date_data[ $key ] );
					break;
				}
			}
			$i++;
		}
		
		return rest_ensure_response( $response );		
	}

	/**
	 * Get the query params for collections.
	 *
	 * @return array
	 */
	public function get_collection_params() {
		return array(
			'context' => $this->get_context_param( array( 'default' => 'view' ) ),
		);
	}
}

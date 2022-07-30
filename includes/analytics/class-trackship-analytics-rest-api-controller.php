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
		
		// get stats data
		register_rest_route( 'wc-analytics/reports/data' , 'trackship/stats', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_trackship_stats' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
		) );

		// Get all provider list
		register_rest_route( 'wc-analytics/data', 'get_shipments_providers', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_providers' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
		) );

		// get all shipments by status
		register_rest_route( 'wc-analytics/reports/data' , 'shipments_by_status', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_by_status' ),
				'permission_callback' => array( $this, 'get_items_permissions_check' ),
				'args'                => $this->get_collection_params(),
			),
		) );
		
		// get all shipments by provider
		register_rest_route( 'wc-analytics/reports/data' , 'shipments_by_provider', array(
			array(
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => array( $this, 'get_shipments_by_providers' ),
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
		 return true;
		 	
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
		$args['providers']			= $request['providers'];
		
		return $args;
	}
	
	public function get_trackship_stats ( $request ) {

		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;

		$query_args = $this->prepare_reports_query( $request );

		$after_date = date( 'Y-m-d', strtotime( $query_args['after'] ) );
		$before_date = date( 'Y-m-d', strtotime( $query_args['before'] ) );
		$providers_id = ( isset( $query_args['providers'] ) &&  '' != $query_args['providers'] ? $query_args['providers'] : null );
		$shipment_status = ( isset( $query_args['shipment_status'] ) && '' != $query_args['shipment_status'] ? $query_args['shipment_status'] : '' );

		$where = $providers_id ? "WHERE provider.id IN ( {$providers_id} )" : '';
		$providers_data = $wpdb->get_results(
			"SELECT provider.id, provider.provider_name, shipments.shipping_provider as ts_slug
			FROM {$woo_trackship_shipment} as shipments LEFT JOIN {$wpdb->prefix}trackship_shipping_provider as provider
			ON provider.ts_slug = shipments.shipping_provider {$where} GROUP BY ts_slug", ARRAY_A
		);

		// Shipment provider query
		$shipment_provider_query = '';
		if ( $providers_id ) {
			$providers_slug = [];
			foreach ( $providers_data as $key => $val ) {
				$providers_slug[] = $val['ts_slug'];
			}
			$providers_slug = $providers_slug ? "'" . implode( "', '", $providers_slug ) . "'" : '';
			$shipment_provider_query = "AND shipping_provider IN ( {$providers_slug} )";
		}
		
		// Shipment status query
		$shipment_status_query = '';
		if ( '' != $shipment_status && 'all' != $shipment_status ) {
			$shipment_status_query = "AND shipment_status LIKE ( '$shipment_status' )";
		}
		
		//interval
		$interval = new DateInterval('P1D');
		$realEnd = new DateTime($before_date);
		$realEnd->add($interval);
		$period = new DatePeriod(new DateTime($after_date), $interval, $realEnd);
		
		$i = 0;
		$response = array();
		//date vise data loop
		foreach ( $period as $date ) {

			$current_date_total_data = $current_date_active_data = $current_date_delievered_data = 0;
			$current_date_total_data = $this->get_totals_count( $date->format('Y-m-d'), '', $shipment_provider_query, $shipment_status_query ); // Date vise totoal data
			$current_date_active_data = $this->get_active_count( $date->format('Y-m-d'), '', $shipment_provider_query, $shipment_status_query ); // Date vise active data
			$current_date_delievered_data = $this->get_delivered_count( $date->format('Y-m-d'), '', $shipment_provider_query, $shipment_status_query );// Date vise delivered data

			$response['intervals'][$i]['interval'] = $date->format('Y-m-d');
			$response['intervals'][$i]['date_start'] = $date->format('Y-m-d 00:00:00');
			$response['intervals'][$i]['date_start_gmt'] = $date->format('Y-m-d 00:00:00');
			$response['intervals'][$i]['date_end'] = $date->format('Y-m-d 23:59:59');
			$response['intervals'][$i]['date_end_gmt'] = $date->format('Y-m-d 23:59:59');
			$response['intervals'][$i]['subtotals']['total_shipments'] = $current_date_total_data;
			$response['intervals'][$i]['subtotals']['active_shipments'] = $current_date_active_data;				
			$response['intervals'][$i]['subtotals']['delivered_shipments'] = $current_date_delievered_data;
			$response['intervals'][$i]['subtotals']['segments'] = $this->segements_data( $date->format('Y-m-d'), '', $providers_data, $providers_id, $shipment_status, $shipment_provider_query, $shipment_status_query );
			$i++;
		}

		$total_data = $active_data = $delievered_data = 0;

		$total_data = $this->get_totals_count( $before_date, $after_date, $shipment_provider_query, $shipment_status_query );
		$active_data = $this->get_active_count( $before_date, $after_date, $shipment_provider_query, $shipment_status_query );
		$delievered_data = $this->get_delivered_count( $before_date, $after_date, $shipment_provider_query, $shipment_status_query );
		$avg_shipping_length = $wpdb->get_var("SELECT AVG(shipping_length) FROM {$woo_trackship_shipment} WHERE shipping_date NOT LIKE ( '%NULL%') AND shipping_date BETWEEN '{$after_date}' AND '{$before_date}' AND shipment_status LIKE ( '%delivered%') {$shipment_provider_query} {$shipment_status_query}");	
		
		$response['totals']['total_shipments'] = $total_data ;
		$response['totals']['active_shipments'] = $active_data ;
		$response['totals']['delivered_shipments'] = $delievered_data;
		$response['totals']['avg_shipment_length'] = (int) $avg_shipping_length;
		$response['totals']['segments'] = $this->segements_data( $before_date, $after_date, $providers_data, $providers_id, $shipment_status, $shipment_provider_query, $shipment_status_query );

		return rest_ensure_response( $response );
	}

	/**
	 * Get Segments array.	 
	 */
	public function segements_data( $before_date, $after_date, $providers_data, $providers_id, $shipment_status, $shipment_provider_query, $shipment_status_query ) {

		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		$segements = [];

		$i = 0;
		foreach ( $providers_data as $key => $val ) {
			if ( $val['id'] ) {
				$total_data = $active_data = $delievered_data = 0;
				$extra_query = " AND shipping_provider LIKE ( '{$val['ts_slug']}' )";
				$total_data = $this->get_totals_count( $before_date, $after_date, $shipment_provider_query, $shipment_status_query, $extra_query );
				$active_data = $this->get_active_count( $before_date, $after_date, $shipment_provider_query, $shipment_status_query, $extra_query );
				$delievered_data = $this->get_delivered_count( $before_date, $after_date, $shipment_provider_query, $shipment_status_query, $extra_query );
	
				$segements[$i]['segment_label'] = $val['provider_name'];
				$segements[$i]['segment_id'] = $val['id'];
				$segements[$i]['subtotals']['total_shipments'] = $total_data ;
				$segements[$i]['subtotals']['active_shipments'] = $active_data ;
				$segements[$i]['subtotals']['delivered_shipments'] = $delievered_data ;
				$segements[$i]['subtotals']['avg_shipment_length'] = 0;
				$i++;
			}
		}

		return $segements;
	}

	/**
	 * Get Totals count.	 
	*/
	public function get_totals_count ( $before_date, $after_date, $shipment_provider_query, $shipment_status_query, $extra_query = '' ) {

		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		$date_query = $after_date ? "shipping_date NOT LIKE ( '%NULL%') AND shipping_date BETWEEN '{$after_date}' AND '{$before_date}'" : "shipping_date NOT LIKE ( '%NULL%') AND shipping_date LIKE '{$before_date}'";
		$total_data = $wpdb->get_var("SELECT COUNT(*) as value FROM {$woo_trackship_shipment} WHERE {$date_query} {$shipment_provider_query} {$shipment_status_query} {$extra_query}");

		return (int) $total_data;
	}

	/**
	 * Get delivered count.	 
	*/
	public function get_delivered_count ( $before_date, $after_date, $shipment_provider_query, $shipment_status_query, $extra_query = '' ) {

		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		$date_query = $after_date ? "shipping_date NOT LIKE ( '%NULL%') AND shipping_date BETWEEN '{$after_date}' AND '{$before_date}'" : "shipping_date NOT LIKE ( '%NULL%') AND shipping_date LIKE '{$before_date}'";
		$delievered_data = $wpdb->get_var("SELECT COUNT(*) as value FROM {$woo_trackship_shipment} WHERE {$date_query} AND shipment_status LIKE ( '%delivered%') {$shipment_provider_query} {$shipment_status_query} {$extra_query}");
		
		return (int) $delievered_data;
	}

	/**
	 * Get active count.	 
	*/
	public function get_active_count ( $before_date, $after_date, $shipment_provider_query, $shipment_status_query, $extra_query = '' ) {

		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		$date_query = $after_date ? "shipping_date NOT LIKE ( '%NULL%') AND shipping_date BETWEEN '{$after_date}' AND '{$before_date}'" : "shipping_date NOT LIKE ( '%NULL%') AND shipping_date LIKE '{$before_date}'";
		$active_data = $wpdb->get_var("SELECT COUNT(*) as value FROM {$woo_trackship_shipment} WHERE {$date_query} AND shipment_status NOT LIKE ( '%delivered%') {$shipment_provider_query} {$shipment_status_query} {$extra_query}");
		
		return (int) $active_data;
	}

	/**
	 * Get shipments count.	 
	 */
	public function get_shipments_providers( $request ) {

		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;

		$id = $request->get_param('include');
		$where = $id ? "WHERE id IN ( {$id} )" : '';
		$all_providers = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}trackship_shipping_provider {$where}" );

		return rest_ensure_response( $all_providers );
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
		$providers_id = ( isset( $query_args['providers'] ) &&  '' != $query_args['providers'] ? $query_args['providers'] : null );
		$shipment_provider = ( isset( $query_args['shipment_provider'] ) &&  '' != $query_args['shipment_provider'] ? $query_args['shipment_provider'] : '' );
		
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		
		// Shipment status query
		$shipmen_status_query = '';
		if ( '' != $shipment_status && 'all' != $shipment_status ) {
			$shipmen_status_query = "AND shipment_status LIKE ( '$shipment_status')";
		}
		
		$where = $providers_id ? "WHERE provider.id IN ( {$providers_id} )" : '';
		$selected_provider = $wpdb->get_results(
			"SELECT provider.id, provider.provider_name, shipments.shipping_provider as ts_slug
			FROM {$woo_trackship_shipment} as shipments LEFT JOIN {$wpdb->prefix}trackship_shipping_provider as provider
			ON provider.ts_slug = shipments.shipping_provider {$where} GROUP BY ts_slug", ARRAY_A
		);

		// Shipment provider query
		$shipment_provider_query = '';
		if ( $providers_id ) {
			$providers_slug = [];
			foreach ( $selected_provider as $key => $val ) {
				$providers_slug[] = $val['ts_slug'];
			}
			$providers_slug = $providers_slug ? "'" . implode( "', '", $providers_slug ) . "'" : '';
			$shipment_provider_query = "AND shipping_provider IN ( {$providers_slug} )";
		}

		$status_data = $wpdb->get_results("SELECT ts.shipment_status , COUNT(1) AS total , ROUND( COUNT(1) / t.cnt * 100 ) AS percentage FROM {$woo_trackship_shipment} ts CROSS JOIN (SELECT COUNT(1) AS cnt FROM {$woo_trackship_shipment} WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipment_provider_query}) t WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipment_provider_query} GROUP BY ts.shipment_status");

		$response = array();

		$j = 0;
		foreach ( $status_data as $data ) {
			$response[$j]['shipment_status'] = apply_filters( 'trackship_status_filter', $data->shipment_status );
			$response[$j]['total'] = $data->total;
			$response[$j]['percentage'] = $data->percentage;
			$response[$j]['href'] = admin_url( 'admin.php?page=trackship-shipments&status=' . $data->shipment_status );
			$j++;
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
		$providers_id = ( isset( $query_args['providers'] ) &&  '' != $query_args['providers'] ? $query_args['providers'] : null );
		$shipment_provider = ( isset( $query_args['shipment_provider'] ) &&  '' != $query_args['shipment_provider'] ? $query_args['shipment_provider'] : '' );
		
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		
		// Shipment status query
		$shipmen_status_query = '';
		if ( '' != $shipment_status && 'all' != $shipment_status ) {
			$shipmen_status_query = "AND shipment_status LIKE ( '$shipment_status')";
		}
		
		$where = $providers_id ? "WHERE provider.id IN ( {$providers_id} )" : '';
		$selected_provider = $wpdb->get_results(
			"SELECT provider.id, provider.provider_name, shipments.shipping_provider as ts_slug
			FROM {$woo_trackship_shipment} as shipments LEFT JOIN {$wpdb->prefix}trackship_shipping_provider as provider
			ON provider.ts_slug = shipments.shipping_provider {$where} GROUP BY ts_slug", ARRAY_A
		);

		// Shipment provider query
		$shipment_provider_query = '';
		if ( $providers_id ) {
			$providers_slug = [];
			foreach ( $selected_provider as $key => $val ) {
				$providers_slug[] = $val['ts_slug'];
			}
			$providers_slug = $providers_slug ? "'" . implode( "', '", $providers_slug ) . "'" : '';
			$shipment_provider_query = "AND shipping_provider IN ( {$providers_slug} )";
		}

		$providers_data = $wpdb->get_results("SELECT ts.shipping_provider , COUNT(1) AS total , ROUND( COUNT(1) / t.cnt * 100 ) AS percentage, AVG(shipping_length) as average FROM {$woo_trackship_shipment} ts CROSS JOIN (SELECT COUNT(1) AS cnt FROM {$woo_trackship_shipment} WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipment_provider_query}) t WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipment_provider_query} GROUP BY ts.shipping_provider");
		


		$status_data = $wpdb->get_results("SELECT ts.shipment_status , COUNT(1) AS total , ROUND( COUNT(1) / t.cnt * 100 ) AS percentage FROM {$woo_trackship_shipment} ts CROSS JOIN (SELECT COUNT(1) AS cnt FROM {$woo_trackship_shipment} WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipment_provider_query}) t WHERE shipping_date BETWEEN '{$after_date}' AND '{$before_date}' {$shipmen_status_query} {$shipment_provider_query} GROUP BY ts.shipment_status");

		$response = array();

		$j = 0;
		foreach ( $status_data as $data ) {
			$response['shipment_status'][$j]['shipment_status'] = apply_filters( 'trackship_status_filter', $data->shipment_status );
			$response['shipment_status'][$j]['total'] = $data->total;
			$response['shipment_status'][$j]['percentage'] = $data->percentage;
			$response['shipment_status'][$j]['href'] = admin_url( 'admin.php?page=trackship-shipments&status=' . $data->shipment_status );
			$j++;
		}

		$i = 0;
		foreach ( $providers_data as $provider ) {
			$results = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woo_shippment_provider WHERE ts_slug = %s', $provider->shipping_provider ) );
			$provider_name = isset( $results->provider_name ) ? $results->provider_name : $provider->shipping_provider;

			$response['shipping_provider'][$i]['shipping_provider'] = $provider_name;
			$response['shipping_provider'][$i]['total'] = $provider->total;
			$response['shipping_provider'][$i]['percentage'] = $provider->percentage;
			$response['shipping_provider'][$i]['average'] = $provider->average ? round( $provider->average ) . ' days' : '';
			$response['shipping_provider'][$i]['href'] = admin_url( 'admin.php?page=trackship-shipments&provider=' . $provider->shipping_provider );
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

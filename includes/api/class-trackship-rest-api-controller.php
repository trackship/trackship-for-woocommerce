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
		
		//check_ts4wc_installed
		register_rest_route( $this->namespace, '/check_ts4wc_installed', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'check_ts4wc_installed' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );
		
		//update-carrier
		register_rest_route( $this->namespace, '/ts-update-carrier', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ts_update_carrier' ),
				'permission_callback' => array( $this, 'get_item_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		//Dashboard query
		/*register_rest_route( $this->namespace, '/trackship/dashboard', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ts_dashboard' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		//Shipment query
		register_rest_route( $this->namespace, '/trackship/shipment', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ts_shipment' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),  // send X-WP-nonce in Header
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		//Shipping provider query
		register_rest_route( $this->namespace, '/trackship/shipping/provider', array(			
			array(
				'methods'             => 'GET',
				'callback'            => array( $this, 'ts_shipping_provider' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),  // send X-WP-nonce in Header
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		//Logs query
		register_rest_route( $this->namespace, '/trackship/logs', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ts_logs' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),  // send X-WP-nonce in Header
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		//Log popup query
		register_rest_route( $this->namespace, '/trackship/log/preview', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ts_log_popup' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),  // send X-WP-nonce in Header
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		//Settings data query
		register_rest_route( $this->namespace, '/trackship/settings/data', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ts_settings_data' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),  // send X-WP-nonce in Header
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );

		//Notifications data query
		register_rest_route( $this->namespace, '/trackship/notifications/data', array(			
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'ts_notifications_data' ),
				'permission_callback' => array( $this, 'get_permissions_check' ),  // send X-WP-nonce in Header
			),
			'schema' => array( $this, 'get_public_item_schema' ),
		) );*/
	}
	
	public function ts_dashboard( $request ) {
		global $wpdb;

		$start_from = null != $request->get_param( 'start_from' ) ? $request->get_param( 'start_from' ) : '' ;

		$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
		$end_date = gmdate( 'Y-m-d' );
		$plan_array = array(
			'Free Trial'	=> 50,
			'Mini'			=> 100,
			'Small'			=> 300,
			'MEDIUM'		=> 500,
			'Large'			=> 1000,
			'X-LARGE'		=> 2000,
			'XX-LARGE'		=> 3000,
			'XXX-LARGE'		=> 5000,
			'HUGE'			=> 10000,
			'Giant 30K'		=> 30000,
			'Giant 50K'		=> 50000,
			'Giant 60k'		=> 60000,
			'Giant 100k'	=> 100000,
		);

		$late_shipments_days = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
		$days = $late_shipments_days - 1 ;
		$late_shipment = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipping_length > %d", $days ) );
		$all_tracking_issues = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row	
			WHERE 
				shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
		", '%delivered%', '%pre_transit%', '%in_transit%', '%out_for_delivery%', '%return_to_sender%', '%available_for_pickup%', '%exception%' ) );
		$return_to_sender_shipment = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%return_to_sender%')" );

		$data = array(
			'late_shipment'				=> $late_shipment,
			'all_tracking_issues'		=> $all_tracking_issues,
			'return_to_sender_shipment' => $return_to_sender_shipment,
		);

		$interval = array(
			'month_to_date' => gmdate('Y-m-01 00:00:00' ),
			'last_30'		=> gmdate('Y-m-d 00:00:00', strtotime( 'today - 29 days' ) ),
			'last_60'		=> gmdate('Y-m-d 00:00:00', strtotime( 'today - 59 days' ) ),
		);
		
		$start_date = $interval[$start_from];

		$data[$start_from]['total_shipment'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipping_date BETWEEN %s AND %s", $start_date, $end_date ) );
		$data[$start_from]['active_shipment'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status NOT LIKE ( %s ) AND shipping_date BETWEEN %s AND %s", '%delivered%', $start_date, $end_date ) );
		$data[$start_from]['delivered_shipment'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( %s ) AND shipping_date BETWEEN %s AND %s", '%delivered%', $start_date, $end_date ) );
		$data[$start_from]['tracking_issues'] = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row	
			WHERE 
				shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipment_status NOT LIKE ( %s )
				AND shipping_date BETWEEN %s AND %s
		", '%delivered%', '%pre_transit%', '%in_transit%','%out_for_delivery%', '%return_to_sender%', '%available_for_pickup%', '%exception%', $start_date, $end_date ) );
		
		$url = 'https://my.trackship.info/wp-json/tracking/get_user_plan';
		$args[ 'body' ] = array(
			'user_key' => trackship_for_woocommerce()->actions->get_trackship_key(),
		);
		$response = wp_remote_post( $url, $args );
		$data['plan_data'] = is_wp_error( $response ) ? array() : json_decode( $response[ 'body' ] );
		$current_plan = is_wp_error( $response ) ? array() : json_decode( $response[ 'body' ] )->subscription_plan;
		$data['plan_data']->subscription_plan_balance = $plan_array[$current_plan];
		wp_send_json( array('success' => true, 'data' => $data) );
	}

	public function ts_shipment( $request ) {
		global $wpdb;
		$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
		$start = isset( $request['start'] ) ? sanitize_text_field($request['start']) : 0;
		$length = isset( $request['length'] ) ? sanitize_text_field($request['length']) : 25;
		$limit = 'limit ' . $start . ', ' . $length;

		// $logger = wc_get_logger();
		// $context = array( 'source' => 'AAA_result' );
		// $logger->info( "start:- $start " . gettype($start) . " ...... length:- $length " . gettype($length) . "\n", $context );

		$where = array();
		$search_bar = isset( $request['search_bar'] ) ? sanitize_text_field($request['search_bar']) : false;
		if ( $search_bar ) {
			$where[] = "( `order_id` = '{$search_bar}' OR `order_number` = '{$search_bar}' OR `shipping_provider` LIKE ( '%{$search_bar}%' ) OR `tracking_number` = '{$search_bar}' OR `shipping_country` LIKE ( '%{$search_bar}%' ) )";
		}

		$late_ship_day = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
		$days = $late_ship_day - 1 ;
		$acive_shipment_status = $request['active_shipment'];

		if ( $acive_shipment_status == 'late_shipment' ) {
			$where[] = "shipping_length > {$days}";
		} elseif ( $acive_shipment_status == 'tracking_issues' ) {
			$where[] = "shipment_status NOT IN ( 'delivered', 'in_transit', 'out_for_delivery', 'pre_transit', 'exception', 'return_to_sender', 'available_for_pickup' )";
		} elseif ( $acive_shipment_status != 'active' ) {
			$where[] = "shipment_status = ( '{$acive_shipment_status}')";
		}

		$shipping_provider = isset( $request['shipping_provider'] ) ? sanitize_text_field( $request['shipping_provider'] ) : false;
		if ( 'all' != $shipping_provider ) {
			$where[] = "`shipping_provider` = '{$shipping_provider}'";
		}

		$where_condition = !empty( $where ) ? 'WHERE ' . implode(" AND ",$where) : '';

		$sum = $wpdb->get_var("
			SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row	
			$where_condition
		");

		$order_query = $wpdb->get_results("
			SELECT * 
				FROM {$woo_trackship_shipment} 
			$where_condition
			ORDER BY
				order_id DESC
			{$limit}
		");

		// $content = print_r( $wpdb->last_query, true);
		// $logger = wc_get_logger();
		// $context = array( 'source' => 'AAA_result' );
		// $logger->info( "AAA_result  \n " . $content . "\n", $context );

		$date_format = 'M d';
			
		$result = array();
		$i = 0;

		foreach( $order_query as $key => $value ){
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $value->order_id );
		
			foreach ( $tracking_items as $key1 => $val1 ) {
				if ( $val1['tracking_number'] == $value->tracking_number ) {
					$formatted_tracking_link = isset( $val1['formatted_tracking_link'] ) ? $val1['formatted_tracking_link'] : '';
					$tracking_url = isset( $val1['ast_tracking_link'] ) && $val1['ast_tracking_link'] ? $val1['ast_tracking_link'] : $formatted_tracking_link;

					$provider_name = $value->new_shipping_provider ? $value->new_shipping_provider : $value->shipping_provider;
					$formatted_provider = trackship_for_woocommerce()->actions->get_provider_name( $provider_name );
					$tracking_provider = isset($formatted_provider) && $formatted_provider ? $formatted_provider : $provider_name;
					$provider_tip_tip = $value->new_shipping_provider ? '<span class="dashicons dashicons-info trackship-tip" title="TrackShip updated ' . $value->shipping_provider . ' to ' . $tracking_provider .'"></span>' : '';
					
					$tracking_number_colom = '<span class="copied_tracking_numnber dashicons dashicons-admin-page" data-number="' . $value->tracking_number . '"></span><a class="open_tracking_details shipment_tracking_number" data-tracking_id="' . $val1['tracking_id'] . '" data-orderid="' . $value->order_id . '" data-nonce="' . wp_create_nonce( 'tswc-' . $value->order_id ) . '">' . $value->tracking_number . '</a>';
				}
			}
			
			$shipping_length = in_array( $value->shipping_length, array( 0, 1 ) ) ? 'Today' : (int)$value->shipping_length. ' days';
			$shipping_length = $value->shipping_length ? $shipping_length : '';
			
			$late_class = 'delivered' == $value->shipment_status ? '' : 'not_delivered' ;
			$late_shipment = $late_ship_day <= $value->shipping_length ? '<span class="dashicons dashicons-info trackship-tip ' . $late_class . '" title="late shipment"></span>' : '';
			
			$active_shipment = '<a href="javascript:void(0);" class="shipments_get_shipment_status" data-orderid="' . $value->order_id . '"><span class="dashicons dashicons-update"></span></a>';
			
			$result[$i] = new \stdClass();
			$result[$i]->et_shipped_at = date_i18n( $date_format, strtotime( $value->shipping_date ) );
			$result[$i]->order_id = '<a href="' . admin_url( 'post.php?post=' . $value->order_id . '&action=edit' ) . '" target="_blank">' . $value->order_number . '</a>';
			$result[$i]->shipment_status = apply_filters("trackship_status_filter", $value->shipment_status );
			$result[$i]->shipment_status_id = $value->shipment_status;
			$result[$i]->shipment_length = '<span class="shipment_length">' . $shipping_length . $late_shipment . '</span>';
			$result[$i]->formated_tracking_provider = $tracking_provider . $provider_tip_tip;
			$result[$i]->tracking_number_colom = $tracking_number_colom;
			$result[$i]->tracking_url = $tracking_url;
			$result[$i]->est_delivery_date = $value->est_delivery_date ? date_i18n( $date_format, strtotime( $value->est_delivery_date ) ) : '';
			$result[$i]->ship_to = $value->shipping_country;
			$result[$i]->refresh_button = 'delivered' == $value->shipment_status ? '' : $active_shipment;
			$i++;
		}

		$obj_result = new \stdclass();
		$obj_result->draw = intval( $request['draw'] );
		$obj_result->recordsTotal = intval( $sum );
		$obj_result->recordsFiltered = intval( $sum );
		$obj_result->data = $result;
		wp_send_json( array( 'success' => true, 'result' => $obj_result ) );
	}

	public function ts_shipping_provider() {
		global $wpdb;
		$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
		$all_providers = $wpdb->get_results( "SELECT shipping_provider FROM {$woo_trackship_shipment} WHERE shipping_provider NOT LIKE ( '%NULL%') GROUP BY shipping_provider" );
		$provider_list = array();
		foreach ( $all_providers as $provider ) {
			$formatted_provider = trackship_for_woocommerce()->actions->get_provider_name( $provider->shipping_provider );
			$provider_name = isset($formatted_provider) && $formatted_provider ? $formatted_provider : $provider->shipping_provider;
			$provider_list[$provider->shipping_provider] = $provider_name;
		}
		wp_send_json( array( 'success' => true, 'result' => $provider_list ) );
	}

	public function ts_logs( $request ) {
		global $wpdb;
		$log_table = $wpdb->prefix . 'zorem_email_sms_log';

		$start = isset( $request['start'] ) ? sanitize_text_field($request['start']) : 0;
		$length = isset( $request['length'] ) ? sanitize_text_field($request['length']) : 25;
		$limit = 'limit ' . $start . ', ' . $length;
		
		$search_bar = isset( $request['search_bar'] ) ? sanitize_text_field($request['search_bar']) : false;
		$shipment_status = isset( $request['shipment_status'] ) ? sanitize_text_field($request['shipment_status']) : false;
		$log_type = isset( $request['log_type'] ) ? sanitize_text_field($request['log_type']) : false;

		$where = array();
		if ( $search_bar ) {
			$where[] = "( `order_id` = '{$search_bar}' OR `order_number` = '{$search_bar}' OR `to` LIKE '%{$search_bar}%' )";
		}
		$where[] = "( `type` LIKE 'Email' OR `sms_type` LIKE 'shipment_status' )";
		if ( $shipment_status ) {
			$where[] = "`shipment_status` = '{$shipment_status}'";
		}
		if ( $log_type ) {
			$where[] = "`type` = '{$log_type}'";
		}

		$where_condition = !empty( $where ) ? 'WHERE ' . implode(" AND ",$where) : '';

		$sum = $wpdb->get_var("
			SELECT COUNT(*) FROM {$log_table} AS row
			$where_condition
		");

		$order_query = $wpdb->get_results("
			SELECT * 
				FROM {$log_table}
			$where_condition
			ORDER BY
				`date` DESC
			{$limit}
		");

		$result = array();
		$i = 0;
		$current_time = strtotime(current_time( 'Y-m-d H:i:s' ));
		
		foreach( $order_query as $key => $value ){

			$notification_time = strtotime( $value->date );
			$time_diff = $current_time - $notification_time;
			if ( 60 > $time_diff ) {
				$condi_time = '<time title="' . date( 'd/m/Y h:i a', $notification_time ) . '">' . $time_diff . ' seconds ago</time>';
			} elseif ( 3600 > $time_diff ) {
				$condi_time = '<time title="' . date( 'd/m/Y h:i a', $notification_time ) . '">' . floor( $time_diff/60 ) . ' mins ago</time>';
			} elseif ( 60*60*24 > $time_diff ) {
				$condi_time = '<time title="' . date( 'd/m/Y h:i a', $notification_time ) . '">' . floor( $time_diff/(60*60) ) . ' hours ago</time>';
			} else {
				$condi_time = '<time title="' . date( 'd/m/Y h:i a', $notification_time ) . '">' . date( 'M d, Y', $notification_time ) . '</time>';
			}

			$result[$i] = new \stdClass();
			$result[$i]->order_id = '<a href="' . admin_url( 'post.php?post=' . $value->order_id . '&action=edit' ) . '" target="_blank">' . $value->order_number . '</a>';
			$result[$i]->shipment_status = apply_filters("trackship_status_filter", $value->shipment_status );
            $result[$i]->date = $condi_time;
            $result[$i]->to = $value->to;
            $result[$i]->type = $value->type == 'Email' ? 'Email' : 'SMS';
            $result[$i]->status = 'Sent' == $value->status ? $value->status : 'Failed';
			$result[$i]->action_button = '<span class="get_log_detail dashicons dashicons-visibility" data-rowid="' . $value->id . '" data-orderid="' . $value->order_id . '"></span>';
			
            $i++;
		}

		$obj_result = new \stdclass();
		$obj_result->draw = intval( $request['draw'] );
		$obj_result->recordsTotal = intval( $sum );
		$obj_result->recordsFiltered = intval( $sum );
		$obj_result->data = $result;
		wp_send_json( array( 'success' => true, 'result' => $obj_result ) );
	}

	public function ts_log_popup( $request ){
		global $wpdb;
		$log_table = $wpdb->prefix . 'zorem_email_sms_log';
		$order_id = isset( $request['order_id'] ) ? sanitize_text_field($request['order_id']) : false;
		$rowid = isset( $request['rowid'] ) ? sanitize_text_field($request['rowid']) : false;

		$row = $wpdb->get_row("
			SELECT * FROM {$log_table}
			WHERE `id` = {$rowid} AND `order_id` = {$order_id}
		");
		$row->shipment_status = apply_filters("trackship_status_filter", $row->shipment_status );
		wp_send_json( array( 'success' => true, 'result' => $row ) );
		// wp_send_json($row);
	}

	public function ts_settings_data( $request ) {
		$result = array();

		$trackship_general_data = trackship_for_woocommerce()->admin->get_trackship_general_data();
		$delivered_data = trackship_for_woocommerce()->admin->get_delivered_data();
		$tracking_page_data = trackship_for_woocommerce()->admin->get_tracking_page_data();
		$sms_provider_data = !function_exists( 'SMSWOO' ) && !is_plugin_active( 'zorem-sms-for-woocommerce/zorem-sms-for-woocommerce.php' ) ? trackship_for_woocommerce()->smswoo_init->smswoo_admin->get_sms_provider_data() : array();
		
		foreach( array_merge( $trackship_general_data, $delivered_data ) as $key => $value ) {
			$result['general_settings'][$key] = get_option( $key );
		}
		$result['general_settings']['wcast_late_shipments_days'] = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
		foreach( $tracking_page_data as $key => $value ) {
			$result['tracking_page'][$key] = get_option( $key );
		}
		foreach( $sms_provider_data as $key => $value ) {
			$result['sms_provider'][$key] = get_option( $key );
		}
		$result['map_provider']['trackship_map_provider'] = get_option( 'trackship_map_provider' );

		$array_list = array();

		foreach ( wc_get_order_statuses() as $key => $val ) {

			if ( 'wc-cancelled' == $key || 'wc-failed' == $key || 'wc-pending' == $key ) {
				continue;
			}

			$status_slug = ( 'wc-' === substr( $key, 0, 3 ) ) ? substr( $key, 3 ) : $key;
			$array_list['status'][$status_slug] = $val;
		}
		$array_list['pages'] = wp_list_pluck( get_pages(), 'post_title', 'ID' );


		$array_list['shippment_providers'] = trackship_for_woocommerce()->admin->get_trackship_provider();

		wp_send_json( array( 'success' => true, 'result' => $result, 'array_list' => $array_list ) );
	}

	public function ts_notifications_data( $request ){
		$ts_notifications = trackship_for_woocommerce()->admin->trackship_shipment_status_notifications_data();
		$result = array();
		foreach ( $ts_notifications as $key => $val ) {
			$result['email_data'][$key]['slug'] = $val['slug'];
			$result['email_data'][$key]['enable_status_name'] = $val['enable_status_name'];
			$result['email_data'][$key]['value'] = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( $val['option_name'], $val['enable_status_name'], '');
			$result['email_data'][$key]['customizer_url'] = $val['customizer_url'];
		}

		wp_send_json( array( 'success' => true, 'result' => $result ) );
	}

	public function ts_update_carrier ( $request ) {
		$content = print_r($request, true);
		$logger = wc_get_logger();
		$context = array( 'source' => 'trackship_update_carrier' );
		$logger->info( "trackship_update_carrier \n" . $content . "\n", $context );

		$order_id = $request->get_param('order_id');
		$tracking_number = $request->get_param('tracking_number');
		$tracking_provider_new = $request->get_param('tracking_provider_new');

		$args = array(
			'new_shipping_provider'	=> $tracking_provider_new,
		);
		trackship_for_woocommerce()->actions->update_shipment_data( $order_id, $tracking_number, $args );

		wp_send_json( array('success' => 'true') );
	}

	public function check_ts4wc_installed( $request ) {
		// check TS4WC installed 
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
		
		//check which shipment tracking plugin active
		$plugin = 'tswc';
		
		if ( is_plugin_active( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php' ) ) {
			$plugin.= '-ast-free';
		}

		if ( is_plugin_active( 'ast-pro/ast-pro.php' ) ) {
			$plugin.= '-ast-pro';
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
	
	public function tracking_webhook( $request ) {
		$content = print_r($request, true);
		$logger = wc_get_logger();
		$context = array( 'source' => 'trackship_tracking_update' );
		$logger->info( "trackship_tracking_update \n" . $content . "\n", $context );
		
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
			$shipment_status[$key]['est_delivery_date'] = $tracking_est_delivery_date ? gmdate('Y-m-d', strtotime($tracking_est_delivery_date)) : null;
			
			update_post_meta( $order_id, 'shipment_status', $shipment_status );
			
			//tracking page link in $shipment_status
			$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $order_id );
			
			$trackship->trigger_tracking_email( $order_id, $previous_status, $tracking_event_status, $tracking_item, $shipment_status[$key] );
			
			$ts_shipment_status[$key]['status'] = $tracking_event_status;
			update_post_meta( $order_id, 'ts_shipment_status', $ts_shipment_status);
			
			$args = array(
				'shipment_status'	=> $shipment_status[$key]['status'],
			);
			$args2 = array(
				'origin_country'		=> $request['origin_country'],
				'destination_country'	=> $request['destination_country'],
				'delivery_number'		=> $request['delivery_number'],
				'delivery_provider'		=> $request['delivery_provider'],
				'shipping_service'		=> $request['shipping_service'],
				'tracking_events'		=> $request['tracking_events']
			);
			$args['est_delivery_date'] = $tracking_est_delivery_date ? gmdate('Y-m-d', strtotime($tracking_est_delivery_date)) : null;
			trackship_for_woocommerce()->actions->update_shipment_data( $order_id, $tracking_item['tracking_number'], $args, $args2 );
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
		update_option( 'wc_ast_api_key', '' );
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

	public function get_permissions_check( $request ) {
		if ( ! wc_rest_check_manager_permissions( 'settings', 'read' ) ) {
			return new WP_Error( 'woocommerce_rest_cannot_view', __( 'Sorry, you cannot list resources.', 'woocommerce' ), array( 'status' => rest_authorization_required_code() ) );
		}
		return true;
	}
}

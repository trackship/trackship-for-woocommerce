<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Trackship_Shipments {

	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		global $wpdb;
	}
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Get the class instance
	 *
	 * @return WC_Trackship_Shipments
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
		
		add_action( 'wp_ajax_get_trackship_shipments', array($this, 'get_trackship_shipments') );
		add_action( 'wp_ajax_get_shipment_status_from_shipments', array($this, 'get_shipment_status_from_shipments') );
		add_action( 'wp_ajax_bulk_shipment_status_from_shipments', array($this, 'bulk_shipment_status_from_shipments') );
		
		//load shipments css js 
		add_action( 'admin_enqueue_scripts', array( $this, 'shipments_styles' ), 1);
	}
	
	/**
	* Load trackship styles.
	*/
	public function shipments_styles( $hook ) {
		
		$page = sanitize_text_field( $_GET['page'] ?? '' );
			
		if ( !in_array( $page, array( 'trackship-for-woocommerce', 'trackship-shipments', 'trackship-dashboard', 'trackship-logs' ) ) ) {
			return;
		}

		$user_plan = get_option( 'user_plan' );

		// Enqueue WooCommerce's Flatpickr and style
		wp_enqueue_script('moment-js', 'https://cdn.jsdelivr.net/momentjs/latest/moment.min.js', array('jquery'), null, true);
		wp_enqueue_script( 'ts_daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js', array('jquery', 'moment-js'), '5.37.0', true );
    	wp_enqueue_style( 'ts_daterangepicker', 'https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css', array(), '5.37.0' );
		
		// Rubik font
		wp_enqueue_style( 'custom-google-fonts', 'https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800&display=swap', array(), trackship_for_woocommerce()->version );

		//dataTables library
		wp_enqueue_script( 'TS-DataTable', 'https://cdn.datatables.net/2.1.8/js/dataTables.js', array ( 'jquery' ), '2.1.8', true);
		wp_enqueue_script( 'DataTable_input', '//cdn.datatables.net/plug-ins/2.1.8/pagination/input.js', array ( 'jquery' ), '2.1.8', true);
		wp_enqueue_style( 'TS-DataTable', 'https://cdn.datatables.net/2.1.8/css/dataTables.dataTables.css', array(), '2.1.8', 'all');

		// Register DataTables buttons
		wp_register_script( 'TS-buttons', 'https://cdn.datatables.net/buttons/3.2.0/js/dataTables.buttons.js', array('jquery'), '3.2.0', true );
	
		// Register pdfmake
		wp_register_script( 'TS-pdfMake', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/pdfmake.min.js', array(), '0.2.7', true );
	
		// Register pdfmake vfs_fonts
		wp_register_script( 'TS-pdfMake-vfsFonts', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.2.7/vfs_fonts.js', array(), '0.2.7', true );
	
		// Register DataTables buttons HTML5
		wp_register_script( 'TS-buttons-html5', 'https://cdn.datatables.net/buttons/3.2.0/js/buttons.html5.min.js', array( 'jquery' ), '3.2.0', true );

		// Register DataTables buttons HTML5
		wp_register_script( 'TS-colVis', 'https://cdn.datatables.net/buttons/3.2.0/js/buttons.colVis.min.js', array( 'jquery' ), '3.2.0', true );

		// Register DataTables Fix Column
		wp_register_script( 'TS-FixColumn1', 'https://cdn.datatables.net/fixedcolumns/5.0.4/js/dataTables.fixedColumns.js', array( 'jquery' ), '5.0.4', true );
		wp_register_script( 'TS-FixColumn2', 'https://cdn.datatables.net/fixedcolumns/5.0.4/js/fixedColumns.dataTables.js', array( 'jquery' ), '5.0.4', true );
		wp_enqueue_style( 'TS-FixColumnCss', 'https://cdn.datatables.net/fixedcolumns/5.0.4/css/fixedColumns.dataTables.css', array(), '5.0.4', 'all');
	
		// Enqueue all scripts
		wp_enqueue_script( 'TS-buttons' );
		wp_enqueue_script( 'TS-pdfMake' );
		wp_enqueue_script( 'TS-pdfMake-vfsFonts' );
		wp_enqueue_script( 'TS-buttons-html5' );
		wp_enqueue_script( 'TS-colVis' );
		wp_enqueue_script( 'TS-FixColumn1' );
		wp_enqueue_script( 'TS-FixColumn2' );

		wp_enqueue_style( 'shipments_styles', trackship_for_woocommerce()->plugin_dir_url() . 'includes/shipments/assets/css/shipments.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_script( 'shipments_script', trackship_for_woocommerce()->plugin_dir_url() . 'includes/shipments/assets/js/shipments.js', array( 'jquery', 'ts_daterangepicker' ), trackship_for_woocommerce()->version, true );
		wp_localize_script('shipments_script', 'shipments_script', array(
			'admin_url'	=> admin_url(),
			'user_plan'	=> $user_plan,
		));
	}
	
	public function get_trackship_shipments() {
		
		check_ajax_referer( '_trackship_shipments', 'ajax_nonce' );
		
		global $wpdb;

		// Sanitize and assign input variables
		$p_start = sanitize_text_field( $_POST['start'] ?? 0 );
		$p_length = sanitize_text_field( $_POST['length'] ?? 25 );
		$limit = $wpdb->prepare( 'LIMIT %d, %d', $p_start, $p_length );
		
		$where = [];
		$params = [];
		$search_bar = sanitize_text_field( $_POST['search_bar'] ?? '' );
		if ( $search_bar ) {
			$like_search = '%' . $wpdb->esc_like( $search_bar ) . '%';
			$where[] = '(order_id = %s OR order_number = %s OR shipping_provider LIKE %s OR tracking_number = %s OR shipping_country LIKE %s)';
			$params = array_merge( $params, [ $search_bar, $search_bar, $like_search, $search_bar, $like_search ] );
		}

		// Get late shipments setting
		$late_ship_day = (int) get_trackship_settings( 'late_shipments_days', 7);
		$days = $late_ship_day - 1 ;

		// Filter shipments by status
		$active_shipment_status = sanitize_text_field($_POST['active_shipment'] ?? '');
		switch ($active_shipment_status) {
			case 'delivered':
				$where[] = "shipment_status = 'delivered'";
				break;
			case 'pending_trackship':
				$where[] = "pending_status = 'pending_trackship'";
				break;
			case 'carrier_unsupported':
				$where[] = "pending_status = 'carrier_unsupported'";
				break;
			case 'late_shipment':
				$where[] = 'shipping_length > %d';
				$params[] = $days;
				break;
			case 'active_late':
				$where[] = "(shipping_length > %d AND shipment_status NOT IN ('delivered', 'return_to_sender'))";
				$params[] = $days;
				break;
			case 'tracking_issues':
				$where[] = "( shipment_status NOT IN ('delivered', 'in_transit', 'out_for_delivery', 'pre_transit', 'exception', 'return_to_sender', 'available_for_pickup') OR pending_status IS NOT NULL )";
				break;
			case 'active':
				$where[] = "shipment_status NOT IN ('delivered', 'return_to_sender')";
				break;
			default:
				if ( 'all_ship' !== $active_shipment_status ) {
					$where[] = 'shipment_status = %s';
					$params[] = $active_shipment_status;
				}
				break;
		}

		// Filter by shipping provider
		$shipping_provider = sanitize_text_field( $_POST['shipping_provider'] ?? '' );
		if ( 'all' != $shipping_provider ) {
			$where[] = 'shipping_provider = %s';
			$params[] = $shipping_provider;
		}

		// Filter by shipping date
		$start_date = sanitize_text_field( $_POST['start_date'] ?? '' );
		$end_date = sanitize_text_field( $_POST['end_date'] ?? '' );
		if ( $start_date && $end_date ) {
			$where[] = 'shipping_date BETWEEN %s AND %s';
			$params[] = $start_date;
			$params[] = $end_date;
		}

		// Compile where clause
		$where_condition = '';
		if ( ! empty( $where ) ) {
			$where_condition = 'WHERE ' . implode( ' AND ', $where );
			$where_condition = $wpdb->prepare( $where_condition, ...$params );
		}

		// Count total records
		$count_sql = "SELECT COUNT(*) FROM {$wpdb->prefix}trackship_shipment t {$where_condition}";
		$sum = $wpdb->get_var( $count_sql );

		// Determine the order direction
		$column_index = wc_clean( $_POST['order'][0]['column'] ?? '' );
		switch ( $column_index ) {
			case '1':
				$column = 'order_id';
				break;
			case '3':
				$column = 'updated_at';
				break;
			default:
				$column = 'shipping_date';
				break;
		}

		// Determine the order direction
		$dir = ( wc_clean( $_POST['order'][0]['dir'] ?? 'desc' ) === 'asc' ) ? 'ASC' : 'DESC';
		$order_by = "{$column} {$dir}";

		// Main query
		$sql = "
			SELECT * 
			FROM {$wpdb->prefix}trackship_shipment t
			LEFT JOIN {$wpdb->prefix}trackship_shipment_meta m ON t.id = m.meta_id
				{$where_condition}
				ORDER BY {$order_by}
				{$limit}
			";

		$order_query = $wpdb->get_results( $sql );

		$date_format = 'M d';

		$result = array();
		$i = 0;
		
		foreach ( $order_query as $key => $value ) {
			$status = $value->pending_status ? $value->pending_status : $value->shipment_status;

			$shipping_length = in_array( $value->shipping_length, array( 0, 1 ) ) ? 'Today' : (int) $value->shipping_length . ' days';
			$shipping_length = $value->shipping_length ? $shipping_length : '';

			$customer = '';
			$formatted_date1 = '';
			$order = wc_get_order( $value->order_id );
			if ( $order ) {
				$customer = trim($order->get_formatted_shipping_full_name()) ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name();
				$order_date = $order->get_date_created();
				// Format as string (e.g., Y-m-d H:i:s)
				if ( $order_date ) {
					$formatted_date1 = $order_date->date( 'M d, Y' );
					$formatted_date2 = $order_date->date( 'M d, Y H:i:s' );
				}
			}

			$result[$i] = new \stdClass();
			$result[$i]->et_shipped_at = date_i18n( 'M d, Y', strtotime( $value->shipping_date ) );
			$result[$i]->updated_at = [ 'updated_date1' => $value->updated_at ? date_i18n( 'M d, Y', strtotime( $value->updated_at ) ) : '', 'updated_date2' => $value->updated_at ? date_i18n( 'M d, Y H:i:s', strtotime( $value->updated_at ) ) : '' ];
			$result[$i]->order_id = $value->order_id;
			$result[$i]->order_date = [ 'formatted_date1' => $formatted_date1, 'formatted_date2' => $formatted_date1 ? $formatted_date2 : '' ];
			$result[$i]->delivery_number = $value->delivery_number;
			$result[$i]->last_event_date = $value->last_event_time ? gmdate( $date_format, strtotime( $value->last_event_time ) ) : '';
			$result[$i]->last_event = $value->last_event ? $value->last_event : '';
			$result[$i]->order_number = wc_get_order( $value->order_id ) ? wc_get_order( $value->order_id )->get_order_number() : $value->order_id;
			$result[$i]->shipment_status = apply_filters('trackship_status_filter', $status );
			$result[$i]->shipment_status_id = $status;
			$result[$i]->shipment_length = [ 'late_class' => 'delivered' == $status ? '' : 'not_delivered', 'shipping_length' => $shipping_length, 'cond' => $late_ship_day <= $value->shipping_length ];
			$result[$i]->formated_tracking_provider = trackship_for_woocommerce()->actions->get_provider_name( $value->shipping_provider );
			$result[$i]->tracking_number = $value->tracking_number;
			$result[$i]->est_delivery_date = $value->est_delivery_date ? date_i18n( $date_format, strtotime( $value->est_delivery_date ) ) : '';
			$result[$i]->ship_from = $value->origin_country ? [ 'country_code' => $value->origin_country, 'country_name' => $this->get_country_name( $value->origin_country ) ] : ['country_code' => ''];
			$result[$i]->ship_to = $value->destination_country ? [ 'country_code' => $value->destination_country, 'country_name' => $this->get_country_name( $value->destination_country ) ] : ['country_code' => ''];
			$result[$i]->ship_state = $value->destination_state ?? '';
			$result[$i]->ship_city = $value->destination_city ?? '';
			$result[$i]->nonce = wp_create_nonce( 'tswc-' . $value->order_id );
			$result[$i]->customer = $customer;
			$i++;
		}

		$obj_result = new \stdclass();
		$obj_result->draw = isset($_POST['draw']) ? intval( wc_clean($_POST['draw']) ) : '';
		$obj_result->recordsTotal = intval( $sum );
		$obj_result->recordsFiltered = intval( $sum );
		$obj_result->data = $result;
		$obj_result->is_success = true;
		echo json_encode($obj_result);
		exit;
	}

	/*
	* get flag icon
	* return flag icon HTML
	*/
	public function get_country_name( $country_code ) {
		return WC()->countries->countries[ $country_code ] ? WC()->countries->countries[ $country_code ] : $country_code;
	}

	/*
	* get shiment lenth of tracker
	* return (int)days
	*/
	public function get_shipment_length( $row ) {

		$status = $row->shipment_status;
		if ( in_array($status, ['pending_trackship', 'carrier_unsupported', 'unknown', 'insufficient_balance', 'label_cancelled', 'invalid_tracking', 'invalid_carrier', 'pending', 'unauthorized', 'deleted', 'connection_issue', 'ssl_error', 'expired', 'missing_order_id', 'missing_tracking', 'missing_carrier', 'unauthorized_api_key', 'unauthorized_store', 'unauthorized_store_api_key', 'shipped', '']) ) {
			return;
		}

		$tracking_events = $row->tracking_events ? json_decode($row->tracking_events) : $row->tracking_events;
		if ( empty($tracking_events ) || 0 == count( $tracking_events ) ) {
			return 0;
		}

		$first = reset($tracking_events);
		$first = (array) $first;

		$first_date = $first['datetime'];
		$last_date = $row->last_event_time ? $row->last_event_time : gmdate('Y-m-d H:i:s');
		
		if ( !in_array( $status, ['delivered', 'return_to_sender'] ) ) {
			$last_date = gmdate('Y-m-d H:i:s');
		}
		$days = $this->get_num_of_days( $first_date, $last_date );
		return (int) $days;
	}
	
	/*
	* Get number of days B/W 2 dates
	*/
	public function get_num_of_days( $first_date, $last_date ) {
		$date2 = new DateTime( gmdate( 'Y-m-d', strtotime($first_date) ) );
		$date1 = new DateTime( gmdate( 'Y-m-d', strtotime($last_date) ) );
		$interval = $date1->diff($date2);
		return $interval->format('%a');
	}

	/*
	* get shiment status single order	
	*/
	public function get_shipment_status_from_shipments() {
		check_ajax_referer( '_trackship_shipments', 'security' );
		$order_id = isset( $_POST['order_id'] ) ? wc_clean($_POST['order_id']) : '';
		trackship_for_woocommerce()->actions->schedule_trackship_trigger( $order_id );
		wp_send_json(true);
	}
	
	/*
	* get shiment status from bulk
	*/
	public function bulk_shipment_status_from_shipments() {
		check_ajax_referer( '_trackship_shipments', 'security' );
		$orderids = isset( $_POST['orderids'] ) ? wc_clean($_POST['orderids']) : [];
		foreach ( ( array ) $orderids as $order_id ) {
			trackship_for_woocommerce()->actions->schedule_trackship_trigger( $order_id );
		}
		wp_send_json(true);
	}
}

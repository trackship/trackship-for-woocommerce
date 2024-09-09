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
		
		// Rubik font
		wp_enqueue_style( 'custom-google-fonts', 'https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800&display=swap', array(), time() );

		//dataTables library
		wp_enqueue_script( 'TS-DataTable', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js', array ( 'jquery' ), '1.13.4', true);
		wp_enqueue_script( 'DataTable_input', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/input.js', array ( 'jquery' ), '1.10.7', true);
		wp_enqueue_style( 'TS-DataTable', 'https://cdn.datatables.net/v/dt/dt-1.13.4/datatables.min.css', array(), '1.10.18', 'all');

		// Register DataTables buttons
		wp_register_script( 'TS-buttons', 'https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js', array('jquery'), '2.3.6', true );
	
		// Register pdfmake
		wp_register_script( 'TS-pdfMake', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js', array(), '0.1.53', true );
	
		// Register pdfmake vfs_fonts
		wp_register_script( 'TS-pdfMake-vfsFonts', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js', array(), '0.1.53', true );
	
		// Register DataTables buttons HTML5
		wp_register_script( 'TS-buttons-html5', 'https://cdn.datatables.net/buttons/2.3.6/js/buttons.html5.min.js', array( 'jquery' ), '2.3.6', true );

		// Register DataTables buttons HTML5
		wp_register_script( 'TS-colVis', 'https://cdn.datatables.net/buttons/2.3.6/js/buttons.colVis.min.js', array( 'jquery' ), '2.3.6', true );
	
		// Enqueue all scripts
		wp_enqueue_script( 'TS-buttons' );
		wp_enqueue_script( 'TS-pdfMake' );
		wp_enqueue_script( 'TS-pdfMake-vfsFonts' );
		wp_enqueue_script( 'TS-buttons-html5' );
		wp_enqueue_script( 'TS-colVis' );

		wp_enqueue_style( 'shipments_styles', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/css/shipments.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_script( 'shipments_script', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/shipments.js', array( 'jquery' ), trackship_for_woocommerce()->version, true );
		wp_localize_script('shipments_script', 'shipments_script', array(
			'admin_url'	=> admin_url(),
			'user_plan'	=> $user_plan,
		));
	}
	
	public function get_trackship_shipments() {
		
		check_ajax_referer( '_trackship_shipments', 'ajax_nonce' );
		
		global $wpdb;

		// Sanitize and assign input variables
		$p_start = sanitize_text_field( $_POST['start'] ?? '' );
		$p_length = sanitize_text_field( $_POST['length'] ?? '' );
		$limit = 'LIMIT ' . $p_start . ', ' . $p_length;
		
		$where = [];
		$search_bar = sanitize_text_field( $_POST['search_bar'] ?? '' );
		if ( $search_bar ) {
			$where[] = "( `order_id` = '{$search_bar}' OR `order_number` = '{$search_bar}' OR `shipping_provider` LIKE ( '%{$search_bar}%' ) OR `tracking_number` = '{$search_bar}' OR `shipping_country` LIKE ( '%{$search_bar}%' ) )";
		}

		// Get late shipments setting
		$late_ship_day = get_trackship_settings( 'late_shipments_days', 7);
		$days = $late_ship_day - 1 ;

		// Filter shipments by status
		$active_shipment_status = sanitize_text_field($_POST['active_shipment'] ?? '');
		switch ($active_shipment_status) {
			case 'delivered':
				$where[] = "shipment_status = 'delivered'";
				break;
			case 'late_shipment':
				$where[] = "shipping_length > {$days}";
				break;
			case 'tracking_issues':
				$where[] = "shipment_status NOT IN ('delivered', 'in_transit', 'out_for_delivery', 'pre_transit', 'exception', 'return_to_sender', 'available_for_pickup') OR pending_status IS NOT NULL";
				break;
			case 'active':
				$where[] = "shipment_status != 'delivered'";
				break;
			default:
				if ($active_shipment_status !== 'all_ship') {
					$where[] = $wpdb->prepare("shipment_status = %s", $active_shipment_status);
				}
				break;
		}

		// Filter by shipping provider
		$shipping_provider = sanitize_text_field( $_POST['shipping_provider'] ?? '' );
		if ( 'all' != $shipping_provider ) {
			$where[] = "`shipping_provider` = '{$shipping_provider}'";
		}

		$where_condition = !empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		// Count total records
		$sum = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}trackship_shipment $where_condition");

		// Determine the order direction
		$column = isset( $_POST['order'][0]['column'] ) && '1' == wc_clean( $_POST['order'][0]['column'] ) ? 'order_id' : 'shipping_date';
		$column = isset( $_POST['order'][0]['column'] ) && '3' == wc_clean( $_POST['order'][0]['column'] ) ? 'updated_at' : $column;

		// Determine the order direction
		$dir = isset( $_POST['order'][0]['dir'] ) && 'asc' == wc_clean($_POST['order'][0]['dir']) ? ' ASC' : ' DESC';
		$order_by = $column . $dir;
		
		$order_query = $wpdb->get_results( $wpdb->prepare("
			SELECT * 
				FROM {$wpdb->prefix}trackship_shipment t
				LEFT JOIN {$wpdb->prefix}trackship_shipment_meta m
				ON t.id = m.meta_id
				{$where_condition}
			ORDER BY
				%1s
			%2s
		", $order_by, $limit ) );
		
		$date_format = 'M d';

		$result = array();
		$i = 0;
		
		foreach ( $order_query as $key => $value ) {
			$status = $value->pending_status ? $value->pending_status : $value->shipment_status;

			$shipping_length = in_array( $value->shipping_length, array( 0, 1 ) ) ? 'Today' : (int) $value->shipping_length . ' days';
			$shipping_length = $value->shipping_length ? $shipping_length : '';

			$customer = '';
			$order = wc_get_order( $value->order_id );
			if ( $order ) {
				$customer = trim($order->get_formatted_shipping_full_name()) ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name();
			}

			$result[$i] = new \stdClass();
			$result[$i]->et_shipped_at = date_i18n( 'M d, Y', strtotime( $value->shipping_date ) );
			$result[$i]->updated_at = [ 'updated_date1' => $value->updated_at ? date_i18n( 'M d, Y', strtotime( $value->updated_at ) ) : '', 'updated_date2' => $value->updated_at ? date_i18n( 'M d, Y H:i:s', strtotime( $value->updated_at ) ) : '' ];
			$result[$i]->order_id = $value->order_id;
			$result[$i]->delivery_number = $value->delivery_number;
			$result[$i]->last_event = $value->last_event ? gmdate( $date_format, strtotime( $value->last_event_time ) ) . ': ' . $value->last_event : '';
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

		$tracking_events = $row->tracking_events ? json_decode($row->tracking_events) : $row->tracking_events;
		if ( empty($tracking_events ) || 0 == count( $tracking_events ) ) {
			return 0;
		}

		$first = reset($tracking_events);
		$first = (array) $first;

		$first_date = $first['datetime'];
		$last_date = $row->last_event_time ? $row->last_event_time : gmdate('Y-m-d H:i:s');
		
		$status = $row->shipment_status;
		if ( 'delivered' != $status ) {
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
			trackship_for_woocommerce()->actions->set_temp_pending( $order_id );
			as_schedule_single_action( time() + 1, 'trackship_tracking_apicall', array( $order_id ) );
		}
		wp_send_json(true);
	}
}

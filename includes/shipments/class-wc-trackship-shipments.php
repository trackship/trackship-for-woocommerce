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
		$this->shipment_table = $wpdb->prefix . 'trackship_shipment';
		$this->shipment_meta = $wpdb->prefix . 'trackship_shipment_meta';
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
	 * @return WC_Advanced_Shipment_Tracking_Admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/*
	* init from parent mail class
	*/
	public function init(){	
		
		add_action( 'wp_ajax_get_trackship_shipments', array($this, 'get_trackship_shipments') );
		add_action( 'wp_ajax_get_shipment_status_from_shipments', array($this, 'get_shipment_status_from_shipments') );
		add_action( 'wp_ajax_bulk_shipment_status_from_shipments', array($this, 'bulk_shipment_status_from_shipments') );
		
		//load shipments css js 
		add_action( 'admin_enqueue_scripts', array( $this, 'shipments_styles' ), 1);
	}
	
	/**
	* Load trackship styles.
	*/
	public function shipments_styles($hook) {
		
		$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
			
		if ( !in_array( $page, array( 'trackship-for-woocommerce', 'trackship-shipments', 'trackship-dashboard', 'trackship-logs' ) ) ) {
			return;
		}
		
		$user_plan = get_option( 'user_plan' );
		?>
		<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Rubik:wght@400;500;600;700;800&display=swap">
		<?php
		
		//dataTables library
		wp_enqueue_script( 'TS-DataTable', 'https://cdn.datatables.net/1.13.4/js/jquery.dataTables.min.js',  array ( 'jquery' ), '1.13.4', true);
		wp_enqueue_script( 'DataTable_input', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/input.js',  array ( 'jquery' ), '1.10.7', true);
		wp_enqueue_style( 'TS-DataTable', 'https://cdn.datatables.net/v/dt/dt-1.13.4/datatables.min.css', array(), '1.10.18', 'all');

		// Register DataTables buttons
		wp_register_script( 'TS-buttons', 'https://cdn.datatables.net/buttons/2.3.6/js/dataTables.buttons.min.js', array('jquery'), '2.3.6', true );
	
		// Register pdfmake
		wp_register_script( 'TS-pdfMake', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js', array(), '0.1.53', true );
	
		// Register pdfmake vfs_fonts
		wp_register_script( 'TS-pdfMake-vfsFonts', 'https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js', array('pdfMake'), '0.1.53', true );
	
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

		wp_enqueue_style( 'shipments_styles',  trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/css/shipments.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_script( 'shipments_script',  trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/shipments.js', array( 'jquery' ), trackship_for_woocommerce()->version, true );
		wp_localize_script('shipments_script', 'shipments_script', array(
			'admin_url'   =>  admin_url(),
			'user_plan'   =>  $user_plan,
		));
	}
	
	public function get_trackship_shipments() {
		
		check_ajax_referer( '_trackship_shipments', 'ajax_nonce' );
		
		global $wpdb;
		$woo_trackship_shipment = $this->shipment_table;
		$meta_table = $this->shipment_meta;
		$limit = 'limit ' . sanitize_text_field($_POST['start']).', '.sanitize_text_field($_POST['length']);
		
		$where = array();
		$search_bar = isset( $_POST['search_bar'] ) ? sanitize_text_field($_POST['search_bar']) : false;
		if ( $search_bar ) {
			$where[] = "( `order_id` = '{$search_bar}' OR `order_number` = '{$search_bar}' OR `shipping_provider` LIKE ( '%{$search_bar}%' ) OR `tracking_number` = '{$search_bar}' OR `shipping_country` LIKE ( '%{$search_bar}%' ) )";
		}
		
		$late_ship_day = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
		$days = $late_ship_day - 1 ;
		$active_shipment_status = $_POST['active_shipment'] ;
		
		if ( $active_shipment_status == 'delivered' ) {
			$where[] = "shipment_status = ( 'delivered')";
		} elseif ( $active_shipment_status == 'late_shipment' ) {
			$where[] = "shipping_length > {$days}";
		} elseif ( $active_shipment_status == 'tracking_issues' ) {
			$where[] = "shipment_status NOT IN ( 'delivered', 'in_transit', 'out_for_delivery', 'pre_transit', 'exception', 'return_to_sender', 'available_for_pickup' ) OR pending_status IS NOT NULL";
		} elseif ( $active_shipment_status != 'active' ) {
			$where[] = "shipment_status = ( '{$active_shipment_status}')";
		}

		$shipping_provider = isset( $_POST['shipping_provider'] ) ? sanitize_text_field( $_POST['shipping_provider'] ) : false;
		if ( 'all' != $shipping_provider ) {
			$where[] = "`shipping_provider` = '{$shipping_provider}'";
		}		
		
		$where_condition = !empty( $where ) ? 'WHERE ' . implode(" AND ",$where) : '';

		$sum = $wpdb->get_var("
			SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row1
			$where_condition
		");

		$column = '1' == $_POST['order'][0]['column'] ? 'order_id' : 'shipping_date';
		$dir = 'asc' == $_POST['order'][0]['dir'] ? ' ASC' : ' DESC';
		$order_by = $column . $dir;

		$order_query = $wpdb->get_results("
			SELECT * 
				FROM {$woo_trackship_shipment} t
				LEFT JOIN {$meta_table} m
				ON t.id = m.meta_id
			$where_condition
			ORDER BY
				{$order_by}
			{$limit}
		");
		
		$date_format = 'M d';
			
		$result = array();
		$i = 0;
		$total_data = 1;
		
		foreach( $order_query as $key => $value ){
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $value->order_id );
			if ( !$tracking_items ) {
				continue;
			}
			$status = $value->pending_status ? $value->pending_status : $value->shipment_status;
			foreach ( $tracking_items as $key1 => $val1 ) {
				if ( $val1['tracking_number'] == $value->tracking_number ) {
					$tracking_url = $val1['tracking_page_link'] ?  $val1['tracking_page_link'] : $val1['formatted_tracking_link'];
					$provider_name = isset($value->new_shipping_provider) ? $value->new_shipping_provider : $value->shipping_provider;
					// print_r($provider_name);
					$formatted_provider = trackship_for_woocommerce()->actions->get_provider_name( $provider_name );
					$tracking_provider = isset($formatted_provider) && $formatted_provider ? $formatted_provider : $provider_name;
					$provider_tip_tip = $value->new_shipping_provider ? '<span class="dashicons dashicons-info trackship-tip" title="TrackShip updated ' . $value->shipping_provider . ' to ' . $tracking_provider .'"></span>' : '';
					
					$tracking_number_colom = '<span class="copied_tracking_numnber dashicons dashicons-admin-page" data-number="' . $value->tracking_number . '"></span><a class="open_tracking_details shipment_tracking_number" data-tracking_id="' . $val1['tracking_id'] . '" data-orderid="' . $value->order_id . '" data-tnumber="' . $value->tracking_number . '" data-nonce="' . wp_create_nonce( 'tswc-' . $value->order_id ) . '">' . $value->tracking_number . '</a>';
				}
			}
			
			$last_event = '';
			if ( $value->last_event ) {
				$last_event = gmdate( $date_format, strtotime( $value->last_event_time ) ) . ': ' . $value->last_event;
				$last_event = '<span class="last_event trackship-tip" title="' . $last_event . '">' . $last_event . '</span>';
			} else {
				$last_event = 'N/A';
			}

			$shipping_length = in_array( $value->shipping_length, array( 0, 1 ) ) ? 'Today' : (int)$value->shipping_length. ' days';
			$shipping_length = $value->shipping_length ? $shipping_length : '';
			
			$late_class = 'delivered' == $status ? '' : 'not_delivered' ;
			$late_shipment = $late_ship_day <= $value->shipping_length ? '<span class="dashicons dashicons-info trackship-tip ' . $late_class . ' late_shipment" title="late shipment"></span>' : '';
			
			$active_shipment = '<a href="javascript:void(0);" class="shipments_get_shipment_status" data-orderid="' . $value->order_id . '" data-tnumber="' . $value->tracking_number . '"><span class="dashicons dashicons-update"></span></a>';

			$customer = '';
			$order = wc_get_order( $value->order_id );
			if ( $order ) {
				$customer = trim($order->get_formatted_shipping_full_name()) ? $order->get_formatted_shipping_full_name() : $order->get_formatted_billing_full_name();
			}

			$ori_country = $value->origin_country;
			$dest_country = $value->destination_country;
			$checkbox = '<input type="checkbox" class="shipment_checkbox" data-orderid="' . $value->order_id . '" data-tnumber="' . $value->tracking_number . '">';
			
			$result[$i] = new \stdClass();
			$result[$i]->et_shipped_at = '<span class="trackship-tip" title="' . date_i18n( 'M d, Y', strtotime( $value->shipping_date ) ) . '">' . date_i18n( 'M d, Y', strtotime( $value->shipping_date ) ) . '</span>';
			$result[$i]->checkbox = $checkbox;
			$result[$i]->order_id = $value->order_id;
			$result[$i]->last_event = $last_event;
			$result[$i]->order_number = wc_get_order( $value->order_id ) ? wc_get_order( $value->order_id )->get_order_number() : $value->order_id;
			$result[$i]->shipment_status = apply_filters("trackship_status_filter", $status );
			$result[$i]->shipment_status_id = $status;
			$result[$i]->shipment_length = '<span class="shipment_length ' . $late_class. '">' . $late_shipment . $shipping_length . '</span>';
			$result[$i]->formated_tracking_provider = $tracking_provider . $provider_tip_tip;
			$result[$i]->tracking_number_colom = $tracking_number_colom;
			$result[$i]->tracking_url = $tracking_url;
			$result[$i]->est_delivery_date = $value->est_delivery_date ? date_i18n( $date_format, strtotime( $value->est_delivery_date ) ) : '';
			$result[$i]->ship_from = $ori_country ? $this->get_flag_icon( $ori_country ) : '';
			$result[$i]->ship_to = $dest_country ? $this->get_flag_icon( $dest_country ) : '';
			$result[$i]->customer = $customer;
			$result[$i]->refresh_button = 'delivered' == $status ? '' : $active_shipment;
			$i++;
		}

		$obj_result = new \stdclass();
		$obj_result->draw = intval( $_POST['draw'] );
		$obj_result->recordsTotal = intval( $sum );
		$obj_result->recordsFiltered = intval( $sum );
		$obj_result->data = $result;
		$obj_result->is_success = true;
		echo json_encode($obj_result);
		exit;
	}

	public function get_flag_icon( $country_code ) {
		// Country name form WC ->   WC()->countries->countries[ $country_code ]
		$country_name = 'GB' == $country_code ? 'UK' : WC()->countries->countries[ $country_code ];
		$country_name = 'US' == $country_code ? 'USA' : $country_name;
		return '<img class="country_flag" src="http://purecatamphetamine.github.io/country-flag-icons/3x2/' . $country_code . '.svg">' . $country_name;
	}

	/*
	* get shiment lenth of tracker
	* return (int)days
	*/
	public function get_shipment_length($row){

		$tracking_events = $row->tracking_events ? json_decode($row->tracking_events) : $row->tracking_events;

		if( empty($tracking_events ))return 0;
		if( count( $tracking_events ) == 0 )return 0;		
		$first = reset($tracking_events);
		$first = (array) $first;

		$first_date = $first['datetime'];
		$last_date = $row->last_event_time ? $row->last_event_time : date("Y-m-d H:i:s");
		
		$status = $row->shipment_status;
		if( $status != 'delivered' ){
			$last_date = date("Y-m-d H:i:s");
		}		
		
		$days = $this->get_num_of_days( $first_date, $last_date );		
		return (int)$days;
	}
	
	/*
	*
	*/
	public function get_num_of_days( $first_date, $last_date ){
		$date1 = strtotime($first_date);
		$date2 = strtotime($last_date);
		$diff = abs($date2 - $date1);
		return date( "d", $diff );
	}

	/*
	* get shiment status single order	
	*/
	public function get_shipment_status_from_shipments(){
		check_ajax_referer( '_trackship_shipments', 'security' );
		$order_id = wc_clean($_POST['order_id']);
		$trackship = new WC_Trackship_Actions;
		$trackship->schedule_trackship_trigger( $order_id );		
		wp_send_json(true);
	}	
	
	/*
	* get shiment status from bulk
	*/
	public function bulk_shipment_status_from_shipments(){
		check_ajax_referer( '_trackship_shipments', 'security' );
		foreach ( (array)$_POST['orderids'] as $order_id ) {
			trackship_for_woocommerce()->actions->set_temp_pending( $order_id );					
			wp_schedule_single_event( time() + 1, 'wcast_retry_trackship_apicall', array( $order_id ) );								
		}				
		wp_send_json(true);
	}		
}

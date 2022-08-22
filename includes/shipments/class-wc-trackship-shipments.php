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
		wp_enqueue_script( 'DataTable', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/jquery.dataTables.min.js',  array ( 'jquery' ), '1.10.18', true);
		wp_enqueue_script( 'DataTable_input', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/input.js',  array ( 'jquery' ), '1.10.7', true);
		wp_enqueue_style( 'DataTable', trackship_for_woocommerce()->plugin_dir_url().'/includes/shipments/assets/css/jquery.dataTables.min.css', array(), '1.10.18', 'all');
		
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
		$limit = 'limit ' . sanitize_text_field($_POST['start']).', '.sanitize_text_field($_POST['length']);
		
		$where = array();
		$search_bar = isset( $_POST['search_bar'] ) ? sanitize_text_field($_POST['search_bar']) : false;
		if ( $search_bar ) {
			$where[] = "( `order_id` = '{$search_bar}' OR `order_number` = '{$search_bar}' OR `shipping_provider` LIKE ( '%{$search_bar}%' ) OR `tracking_number` = '{$search_bar}' OR `shipping_country` LIKE ( '%{$search_bar}%' ) )";
		}
		
		$late_ship_day = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
		$days = $late_ship_day - 1 ;
		$acive_shipment_status = $_POST['active_shipment'] ;
		
		if ( $acive_shipment_status == 'delivered' ) {
			$where[] = "shipment_status = ( 'delivered')";
		} elseif ( $acive_shipment_status == 'late_shipment' ) {
			$where[] = "shipping_length > {$days}";
		} elseif ( $acive_shipment_status == 'tracking_issues' ) {
			$where[] = "shipment_status NOT IN ( 'delivered', 'in_transit', 'out_for_delivery', 'pre_transit', 'exception', 'return_to_sender', 'available_for_pickup' )";
		} elseif ( $acive_shipment_status != 'active' ) {
			$where[] = "shipment_status = ( '{$acive_shipment_status}')";
		}

		$shipping_provider = isset( $_POST['shipping_provider'] ) ? sanitize_text_field( $_POST['shipping_provider'] ) : false;
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
		
		$date_format = 'M d';
			
		$result = array();
		$i = 0;
		$total_data = 1;
		
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
			$result[$i]->order_id = $value->order_id;
			$result[$i]->order_number = wc_get_order( $value->order_id ) ? wc_get_order( $value->order_id )->get_order_number() : $value->order_id;
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
		$obj_result->draw = intval( $_POST['draw'] );
		$obj_result->recordsTotal = intval( $sum );
		$obj_result->recordsFiltered = intval( $sum );
		$obj_result->data = $result;
		$obj_result->is_success = true;
		echo json_encode($obj_result);
		exit;
	}

	/*
	* get shiment lenth of tracker
	* return (int)days
	*/
	public function get_shipment_length($ep_tracker){
		if( empty($ep_tracker['tracking_events'] ))return 0;
		if( count( $ep_tracker['tracking_events'] ) == 0 )return 0;		
		
		$first = reset($ep_tracker['tracking_events']);
		$first_date = $first->datetime;
		
		$last = ( isset( $ep_tracker['tracking_destination_events'] ) && count( $ep_tracker['tracking_destination_events'] ) > 0  ) ? end($ep_tracker['tracking_destination_events']) : end($ep_tracker['tracking_events']);
		$last_date = $last->datetime;
		
		$status = $ep_tracker['status'];
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
		echo 1;exit;
	}	
	
	/*
	* get shiment status from bulk
	*/
	public function bulk_shipment_status_from_shipments(){		
		foreach ( (array)$_POST['order_id'] as $order_id ) {						
			wp_schedule_single_event( time() + 1, 'wcast_retry_trackship_apicall', array( $order_id ) );								
		}				
		echo 1;exit;
	}		
}

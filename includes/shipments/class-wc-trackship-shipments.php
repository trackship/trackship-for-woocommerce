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
				
		if( 'trackship-for-woocommerce' != $page && 'trackship-shipments' != $page ) {
			return;
		}
		
		$user_plan = get_option( 'user_plan' );
		
		//Daterangepicker library
		wp_enqueue_style( 'daterangepicker', trackship_for_woocommerce()->plugin_dir_url().'/includes/shipments/assets/css/daterangepicker.css', array(), '3.14.1', 'all');				
		wp_enqueue_script( 'moment_js', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/moment.min.js',  array ( 'jquery' ), '2.18.1', true);
		wp_enqueue_script( 'daterangepicker', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/daterangepicker.min.js',  array ( 'jquery' ), '3.14.1', true);
		
		//dataTables library
		wp_enqueue_script( 'DataTable', trackship_for_woocommerce()->plugin_dir_url() . '/includes/shipments/assets/js/jquery.dataTables.min.js',  array ( 'jquery' ), '1.10.18', true);
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
		
		$limit = "limit ".sanitize_text_field($_POST['start']).", ".sanitize_text_field($_POST['length'])."";
		$late_shipments_email_settings = get_option( 'late_shipments_email_settings' );
		$wcast_late_shipments_days = isset($late_shipments_email_settings['wcast_late_shipments_days']) && is_null( $late_shipments_email_settings['wcast_late_shipments_days'] ) ? $late_shipments_email_settings['wcast_late_shipments_days'] : 7;
		$days = $wcast_late_shipments_days - 1 ;
		$late_ship_condi = 'late_shipment' == $_POST['shipment_status'] ? "AND shipping_length > {$days}" : '';
		$order_number = isset( $_POST['search_bar'] ) ? sanitize_text_field($_POST['search_bar']) : false;
		
		if ( $_POST['active_shipment'] == 'active' ) {
			$shipment_status = "shipment_status NOT LIKE ( '%delivered%')";
			if ( $_POST['shipment_status'] != 'late_shipment' && $_POST['shipment_status'] != 'all_tracking_statuses' ){		
				$shipment_status .= "AND shipment_status LIKE ( '%{$_POST['shipment_status']}%')";
			}
		} elseif ( $_POST['active_shipment'] == 'delivered' ) {
			$shipment_status = "shipment_status LIKE ( '%delivered%')";
		}
		
		$sum = $wpdb->get_var("
			SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row	
				WHERE 
					{$shipment_status}
					AND order_number LIKE ( '%{$order_number}%' )
					{$late_ship_condi}
		");
		
		$order_query = $wpdb->get_results("
			SELECT * 
				FROM {$woo_trackship_shipment} 
			WHERE 
				{$shipment_status}
				AND order_number LIKE ( '%{$order_number}%' )
				{$late_ship_condi}
			ORDER BY
				order_id DESC
			{$limit}
		");
		
		$date_format = 'd/m/Y' == get_option( 'date_format' ) ? 'd/m/Y' : 'm/d/Y';
			
		$result = array();
		$i = 0;
		$total_data = 1;
		$late_shipments_email_settings = get_option( 'late_shipments_email_settings' );
		$wcast_late_shipments_days = isset( $late_shipments_email_settings['wcast_late_shipments_days'] ) ? $late_shipments_email_settings['wcast_late_shipments_days'] : '7';
		
		foreach( $order_query as $key => $value ){			
			
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $value->order_id );
			foreach ( $tracking_items as $key1 => $val1 ) {
				if ( $val1['tracking_number'] == $value->tracking_number ) {
					$tracking_url = isset( $val1['ast_tracking_link'] ) && $val1['ast_tracking_link'] ? $val1['ast_tracking_link'] : $val1['formatted_tracking_link'];
					$tracking_provider = $val1['formatted_tracking_provider'] ? $val1['formatted_tracking_provider'] : $value->shipping_provider;
				}
			}
			
			$shipping_length = in_array( $value->shipping_length, array( 0, 1 ) ) ? 'Today' : (int)$value->shipping_length. ' days';
			$shipping_length = $value->shipping_length ? $shipping_length : '';
			
			$late_shipment = $wcast_late_shipments_days <= $value->shipping_length ? '<img class="trackship-tip" title="late shipment" src="' . esc_url( trackship_for_woocommerce()->plugin_dir_url() ) . 'assets/css/icons/invalid-tracking-number.png">' : '';
			$late_shipment = $_POST['active_shipment'] != 'delivered' ? $late_shipment : '' ;
			
			$active_shipment = '<a href="javascript:void(0);" class="shipments_get_shipment_status" data-orderid="' . $value->order_id . '"><span class="dashicons dashicons-update"></span></a>';
			$delivered_shipment = '<span class="dashicons dashicons-minus"></span>';
			
			$result[$i] = new \stdClass();
			$result[$i]->et_shipped_at = date_i18n( $date_format, strtotime( $value->shipping_date ) );
			$result[$i]->order_id = $value->order_id;
			$result[$i]->order_number = apply_filters( 'woocommerce_order_number', $value->order_id, wc_get_order( $value->order_id ) );
			$result[$i]->shipment_status = apply_filters("trackship_status_filter", $value->shipment_status );
			$result[$i]->shipment_status_id = $value->shipment_status;
			$result[$i]->shipment_length = '<span class="shipment_length">' . $shipping_length . $late_shipment . '</span>';
			$result[$i]->formated_tracking_provider = $tracking_provider;
			$result[$i]->tracking_number = $value->tracking_number;
			$result[$i]->tracking_url = $tracking_url;
			$result[$i]->est_delivery_date = $value->est_delivery_date;
			$result[$i]->ship_to = $value->shipping_country;
			$result[$i]->refresh_button = 'delivered' == $value->shipment_status ? $delivered_shipment : $active_shipment;
			
			$i++;
		}

		$obj_result = new \stdclass();
		$obj_result->draw = intval( $_POST['draw'] );
		$obj_result->recordsTotal = intval( $sum );
		$obj_result->recordsFiltered = intval( $sum );
		$obj_result->data = $result;
		//$obj_result->last_sql = $wpdb->last_query;
		$obj_result->is_success = true;
		//$obj_result->z_total = $late_shipment;
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
	function get_num_of_days( $first_date, $last_date ){
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

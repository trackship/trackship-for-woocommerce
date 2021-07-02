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
			if ( is_plugin_active_for_network( 'trackship-for-woocommerce/woocommerce-advanced-shipment-tracking.php' ) ) {
				$main_blog_prefix = $wpdb->get_blog_prefix(BLOG_ID_CURRENT_SITE);			
				$this->table = $main_blog_prefix."woo_shippment_provider";	
			} else{
				$this->table = $wpdb->prefix."woo_shippment_provider";
			}
		} else{
			$this->table = $wpdb->prefix."woo_shippment_provider";	
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
		));
	}		
	
	public function get_trackship_shipments() {
		
		check_ajax_referer( '_trackship_shipments', 'ajax_nonce' );
		
		$limit = "limit ".sanitize_text_field($_POST['start']).", ".sanitize_text_field($_POST['length'])."";
		
		$tracker_status = isset( $_POST['tracker_status'] ) ? sanitize_text_field($_POST['tracker_status']) : false;
		
		global $wpdb;
			
		if ( $_POST['active_shipment'] == 'active' ) {
			$shipment_status = "AND ts_shipment_status.meta_value NOT LIKE ( '%delivered%')";
			if ( $_POST['shipment_status'] != 'late_shipment' && $_POST['shipment_status'] != 'all_tracking_statuses' ){				
				$shipment_status .= "AND ts_shipment_status.meta_value LIKE ( '%{$_POST['shipment_status']}%')";					
			}			
		} elseif ( $_POST['active_shipment'] == 'delivered' ) {
			$shipment_status = "AND ts_shipment_status.meta_value LIKE ( '%delivered%')";			
		}  
		
		$shipment_type = isset( $_POST['shipment_type'] ) ? sanitize_text_field($_POST['shipment_type']) : false;
		$shipping_provider = isset( $_POST['shipping_provider'] ) ? sanitize_text_field($_POST['shipping_provider']) : false;
		$tracking_code = isset( $_POST['tracking_code'] ) ? sanitize_text_field($_POST['tracking_code']) : false;
		$order_id = isset( $_POST['search_bar'] ) ? sanitize_text_field($_POST['search_bar']) : false;
		
		
		
		$sum = $wpdb->get_var("
			SELECT 				
				COUNT(*)
				
				FROM    {$wpdb->posts} AS posts				
				LEFT JOIN {$wpdb->postmeta} AS shipping_country_meta ON(posts.ID = shipping_country_meta.post_id)
				LEFT JOIN {$wpdb->postmeta} AS shipment_tracking_items ON(posts.ID = shipment_tracking_items.post_id)
				LEFT JOIN {$wpdb->postmeta} AS shipment_status ON(posts.ID = shipment_status.post_id)
				LEFT JOIN {$wpdb->postmeta} AS ts_shipment_status ON(posts.ID = ts_shipment_status.post_id)						
			WHERE 
				posts.post_status IN ('wc-completed','wc-delivered', 'wc-shipped', 'wc-partial-shipped')
				AND posts.post_type IN ( 'shop_order' )	
				AND posts.ID LIKE ( '%{$order_id}%' )				
				AND shipping_country_meta.meta_key IN ( '_shipping_country')
				AND shipment_tracking_items.meta_key IN ( '_wc_shipment_tracking_items')
				AND shipment_tracking_items.meta_key IS NOT NULL
				AND ts_shipment_status.meta_key IN ( 'ts_shipment_status')
				$shipment_status				
				AND shipment_status.meta_key IN ( 'shipment_status')			
		");

				
		$order_query = "
			SELECT 				
				posts.post_status as ordr_status,  				
				shipping_country_meta.meta_value as shipping_country,
				shipment_tracking_items.meta_value as shipment_tracking_items,
				shipment_status.meta_value as shipment_status,
				ts_shipment_status.meta_value as ts_shipment_status,				
				posts.ID AS ID
				
				FROM    {$wpdb->posts} AS posts				
				LEFT JOIN {$wpdb->postmeta} AS shipping_country_meta ON(posts.ID = shipping_country_meta.post_id)
				LEFT JOIN {$wpdb->postmeta} AS shipment_tracking_items ON(posts.ID = shipment_tracking_items.post_id)
				LEFT JOIN {$wpdb->postmeta} AS shipment_status ON(posts.ID = shipment_status.post_id)
				LEFT JOIN {$wpdb->postmeta} AS ts_shipment_status ON(posts.ID = ts_shipment_status.post_id)						
			WHERE 
				posts.post_status IN ('wc-completed','wc-delivered', 'wc-shipped', 'wc-partial-shipped')
				AND posts.post_type IN ( 'shop_order' )	
				AND posts.ID LIKE ( '%{$order_id}%' )				
				AND shipping_country_meta.meta_key IN ( '_shipping_country')
				AND shipment_tracking_items.meta_key IN ( '_wc_shipment_tracking_items')
				AND shipment_tracking_items.meta_key IS NOT NULL
				AND ts_shipment_status.meta_key IN ( 'ts_shipment_status')
				$shipment_status				
				AND shipment_status.meta_key IN ( 'shipment_status')
				
			ORDER BY
				posts.ID DESC
			{$limit}
		";
		
		$orders_data = $wpdb->get_results($order_query,ARRAY_A);

		$wp_date_format = get_option( 'date_format' );
		if($wp_date_format == 'd/m/Y'){
			$date_format = 'd/m'; 
		} else{
			$date_format = 'm/d';
		}
			
		$result = array();
		$i = 0;
		$total_data = 1;
		$late_shipments_email_settings = get_option( 'late_shipments_email_settings' );
		$wcast_late_shipments_days = isset( $late_shipments_email_settings['wcast_late_shipments_days'] ) ? $late_shipments_email_settings['wcast_late_shipments_days'] : '7';	
		
		foreach( $orders_data as $key => $order ){			

			if(is_array(unserialize($order['shipment_tracking_items']))){
				
				$tracking_items = trackship_for_woocommerce()->get_tracking_items($order['ID']);								
				
				$shipment_status = unserialize($order['shipment_status']);			
				
				foreach( (array)$tracking_items as $key => $item ){
					
					$date_shipped = '';
					if(!empty($item['date_shipped'])){
						$date_shipped = date_i18n('Y-m-d', $item['date_shipped'] );
					}
					
					if( !empty($_POST['shipping_date']) ){
						$shipping_date = explode(" - ", $_POST['shipping_date'] );
						
						if (!(($date_shipped >= $shipping_date['0']) && ($date_shipped <= $shipping_date['1']))){
							continue;
						}
					}
					
					if ( empty ( $shipment_status[$key] ) ) {
						continue;	
					}					
					
					$shipment_length = $this->get_shipment_length($shipment_status[$key]);
					
					$result[$i] = new \stdClass();
					$result[$i]->order_id = $order['ID'];
					$result[$i]->order_number = apply_filters( 'woocommerce_order_number', $order['ID'], wc_get_order( $order['ID'] ) );
					$result[$i]->ordr_status = wc_get_order_status_name( $order['ordr_status'] );
					$result[$i]->tracking_number = $item['tracking_number'];
					$result[$i]->tracking_provider = $item['tracking_provider'];
					
					if(isset($shipment_status[$key]['pending_status'])){
						$result[$i]->shipment_status = apply_filters("trackship_status_filter",$shipment_status[$key]['pending_status']);	
					} else{
						$result[$i]->shipment_status = apply_filters("trackship_status_filter",$shipment_status[$key]['status']);
					}
					
					if(isset($shipment_status[$key]['pending_status'])){
						$result[$i]->shipment_status_id = $shipment_status[$key]['pending_status'];
					} else{
						$result[$i]->shipment_status_id = $shipment_status[$key]['status'];
					}
					
					$late_shipment = $wcast_late_shipments_days <= $shipment_length ? '<img class="trackship-tip" title="late shipment" src="' . esc_url( trackship_for_woocommerce()->plugin_dir_url() ) . 'assets/css/icons/invalid-tracking-number.png">' : '';
					$late_shipment = $_POST['active_shipment'] != 'delivered' ? $late_shipment : '' ;
					
					$days = $shipment_length == '0' ? 'Today' : (int)$shipment_length . ' days';
					
					$result[$i]->shipment_length = '<span class="shipment_length">' . $days . $late_shipment . '</span>';
					
					if( is_plugin_active( 'ast-pro/ast-pro.php' ) ){
						$result[$i]->tracking_url = $item['ast_tracking_link'];
					} else{
						$result[$i]->tracking_url = $item['formatted_tracking_link'];
					}	
					
					$result[$i]->formated_tracking_provider = $item['formatted_tracking_provider'];	
					
					$wp_date_format = get_option( 'date_format' );
					if($wp_date_format == 'd/m/Y'){
						$date_format = 'd/m/Y'; 
					} else{
						$date_format = 'm/d/Y';
					}
					
					if(!empty($shipment_status[$key]['est_delivery_date'])){
						$result[$i]->est_delivery_date = date_i18n( $date_format, strtotime($shipment_status[$key]['est_delivery_date']) );
					} else{
						$result[$i]->est_delivery_date = '';
					}															
										
					if(!empty($item['date_shipped'])){
						$result[$i]->et_shipped_at = date_i18n( $date_format, $item['date_shipped'] );						
					} else{
						$result[$i]->et_shipped_at = '';
					}
					
					if(!empty($shipment_status[$key]['status_date'])){
						$result[$i]->ep_updated_at = date_i18n( $date_format, strtotime($shipment_status[$key]['status_date']) );
					} else{
						$result[$i]->ep_updated_at = '';
					}	
					
					$result[$i]->ship_to = WC()->countries->countries[ $order['shipping_country'] ];	
					$result[$i]->action = '';			
					$i++;
					$total_data++;
				}				
			}			
		}
		
		$count_result = array();
		$j = 0;
		foreach( (array)$orders_data as $key => $order ){
			if(is_array(unserialize($order['shipment_tracking_items']))){
				
				$tracking_items = trackship_for_woocommerce()->get_tracking_items($order['ID']);								
				
				$shipment_status = unserialize($order['shipment_status']);			
				
				foreach( (array)$tracking_items as $key => $item ){
					
					
					$date_shipped = '';
					if(!empty($item['date_shipped'])){
						$date_shipped = date_i18n('Y-m-d', $item['date_shipped'] );
					}
					
					if( !empty($_POST['shipping_date']) ){
						$shipping_date = explode(" - ", $_POST['shipping_date'] );
						
						if (!(($date_shipped >= $shipping_date['0']) && ($date_shipped <= $shipping_date['1']))){
							continue;
						}
					}
					
					if ( empty ( $shipment_status[$key] ) ) {
						continue;	
					}					

					$count_result[$j] = new \stdClass();
					$count_result[$j]->order_id = $order['ID'];	
					$j++;					
				}				
			}			
		}
		
		//echo '<pre>';print_r($orders_data);echo '</pre>';exit;	
		
		$obj_result = new \stdclass();
		$obj_result->draw = intval( $_POST['draw'] );
		$obj_result->recordsTotal = intval( $sum );
		$obj_result->recordsFiltered = intval( $sum );
		$obj_result->data = $result;
		$obj_result->last_sql = $order_query;
		$obj_result->is_success = true;
		//$obj_result->z_total = $sum;
		echo json_encode($obj_result);
		exit;
	}

	/*
	* get shiment lenth of tracker
	* return (int)days
	*/
	function get_shipment_length($ep_tracker){
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
		return $days;
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

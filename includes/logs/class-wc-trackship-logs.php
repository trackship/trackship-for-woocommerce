<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
class WC_Trackship_Logs {

	const CRON_HOOK = 'notification_log_clean_cron_hook';

	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		
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
	 * @return WC_Trackship_Logs
	 * 
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
		add_action( 'wp_ajax_get_trackship_logs', array($this, 'get_trackship_logs') );
		
		//SHow popup on click action
		add_action( 'wp_ajax_log_details_popup', array($this, 'log_details_popup') );
		
		//load shipments css js 
		add_action( 'admin_enqueue_scripts', array( $this, 'trackship_log_styles' ), 1);
	}

	/**
	* Load trackship styles.
	*/
	public function trackship_log_styles( $hook ) {
		
		$page = sanitize_text_field( $_GET['page'] ?? '' );

		if ( 'trackship-for-woocommerce' != $page && 'trackship-logs' != $page ) {
			return;
		}
		
		wp_enqueue_style( 'trackship_log_styles', trackship_for_woocommerce()->plugin_dir_url() . '/includes/logs/assets/logs.css', array(), trackship_for_woocommerce()->version );
		wp_enqueue_script( 'trackship_log_script', trackship_for_woocommerce()->plugin_dir_url() . '/includes/logs/assets/logs.js', array( 'jquery' ), trackship_for_woocommerce()->version, true );

	}

	public function get_trackship_logs() {
		
		check_ajax_referer( '_trackship_logs', 'ajax_nonce' );
		
		global $wpdb;
		$p_start = sanitize_text_field( $_POST['start'] ?? '');
		$p_length = sanitize_text_field( $_POST['length'] ?? '');
		$limit = 'limit ' . $p_start . ', ' . $p_length;
		
		$search_bar = sanitize_text_field( $_POST['search_bar'] ?? '');
		$shipment_status = sanitize_text_field( $_POST['shipment_status'] ?? '');
		$log_type = sanitize_text_field( $_POST['log_type'] ?? '');

		$where = [];
		if ( $search_bar ) {
			$where[] = "( `order_id` = '{$search_bar}' OR `order_number` = '{$search_bar}' OR `to` LIKE '%{$search_bar}%' OR `tracking_number` = '{$search_bar}' )";
		}
		$where[] = "( `type` LIKE 'Email' OR `sms_type` LIKE 'shipment_status' )";
		if ( $shipment_status ) {
			$where[] = "`shipment_status` = '{$shipment_status}'";
		}
		
		if ( $log_type ) {
			$where[] = "`type` = '{$log_type}'";
		}
		
		$where_condition = !empty( $where ) ? 'WHERE ' . implode( ' AND ', $where ) : '';

		$sum = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}zorem_email_sms_log $where_condition ");
		$order_query = $wpdb->get_results("
			SELECT * 
				FROM {$wpdb->prefix}zorem_email_sms_log
			$where_condition
			ORDER BY
				`date` DESC
			{$limit}
		");
		
		$result = array();
		$i = 0;
		$current_time = strtotime(current_time( 'Y-m-d H:i:s' ));
		
		foreach ( $order_query as $key => $value ) {

			$notification_time = strtotime( $value->date );
			$time_diff = $current_time - $notification_time;
			
			if ( in_array( $value->status_msg, array( 'Settings disabled' ) ) ) {
				$msg = 'Settings disabled';
			} elseif ( $value->status ) {
				$msg = 'Sent';
			} else {
				$msg = 'Failed';
			}

			$result[$i] = new \stdClass();
			$result[$i]->id = $value->id;
			$result[$i]->order_id = $value->order_id;
			$result[$i]->order_number = $value->order_number;
			$result[$i]->shipment_status = apply_filters('trackship_status_filter', $value->shipment_status );
			$result[$i]->date = [ 'time_diff' => $time_diff, 'time1' => gmdate( 'd/m/Y h:i a', $notification_time ), 'time2' => gmdate( 'M d, Y', $notification_time ) ];
			$result[$i]->to = $value->to;
			$result[$i]->type = 'Email' == $value->type ? 'Email' : 'SMS';
			$result[$i]->status = $msg;
			
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

	public function log_details_popup() {
		check_ajax_referer( '_trackship_logs', 'security' );
		global $wpdb;
		$order_id = sanitize_text_field($_POST['order_id'] ?? '');
		$rowid = sanitize_text_field($_POST['rowid'] ?? '');

		$row = $wpdb->get_row($wpdb->prepare("
			SELECT * FROM {$wpdb->prefix}zorem_email_sms_log
			WHERE `id` = %s AND `order_id` = %s
		", $rowid, $order_id ));
		$row->shipment_status = apply_filters( 'trackship_status_filter', $row->shipment_status );
		wp_send_json($row);
	}
}

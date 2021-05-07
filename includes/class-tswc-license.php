<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSWC_License {
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;
	
	/**
	 * @var string store_url
	*/
	var $item_code = 'tswc';
	var $store_url = 'https://www.zorem.com/';
	var $default_product_id = '87487';
	
	/**
	 * Get the class instance
	 *
	 * @since  1.0
	 * @return TSWC_License
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/**
	 * Initialize the main plugin function
	 * 
	 * @since  1.0
	 * @return  void
	*/
	public function __construct() {		
		$this->init();
	}
	
	/**
	 * Return item code
	 *
	 * @since   1.0
	 * @return  string
	 *
	 */
	public function get_item_code() {
		return $this->item_code;
	}
	
	/**
	 * Set license key
	 *
	 * @since   1.0
	 * @return  Void
	 *
	 */
	public function set_license_key( $license_key ) {
		update_option( $this->get_item_code().'_license_key', $license_key );
	}
	
	/**
	 * Return licence key
	 *
	 * @since   1.0
	 * @return  string
	 *
	 */
	public function get_license_key() {
		return get_option( $this->get_item_code().'_license_key', false);
	}
	
	/**
	 * Set license status
	 *
	 * @since   1.0
	 * @return  Void
	 *
	 */
	public function set_license_status( $status ) {
		update_option( $this->get_item_code().'_license_status', $status );
	}
	
	/**
	 * Return license status
	 *
	 * @since   1.0
	 * @return  Bool
	 *
	 */
	public function get_license_status() {
		return get_option( $this->get_item_code().'_license_status', false);
	}
	
	/**
	 * Create Instance ID
	 *
	 * @since   1.0
	 * @return  string
	 *
	 */
	public function create_instance_id() {
		return $instance_id = md5( $this->get_item_code().time() );
	}
	
	/**
	 * Set Instance ID
	 *
	 * @since   1.0
	 * @return  Void
	 *
	 */
	public function set_instance_id( $instance_id ) {
		update_option( $this->get_item_code().'_instance_id', $instance_id );
	}
	
	/**
	 * Return Instance ID
	 *
	 * @since   1.0
	 * @return  string
	 *
	 */
	public function get_instance_id() {
		return get_option( $this->get_item_code().'_instance_id', false);
	}
	
	/**
	 * Return item code
	 *
	 * @since   1.0
	 * @return  string
	 *
	 */
	public function get_product_id() {
		return $this->default_product_id;
	}
	
	/**
	 * Return cron hook
	 *
	 * @since   1.0
	 * @return  string
	 *
	 */
	public function get_license_cron_hook() {
		return $this->get_item_code().'_license_cron_hook';
	}
	
	/*
	 * init function
	 *
	 * @since  1.0
	*/
	public function init(){
		
		//cron schedule added
		add_filter( 'cron_schedules', array( $this, 'license_cron_schedule') );
		
		//ajax call for license
		add_action( 'wp_ajax_'.$this->get_item_code().'_license_activate', array( $this, 'license_activate') );
		add_action( 'wp_ajax_'.$this->get_item_code().'_license_deactivate', array( $this, 'license_deactivate') );
		
		//cron schedule
		add_action( 'admin_init', array( $this, 'add_cron_schedule') );

		//check license valid
		add_action( $this->get_license_cron_hook(), array( $this, 'check_license_valid' ) );
		
	}
	
	/*
	* add schedule for license check
	*
	* @since  1.0
	*
	* @return  array
	*/
	function license_cron_schedule( $schedules ){
		$schedules[ 'license_cron_events' ] = array(
			'interval' => 86400,
			'display'  => __( 'Every day' ),
		);
		return $schedules;
	}
	
	/*
	* license activate
	* @return  json string
	*/
	function license_activate() {
		$license_key = sanitize_text_field( $_POST['license_key'] );

		if( isset( $license_key ) ) {
			$instance_id = $this->create_instance_id();
			$return = $authorize_data = $this->license_authorize_action( $license_key, 'activate', $instance_id );
			if($authorize_data->success == 'true'){
				$this->set_license_key( $license_key );
				$this->set_instance_id( $instance_id );
				$this->set_license_status( 1 );
				delete_transient( 'zorem_upgrade_'.$this->get_item_code() );
			} else if( $authorize_data->error ) {
				$this->set_license_key( '' );
				$this->set_instance_id( '' );
				$this->set_license_status( 0 );
			}
			header('Content-type: application/json');
			echo json_encode($return, JSON_PRETTY_PRINT);
			die();
		}		
	}
	
	/*
	* license deactivate
	* @return  json string
	*/
	function license_deactivate() {
		
		$license_key = sanitize_text_field( $_POST['license_key'] );
		
		if( isset( $license_key ) ) {
			$return = $this->license_authorize_action( $license_key, 'deactivate' );
			if($return->success == 'true'){
				$this->set_license_key( '' );
				$this->set_instance_id( '' );
				$this->set_license_status( 0 );
				delete_transient( 'zorem_upgrade_'.$this->get_item_code() );
			}
			header('Content-type: application/json');
			echo json_encode($return, JSON_PRETTY_PRINT);
			die();
		}		
	}
	
	/*
	* License authorize with server
	*/
	public function license_authorize_action( $license_key = '', $action = 'validate', $instance_id = false) {
		
		if ( $instance_id == false ) $instance_id = $this->get_instance_id();
		
		$domain = home_url();
		
		$api_params = array(
			'wc-api' => 'wc-am-api',
			'wc_am_action' => $action,
			'instance' => $instance_id,
			'object' => $domain,
			'product_id' => $this->get_product_id(),
			'api_key' => $license_key,
		);
		
		$request = add_query_arg( $api_params, $this->store_url );
		$response = wp_remote_get( $request, array( 'timeout' => 15, 'sslverify' => false ) );
		
		if ( is_wp_error( $response ) )
			return false;
		
		$authorize_data = json_decode( wp_remote_retrieve_body( $response ) );
		if(empty($authorize_data) || $authorize_data === NULL || $authorize_data === false) return false;
		return $authorize_data;
	}
	
	/*
	 * schedule cron event if not scheduled
	 *
	 * @since  1.0
	 *
	 * @return  null
	 */
	function add_cron_schedule(){

		if ( ! wp_next_scheduled( $this->get_license_cron_hook() ) ) {
			wp_schedule_event( time(), 'license_cron_events', $this->get_license_cron_hook() );
		}

	}
	
	/**
	 *
	 * Check license valid
	 *
	 * @since  1.0
	 *
	 * @return  null
	 */
	public function check_license_valid() {
		
		if( $this->get_license_status() ){
			
			$authorize = $this->license_authorize_action( $this->get_license_key(), 'status' );
			
			$license_status = $authorize->status_check;
			if( $license_status == 'inactive'){
				$this->set_license_key( '' );
				$this->set_instance_id( '' );
				$this->set_license_status( 0 );
			}
		}
	}
}

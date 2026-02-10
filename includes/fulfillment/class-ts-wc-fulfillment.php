<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TrackShip WooCommerce Fulfillment Init
 */
class TSWC_Fulfillment_Init {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	public $providers_admin;
	
	/**
	 * Get the class instance
	 *
	 * @return WC_Trackship_Admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {

		require_once 'class-ts-wc-fulfillment-admin.php';
		$this->providers_admin = TrackShip_Fulfillment_Admin::get_instance();
	}
}

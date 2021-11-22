<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class tswc_smswoo_init {
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		$this->init();
	}

	/**
	 * Get the class instance
	 *
	 * @return smswoo_admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/*
	 * init function
	*/
	public function init() {
		
		//admin
		require_once 'class-smswoo-admin.php';
		$this->smswoo_admin = tswc_smswoo_admin::get_instance();
		
		//sms-notification
		require_once 'class-smswoo-sms-notification.php';
		$this->sms_notification = tswc_smswoo_sms_notification::get_instance();
		
		//sms-gateway
		require_once 'class-smswoo-sms-gateway.php';
		
		//include all provider
		if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			return;
		}
		require_once 'services/class-smswoo-nexmo.php';
		require_once 'services/class-smswoo-twilio.php';
		require_once 'services/class-smswoo-clicksend.php';
		require_once 'services/class-smswoo-fast2sms.php';
		require_once 'services/class-smswoo-msg91.php';
		require_once 'services/class-smswoo-smsalert.php';
	}
	
}

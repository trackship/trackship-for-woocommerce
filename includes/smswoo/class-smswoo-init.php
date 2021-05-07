<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class TSWC_SMSWOO_Init {
	
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
		$this->smswoo_admin = TSWC_SMSWoo_Admin::get_instance();
		
		//sms-notification
		require_once 'class-smswoo-sms-notification.php';
		$this->sms_notification = TSWC_SMSWoo_SMS_Notification::get_instance();
		
		//sms-gateway
		require_once 'class-smswoo-sms-gateway.php';
		
		//include all provider
		require_once 'services/class-smswoo-nexmo.php';
		require_once 'services/class-smswoo-twilio.php';
		require_once 'services/class-smswoo-clicksend.php';
		
	}
	
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 *
 * This source file is subject to the GNU GENERAL PUBLIC LICENSE (GPL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://www.gnu.org/licenses/gpl-3.0.txt
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( ! class_exists( 'smswoo_sms_gateway' ) ) {

	/**
	 * Smswoo SMS Gateway abstract class
	 *
	 * @class   smswoo_sms_gateway
	 * @package zorem
	 * @since   1.0.0
	 *
	 */
	abstract class smswoo_sms_gateway {

		protected $_from_number;

		protected $_from_asid;

		protected $_log;

		protected $_logger;

		/**
		 * Constructor
		 *
		 * @since   1.0
		 * @return  void
		 */
		public function __construct() {

			$this->_from_asid   = substr( get_option( 'smswoo_from_asid' ), 0, 11 );
			//$this->_from_number = preg_replace( '[\D]', '', get_option( 'smswoo_sender_phone_number' ) );
			$this->_from_number = get_option( 'smswoo_sender_phone_number' );
			
			$this->_logger      = wc_get_logger();

		}

		/**
		 * Send SMS
		 *
		 * @since   1.0
		 *
		 * @param   $to_phone     string
		 * @param   $message      string
		 * @param   $country_code string
		 *
		 * @return  void
		 * @throws  Exception for WP HTTP API error, no response, HTTP status code is not 201 or if HTTP status code not set
		 */
		public function send( $to_phone, $message, $country_code ) {

			die( 'function smswoo_sms_gateway->send() must be over-ridden in a sub-class.' );

		}
		
		/**
		 * Validate number
		 *
		 * @since   1.0
		 *
		 * @param   $to_phone     string
		 *
		 * @return  void
		 * @throws  Exception for WP HTTP API error, no response, HTTP status code is not 201 or if HTTP status code not set
		 */
		public function validate_number( $to_phone ) {

			die( 'function smswoo_sms_gateway->validate_number() must be over-ridden in a sub-class.' );

		}

		/**
		 * Print send log
		 *
		 * @since   1.0.0
		 * @return  void
		 */
		public function print_log() {

			error_log( print_r( $this->_log, true ) );
			//update_option( 'smswoo_debug_log', print_r( $this->_log, true ) );

		}

		/**
		 * Add log send log
		 *
		 * @since   1.0.0
		 *
		 * @param   $args
		 *
		 * @return  void
		 */
		public function write_log( $args ) {
			
			$smswoo_enable_log = get_option( 'smswoo_enable_log', 1 );
			
			if ( $smswoo_enable_log ) {
				$context = array( 'source' => 'smswoo' );
	
				$log = strtoupper( ( 'test' != $args['type'] ? 'Order #' . $args['order'] . ' - ' : '' ) . $args['type'] . ' MESSAGE' ) . "\r\n";
				$log .= 'Status: ' . ( $args['success'] ? 'SUCCESS' : 'FAILED - ' . $args['status_message'] ) . "\r\n";
				$log .= 'Phone: ' . $args['phone'] . "\r\n";
				$log .= 'Message: ' . $args['message'] . "\r\n";
	
				if ( $args['success'] ) {
					$this->_logger->info( $log, $context );
				} else {
					$this->_logger->error( $log, $context );
				}
			}
		}
	}
}

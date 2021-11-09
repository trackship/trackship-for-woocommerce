<?php
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

if ( ! class_exists( 'SMSWOO_Msg91' ) ) {

	/**
	 * Class   SMSWOO_Msg91
	 * 
	 * @since   1.0
	 *
	 */
	class SMSWOO_Msg91 extends SMSWOO_Sms_Gateway {

		private $_fast2sms_api_key;

		/**
		 * Constructor
		 *
		 * @since   1.0
		 * @return  void
		 */
		public function __construct() {

			$this->_msg91_authkey    = get_option( 'smswoo_msg91_authkey' );

			parent::__construct();

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
			
			$to_phone = str_replace( '+', '', $to_phone );

			if ( '' != $this->_from_asid ) {

				$from = $this->_from_asid;

			} else {

				$from = $this->_from_number;

			}

			$type = empty( apply_filters( 'smswoo_additional_charsets', get_option( 'smswoo_active_charsets', array() ) ) ) ? 'english' : 'unicode';
			
			if ( 'IN' == $country_code ) {
				$country_dial_code = '91';
			} else if ( 'US' == $country_code ) {
				$country_dial_code = '1';
			} else {
				$country_dial_code = '0';
			}
			
			$body = array(
				'sender'	=> $from,
				'route'		=> '4',
				'country'	=> $country_dial_code,
				'sms' => array(
					array (
						'message' => $message,
						'to' => array (
							$to_phone,
						),
					),
				),
				'unicode'	=> 1
			);
			
			$args = array(
				'body'    => wp_json_encode( $body ),
				'headers' => array(
					'authkey' => $this->_msg91_authkey,
					'Content-Type' => 'application/json',
				),
			);
			
			$url = 'https://api.msg91.com/api/v2/sendsms';
			
			$response = wp_safe_remote_post( $url, $args);
			//echo '<pre>';print_r($response);echo '</pre>';exit;
			
			// WP HTTP API error like network timeout, etc
			if ( is_wp_error( $response ) ) {

				throw new Exception( $response->get_error_message() );

			}

			$this->_log[] = $response;

			// Check for proper response / body
			if ( ! isset( $response['response'] ) || ! isset( $response['body'] ) ) {

				throw new Exception( __( 'No answer', 'smswoo' ) );

			}
			
			$result = json_decode( $response['body'], true );

			if ( 'error' == $result['type'] ) {
				/* translators: %s: search for a tag */
				throw new Exception( sprintf( __( 'An error has occurred: %s', 'smswoo' ), $result['message'] ) );

			}

			return;

		}
		
		/**
		 * Send SMS
		 *
		 * @since   1.0
		 *
		 * @param   $to_phone     string
		 *
		 * @return  void
		 * @throws  Exception for WP HTTP API error, no response, HTTP status code is not 201 or if HTTP status code not set
		 */
		public function validate_number( $to_phone ) {

			throw new Exception( sprintf( __( 'An error has occurred: MSG91 is not supported for phone number validation on checkout, Please contact support', 'smswoo' ) ) );

		}

	}
}

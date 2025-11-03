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

if ( ! class_exists( 'WhatsApp_Business' ) ) {

	/**
	 * Class WhatsApp_Businesses
	 * 
	 * @package smswoo
	 * @since 1.0.0
	 *
	 */
	class WhatsApp_Business extends SMSWOO_Sms_Gateway {

		public $new_status;
		public $tracking_number;
		public $_from_number;
		
		/**
		 * Constructor
		 *
		 * @since 1.0
		 * @return void
		 */
		public function __construct() {
			parent::__construct();
		}

		/**
		 * Send SMS
		 *
		 * @since 1.0
		 *
		 * @param $to_phone string
		 * @param $message string
		 * @param $country_code string
		 *
		 * @return void
		 * @throws Exception for WP HTTP API error, no response, HTTP status code is not 201 or if HTTP status code not set
		 */
		public function send( $to_phone, $message, $country_code ) {
			$to_phone = str_replace( '+', '', $to_phone );
			$template_id = get_option('smswoo_trackship_status_' . $this->new_status . '_sms_template_templete_id');
			$template_var = get_option('smswoo_trackship_status_' . $this->new_status . '_sms_template_template_var' );
			$templete_lang = get_option('smswoo_trackship_status_' . $this->new_status . '_sms_template_template_lang' );

			$sms_notification = TSWC_SMSWoo_SMS_Notification::get_instance();
			$replace_var = $sms_notification->replace_message_variables( $template_var, '' );
			$replace_var = $replace_var ? explode( ',', $replace_var ) : [];
			
			$template_var = $template_var ? explode( ',', $template_var ) : [];
			$parameters = [];
			foreach ( $replace_var as $key => $placeholder ) {
				$parameters[] = [
					'type' => 'text',
					'parameter_name' => str_replace( [ '{', '}' ], '', $template_var[$key] ),
					'text' => trim( $placeholder ),
				];
			}

			$components = [];
			$components[] = [
				'type' => 'button',
				'sub_type' => 'url',
				'index' => '0',
				'parameters' => [
					[
						'type' => 'text',
						'text' => $this->tracking_number
					]
				]
			];
			$components[] = [
				'type' => 'body',
				'parameters' => $parameters // Make sure this is an array of body parameters
			];

			$body = [
				'messaging_product' => 'whatsapp',
				'to' => $to_phone,
				'type' => 'template',
				'template' => [
					'name' => $template_id,
					'language' => [
						'code' => $templete_lang
					],
					'components' => $components
				]
			];
			
			$wp_remote_http_args =[
				'body' => json_encode($body),
				'headers' => [
					'Content-Type' => 'application/json',
					'Authorization' => 'Bearer ' . get_option( 'whatsapp_business_authkey' )
				],
			];
			$endpoint = str_replace( '{from}', $this->_from_number, 'https://graph.facebook.com/v19.0/{from}/messages' );
			$response = wp_remote_post( $endpoint, $wp_remote_http_args );

			if ( is_wp_error( $response ) ) {
				throw new Exception( $response->get_error_message() );
			}

			$body = wp_remote_retrieve_body( $response );
			$bodyArray = json_decode( $body, true );

			if ( in_array( wp_remote_retrieve_response_code( $response ), [400, 404, 401] ) ) {
				throw new Exception( $bodyArray['error']['message'] ?? __( 'An error has occurred while sending the sms', 'sms-for-woocommerce' ) );
			}

			return;

		}

	}

}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class tswc_smswoo_sms_notification {
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;
	
	/**
	 * @var sms_gateway SMS gateway
	 * not in use, remove in future
	*/
	private $sms_gateway;
	
	/**
	 * @var string country code
	 */
	private $_country_code;
	
	/**
	 * @var string SMS type
	 */
	private $_sms_type;
	
	/**
	 * @var boolean SMS is for customer
	 */
	public $_customer_sms = false;
	
	/**
	 * @var integer SMS length
	 */
	public $_sms_length = 160;
	
	/**
	 * Initialize the main plugin function
	 * 
	 * @since  1.0
	*/
	public function __construct() {
		$this->init();
	}

	/**
	 * Get the class instance
	 *
	 * @since  1.0
	 * @return WC_Advanced_Shipment_Tracking_Admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self;
		}

		return self::$instance;
	}
	
	/*
	 * init function
	 *
	 * @since  1.0
	*/
	public function init(){
		
		//TrackShip support 
		add_action( 'ast_trigger_ts_status_change', array( $this, 'trigger_sms_on_shipment_status_change' ), 20, 5 );
		
		//AST support for order status sms
		add_filter( 'smswoo_sms_message_replacements', array( $this, 'ast_order_variable_support' ), 10, 2 );
		
	}
		
	/**
	 * Replaces SMS template variables in SMS message
	 *
	 * @since 1.0
	 * @param string $message raw SMS message to replace with variable info
	 * @return string message with variables replaced with indicated values
	 */
	private function replace_message_variables( $message ) {

		$replacements = array(
			'%shop_name%'       => get_bloginfo( 'name' ),
			'%order_id%'        => $this->order->get_order_number(),
			'%order_count%'     => $this->order->get_item_count(),
			'%order_amount%'    => $this->order->get_total(),
			'%order_status%'    => wc_get_order_status_name( $this->order->get_status() ),
			'%billing_name%'    => $this->order->get_formatted_billing_full_name(),
			'%shipping_name%'   => $this->order->get_formatted_shipping_full_name(),
			'%shipping_method%' => $this->order->get_shipping_method(),
			'%billing_first%'   => $this->order->get_billing_first_name( 'edit' ),
			'%billing_last%'    => $this->order->get_billing_last_name( 'edit' ),
			'{shop_name}'       => get_bloginfo( 'name' ),
			'{order_id}'        => $this->order->get_order_number(),
			'{order_count}'     => $this->order->get_item_count(),
			'{order_amount}'    => $this->order->get_total(),
			'{order_status}'    => wc_get_order_status_name( $this->order->get_status() ),
			'{billing_name}'    => $this->order->get_formatted_billing_full_name(),
			'{shipping_name}'   => $this->order->get_formatted_shipping_full_name(),
			'{shipping_method}' => $this->order->get_shipping_method(),
			'{billing_first}'   => $this->order->get_billing_first_name( 'edit' ),
			'{billing_last}'    => $this->order->get_billing_last_name( 'edit' ),
		);

		/**
		 * Filter the notification placeholders and replacements.
		 *
		 * @since 1.0
		 * @param array $replacements {
		 *     The replacements in 'placeholder' => 'replacement' format.
		 *
		 *     @type string %shop_name%       The site name.
		 *     @type int    %order_id%        The order ID.
		 *     @type int    %order_count%     The total number of items ordered.
		 *     @type string %order_amount%    The order total.
		 *     @type string %order_status%    The order status.
		 *     @type string %billing_name%    The billing first and last name.
		 *     @type string %shipping_name%   The shipping first and last name.
		 *     @type string %shipping_method% The shipping method name.
	 	 * }
		 */
		$replacements = apply_filters( 'smswoo_sms_message_replacements', $replacements, $this );

		return str_replace( array_keys( $replacements ), $replacements, $message );
	}
	
	public function ast_order_variable_support( $replacements, $object ){
		if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			return $replacements;
		}
		
		if ( function_exists( 'ast_get_tracking_items' ) ) {
			
			$tracking_items = ast_get_tracking_items( $object->order->get_id() );
			$tracking_number = array_column($tracking_items, 'tracking_number');
			
			$tracking_number = array_column($tracking_items, 'tracking_number');
			$replacements[ '%tracking_number%' ] = implode( ', ', $tracking_number );
			$replacements[ '{tracking_number}' ] = implode( ', ', $tracking_number );
			
			$tracking_provider = array_column($tracking_items, 'formatted_tracking_provider');
			$replacements[ '%tracking_provider%' ] = implode( ', ', $tracking_provider );
			$replacements[ '{tracking_provider}' ] = implode( ', ', $shipping_provider );
			
			$tracking_link = array_column($tracking_items, 'formatted_tracking_link');
			$replacements[ '%tracking_link%' ] = implode( ', ', $tracking_link );
			$replacements[ '{tracking_link}' ] = implode( ', ', $tracking_link );
		}
		
		if( trackship_for_woocommerce()->is_trackship_connected() ){
			
			$shipment_status = get_post_meta( $object->order->get_id(), 'shipment_status', array() );
			
			$status = array_column( (array)$shipment_status, 'status');
			$replacements[ '%shipment_status%' ] = implode( ', ', $status );
			$replacements[ '{shipment_status}' ] = implode( ', ', $status );
			
			$est_delivery_date = array_column( (array)$shipment_status, 'est_delivery_date');
			$replacements[ '%est_delivery_date%' ] = implode( ', ', $est_delivery_date );
			$replacements[ '{est_delivery_date}' ] = implode( ', ', $est_delivery_date );
		}
		
		return $replacements;
		
	}
	
	/**
	 * Send SMS
	 *
	 * @since   1.0.0
	 *
	 * @param   $phone   string
	 * @param   $message string
	 *
	 * @return  boolean
	 */
	private function send_sms( $phone, $message ) {
		
		if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			return ;
		}
		
		$bool = apply_filters( 'smswoo_timeschedule', false, $this, $phone, $message );
		
		//retun if sms is scheduled
		if( $bool )return;
		
		$timestamp   = time();
		
		$sms_provider = $this->get_sms_provider();
		
		$sms_gateway = new $sms_provider();
		$order_id    = ! empty( $this->order ) ? $this->order->get_id() : '';
		
		$this->_sms_length = 160;

		$sms_limit = apply_filters( 'smswoo_sms_limit', $this->_sms_length );

		try {
			
			$customer_country		= ! empty( $this->order ) ? $this->order->get_billing_country() : '';
			$shop_country			= substr( get_option( 'woocommerce_default_country' ), 0, 2 );
			$this->_country_code	= ! empty( $customer_country ) ? $customer_country : $shop_country;
			$this->_calling_code = $this->get_calling_code( $this->_country_code );

			$phone          = $this->format_phone_number( $phone );
			//$message        = mb_substr( $message, 0, $sms_limit );
			$status_message = __( 'Sent', 'trackship-for-woocommerce' );
			$sms_gateway->send( $phone, $message, $this->_country_code );
			$success = true;

		} catch ( Exception $e ) {

			$status_message = $e->getMessage();
			$success        = false;

		}

		$log_args = array(
			'type'           => $this->_sms_type,
			'order'          => $order_id,
			'success'        => $success,
			'status_message' => $status_message,
			'phone'          => $phone,
			'message'        => $message
		);

		$sms_gateway->write_log( $log_args );

		$smswoo_enable_order_note_log = get_option( 'smswoo_enable_order_note_log', 1 );
		if ( $this->_customer_sms && $smswoo_enable_order_note_log ) {

			$order = $this->order;

			$datetime = new DateTime( '@{$timestamp}', new DateTimeZone( 'UTC' ) );
			$datetime->setTimezone( new DateTimeZone( wc_timezone_string() ) );
			$send_date = date_i18n( wc_date_format() . ' ' . wc_time_format(), $timestamp + $datetime->getOffset() );

			ob_start();
			?>
			SMSWoo
			<?php _e( 'Status', 'trackship-for-woocommerce' ); ?>: <?php echo esc_html( $status_message ); ?>
			<br />
			<?php _e( 'Content', 'trackship-for-woocommerce' ); ?>: <?php echo esc_html( $message ); ?>
			<?php

			$this->order_note['text'] = ob_get_clean();
			$this->order_note['id']   = $order->add_order_note( $this->order_note['text'] );

		}

		return $success;

	}
	
	/**
	 * SMS provider
	 *
	 * @since   1.0
	 *
	 *
	 * @return  string provider class
	 */
	function get_sms_provider(){
		if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			return;
		}
		
		if( empty( $this->_sms_gateway )  ) {
			$this->_sms_gateway  = get_option( 'smswoo_sms_provider' );
		}
		
		return $this->_sms_gateway;
	}
	
	/**
	 * Trigger sms on shipment status change
	 * send shipment status notificaion 
	 *
	 * @since   1.0
	 *
	 *
	 */
	function trigger_sms_on_shipment_status_change( $order_id, $old_status, $new_status, $tracking_item, $shipment_status ){
		$this->order = wc_get_order( $order_id );
		
		$logger = wc_get_logger();
		$context = array( 'source' => 'smswoo' );
		//$logger->log( 'debug', 'Order id: '.$this->order->get_id(), $context );
		
		// Check if sending SMS updates for this order's status
		if ( get_option( 'smswoo_trackship_status_' . $new_status . '_sms_template_enabled_customer' ) || get_option( 'smswoo_trackship_status_' . $new_status . '_sms_template_enabled_admin' ) ) {
			
			//$logger->log( 'debug', 'Sms will be sent', $context );
			
			// get message template
			$message = get_option( 'smswoo_trackship_status_' . $new_status . '_sms_template' );

			// use the default template if status-specific one is blank
			if ( empty( $message ) ) {
				$message = get_option( 'smswoo_default_sms_template', 'Hi, Your order no %order_id% on %shop_name% is now {$label}.' );
			}

			// allow modification of message before variable replace (add additional variables, etc)
			$message = apply_filters( 'smswoo_customer_sms_before_variable_replace', $message, $this->order );

			// replace template variables
			$message = $this->replace_message_variables( $message );

			// allow modification of message after variable replace
			$message = apply_filters( 'smswoo_customer_sms_after_variable_replace', $message, $this->order );
			
			//$logger->log( 'debug', 'Message: ' . $message, $context );
			//$logger->log( 'debug', 'Phone: ' . $phone, $context );
			
			//message filter
			$message = apply_filters( 'smswoo_customer_sms_send', $message, $this->order );
			
			// send the SMS to customer!
			if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) && get_option( 'smswoo_trackship_status_' . $new_status . '_sms_template_enabled_customer' ) && $this->user_subscribed_sms( $order_id ) ){
				
				// allow modification of the "to" phone number
				$phone = apply_filters( 'smswoo_sms_customer_phone', $this->order->get_billing_phone( 'edit' ), $this->order );
				$this->_customer_sms = true;
				$this->send_sms( $phone, $message );
			}
			
			// send the SMS to admin!
			if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) && get_option( 'smswoo_trackship_status_' . $new_status . '_sms_template_enabled_admin' ) ){
				
				// allow modification of the "to" phone number
				$phone = apply_filters( 'smswoo_sms_customer_admin', get_option( 'smswoo_admin_phone_number' ), $this->order );
				$this->_customer_sms = false;
				$this->send_sms( $phone, $message );
			}
		}
	}
	
	/**
	 * Check if customer opt-in for SMS
	 *
	 * @since   1.0.0
	 *
	 * @param   $order_id integer
	 *
	 * @return  boolean
	 */
	public function user_subscribed_sms( $order_id ) {

		return true;

	}
	
	/**
	 * Get the calling code of a given country
	 *
	 * @since   1.0.6
	 *
	 * @param   $country_code string
	 *
	 * @return  string
	 */
	private function get_calling_code( $country_code ) {

		$calling_codes = array(
			'AC' => '247',
			'AD' => '376',
			'AE' => '971',
			'AF' => '93',
			'AG' => '1268',
			'AI' => '1264',
			'AL' => '355',
			'AM' => '374',
			'AO' => '244',
			'AQ' => '672',
			'AR' => '54',
			'AS' => '1684',
			'AT' => '43',
			'AU' => '61',
			'AW' => '297',
			'AX' => '358',
			'AZ' => '994',
			'BA' => '387',
			'BB' => '1246',
			'BD' => '880',
			'BE' => '32',
			'BF' => '226',
			'BG' => '359',
			'BH' => '973',
			'BI' => '257',
			'BJ' => '229',
			'BL' => '590',
			'BM' => '1441',
			'BN' => '673',
			'BO' => '591',
			'BQ' => '599',
			'BR' => '55',
			'BS' => '1242',
			'BT' => '975',
			'BW' => '267',
			'BY' => '375',
			'BZ' => '501',
			'CA' => '1',
			'CC' => '61',
			'CD' => '243',
			'CF' => '236',
			'CG' => '242',
			'CH' => '41',
			'CI' => '225',
			'CK' => '682',
			'CL' => '56',
			'CM' => '237',
			'CN' => '86',
			'CO' => '57',
			'CR' => '506',
			'CU' => '53',
			'CV' => '238',
			'CW' => '599',
			'CX' => '61',
			'CY' => '357',
			'CZ' => '420',
			'DE' => '49',
			'DJ' => '253',
			'DK' => '45',
			'DM' => '1767',
			'DO' => '1809',
			'DZ' => '213',
			'EC' => '593',
			'EE' => '372',
			'EG' => '20',
			'EH' => '212',
			'ER' => '291',
			'ES' => '34',
			'ET' => '251',
			'EU' => '388',
			'FI' => '358',
			'FJ' => '679',
			'FK' => '500',
			'FM' => '691',
			'FO' => '298',
			'FR' => '33',
			'GA' => '241',
			'GB' => '44',
			'GD' => '1473',
			'GE' => '995',
			'GF' => '594',
			'GG' => '44',
			'GH' => '233',
			'GI' => '350',
			'GL' => '299',
			'GM' => '220',
			'GN' => '224',
			'GP' => '590',
			'GQ' => '240',
			'GR' => '30',
			'GT' => '502',
			'GU' => '1671',
			'GW' => '245',
			'GY' => '592',
			'HK' => '852',
			'HN' => '504',
			'HR' => '385',
			'HT' => '509',
			'HU' => '36',
			'ID' => '62',
			'IE' => '353',
			'IL' => '972',
			'IM' => '44',
			'IN' => '91',
			'IO' => '246',
			'IQ' => '964',
			'IR' => '98',
			'IS' => '354',
			'IT' => '39',
			'JE' => '44',
			'JM' => '1876',
			'JO' => '962',
			'JP' => '81',
			'KE' => '254',
			'KG' => '996',
			'KH' => '855',
			'KI' => '686',
			'KM' => '269',
			'KN' => '1869',
			'KP' => '850',
			'KR' => '82',
			'KW' => '965',
			'KY' => '1345',
			'KZ' => '7',
			'LA' => '856',
			'LB' => '961',
			'LC' => '1758',
			'LI' => '423',
			'LK' => '94',
			'LR' => '231',
			'LS' => '266',
			'LT' => '370',
			'LU' => '352',
			'LV' => '371',
			'LY' => '218',
			'MA' => '212',
			'MC' => '377',
			'MD' => '373',
			'ME' => '382',
			'MF' => '590',
			'MG' => '261',
			'MH' => '692',
			'MK' => '389',
			'ML' => '223',
			'MM' => '95',
			'MN' => '976',
			'MO' => '853',
			'MP' => '1670',
			'MQ' => '596',
			'MR' => '222',
			'MS' => '1664',
			'MT' => '356',
			'MU' => '230',
			'MV' => '960',
			'MW' => '265',
			'MX' => '52',
			'MY' => '60',
			'MZ' => '258',
			'NA' => '264',
			'NC' => '687',
			'NE' => '227',
			'NF' => '672',
			'NG' => '234',
			'NI' => '505',
			'NL' => '31',
			'NO' => '47',
			'NP' => '977',
			'NR' => '674',
			'NU' => '683',
			'NZ' => '64',
			'OM' => '968',
			'PA' => '507',
			'PE' => '51',
			'PF' => '689',
			'PG' => '675',
			'PH' => '63',
			'PK' => '92',
			'PL' => '48',
			'PM' => '508',
			'PR' => '1787',
			'PS' => '970',
			'PT' => '351',
			'PW' => '680',
			'PY' => '595',
			'QA' => '974',
			'QN' => '374',
			'QS' => '252',
			'QY' => '90',
			'RE' => '262',
			'RO' => '40',
			'RS' => '381',
			'RU' => '7',
			'RW' => '250',
			'SA' => '966',
			'SB' => '677',
			'SC' => '248',
			'SD' => '249',
			'SE' => '46',
			'SG' => '65',
			'SH' => '290',
			'SI' => '386',
			'SJ' => '47',
			'SK' => '421',
			'SL' => '232',
			'SM' => '378',
			'SN' => '221',
			'SO' => '252',
			'SR' => '597',
			'SS' => '211',
			'ST' => '239',
			'SV' => '503',
			'SX' => '1721',
			'SY' => '963',
			'SZ' => '268',
			'TA' => '290',
			'TC' => '1649',
			'TD' => '235',
			'TG' => '228',
			'TH' => '66',
			'TJ' => '992',
			'TK' => '690',
			'TL' => '670',
			'TM' => '993',
			'TN' => '216',
			'TO' => '676',
			'TR' => '90',
			'TT' => '1868',
			'TV' => '688',
			'TW' => '886',
			'TZ' => '255',
			'UA' => '380',
			'UG' => '256',
			'UK' => '44',
			'US' => '1',
			'UY' => '598',
			'UZ' => '998',
			'VA' => '39',
			'VC' => '1784',
			'VE' => '58',
			'VG' => '1284',
			'VI' => '1340',
			'VN' => '84',
			'VU' => '678',
			'WF' => '681',
			'WS' => '685',
			'XC' => '991',
			'XD' => '888',
			'XG' => '881',
			'XL' => '883',
			'XN' => '857',
			'XP' => '878',
			'XR' => '979',
			'XS' => '808',
			'XT' => '800',
			'XV' => '882',
			'YE' => '967',
			'YT' => '262',
			'ZA' => '27',
			'ZM' => '260',
			'ZW' => '263',
		);

		return ( isset( $calling_codes[ $country_code ] ) ) ? $calling_codes[ $country_code ] : '';

	}
	
	/**
	 * Format a number to E.164 format
	 *
	 * @since   1.0.0
	 *
	 * @param   $phone string
	 *
	 * @return  string
	 */
	private function format_phone_number( $phone ) {

		if ( $this->_calling_code == '' ) {
			return apply_filters( 'smswoo_format_phone_number', $phone, $this->_calling_code );
		}

		// Check if number do not starts with '+'
		if ( '+' != substr( $phone, 0, 1 ) ) {

			// remove leading zero
			$phone = preg_replace( '/^0/', '', $phone );

			$phone = $this->country_special_cases( $phone );

			// Check if number has country code
			if ( $this->_calling_code != substr( $phone, 0, strlen( $this->_calling_code ) ) ) {

				$phone = $this->_calling_code . $phone;
			}

		}

		// remove any non-number characters
		$phone = preg_replace( '[\D]', '', $phone );

		// Check if the number starts with the expected country code, remove any zero which immediately follows the country code.
		if ( $this->_calling_code == substr( $phone, 0, strlen( $this->_calling_code ) ) ) {
			$phone = preg_replace( '/^{$this->_calling_code}(\s*)?0/', $this->_calling_code, $phone );
		}

		return apply_filters( 'smswoo_format_phone_number', $phone, $this->_calling_code );

	}
	
	/**
	 * Check if some country has special cases
	 *
	 * @since   1.0.6
	 *
	 * @param   $phone string
	 *
	 * @return  string
	 */
	private function country_special_cases( $phone ) {

		switch ( $this->_country_code ) {

			case 'IT':

				/**
				 * in Italy, the telephone prefixes released by "H3G" operator have the first two digits equal to the Italian international prefix.
				 * If the customer has entered the number without the country code, the sending of SMS can fail because of this similarity
				 */
				if ( strlen( $phone ) <= apply_filters( 'smswoo_italian_numbers_length', 10 ) ) {

					$mobile_prefixes = apply_filters( 'smswoo_italian_prefixes', array( '390', '391', '392', '393', '397' ) );

					if ( in_array( substr( $phone, 0, 3 ), $mobile_prefixes ) ) {

						$phone = $this->_calling_code . $phone;

					}

				}

				break;

			case 'NO':

				/**
				 * in Norway, the newer telephone prefixes have the first two digits equal to the Norwegian international prefix.
				 * If the customer has entered the number without the country code, the sending of SMS can fail because of this similarity
				 */
				if ( strlen( $phone ) <= apply_filters( 'smswoo_norwegian_numbers_length', 8 ) ) {

					$mobile_prefixes = apply_filters( 'smswoo_norwegian_prefixes', array( '47' ) );

					if ( in_array( substr( $phone, 0, 2 ), $mobile_prefixes ) ) {

						$phone = $this->_calling_code . $phone;

					}

				}

				break;

		}

		return $phone;

	}
}

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_TrackShip_Late_Shipments {
	
	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;

	const CRON_HOOK = 'trackship_late_shipments_hook';
	
	/**
	 * Get the class instance
	 *
	 * @since 1.0
	 * @return WC_TrackShip_Late_Shipments
	*/
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/**
	 * Initialize the main plugin function
	 * 
	 * @since 1.0
	 * @return void
	*/
	public function __construct() {
		$this->init();
	}
	
	/*
	 * init function
	 *
	 * @since 1.0
	*/
	public function init() {

		$late_shipments_email_enable = get_trackship_settings( 'late_shipments_email_enable' );
		
		if ( !$late_shipments_email_enable || ! is_trackship_connected() ) {
			return;
		}
		
		//cron schedule added
		// add_action( 'wp_ajax_send_late_shipments_email', array( $this, 'send_late_shipments_email') );
		
		//Send Late Shipments Email
		add_action( self::CRON_HOOK, array( $this, 'send_late_shipments_email' ) );

	}
	
	/**
	 * Remove the Cron
	 *
	 * @since 1.0.0
	 */
	public function remove_cron() {
		wp_clear_scheduled_hook( 'ast_late_shipments_cron_hook' );
		wp_clear_scheduled_hook( 'trackship_late_shipments_hook' );
	}

	/**
	 * Setup the Cron
	 * 
	 * @since 1.0.0
	 */
	public function setup_cron() {

		$daily_digest_time = get_trackship_settings('late_shipments_digest_time');
		
		if ( !get_trackship_settings( 'late_shipments_email_enable' ) || wp_next_scheduled( self::CRON_HOOK ) ) {
			return;
		}

		if ( $daily_digest_time ) {

			// Create a Date Time object when the cron should run for the first time
			$first_cron = new DateTime( gmdate( 'Y-m-d' ) . ' ' . $daily_digest_time . ':00', new DateTimeZone( wc_timezone_string() ) );
			$first_cron->setTimeZone(new DateTimeZone('GMT'));
			$time = new DateTime( gmdate( 'Y-m-d H:i:s' ), new DateTimeZone( wc_timezone_string() ) );
			
			if ( $time->getTimestamp() > $first_cron->getTimestamp() ) {
				$first_cron->modify( '+1 day' );
			}

			wp_schedule_event( $first_cron->format( 'U' ) + $first_cron->getOffset(), 'daily', self::CRON_HOOK );
		
		} else {
			if (!wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time() , 'daily', self::CRON_HOOK );
			}
		}
	}
	
	/**
	 *
	 * Send Late Shipments Email
	 *
	 */
	public function send_late_shipments_email() {
		
		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ) {
			$logger = wc_get_logger();
			$context = array( 'source' => 'trackship_late_shipments_email' );
			$logger->info( 'Late Shipments email not sent. Upgrade your plan', $context );
			return;
		}
		global $wpdb;
		$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
		
		$late_ship_day = get_trackship_settings( 'late_shipments_days', 7);
		$day = $late_ship_day - 1;
		
		//total late shipment count
		$count = $wpdb->get_var($wpdb->prepare("
			SELECT
				COUNT(*)
				FROM {$wpdb->prefix}trackship_shipment
			WHERE 
				shipment_status NOT IN ('delivered', 'return_to_sender', 'label_cancelled')
				AND late_shipment_email = %d
				AND shipping_length > %d
				AND shipping_date > NOW() - INTERVAL 60 DAY
		", 0, $day ));

		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) || 0 == $count ) {
			return;
		}

		// late shipment query in trackship_shipment table
		$total_order = $wpdb->get_results($wpdb->prepare("
			SELECT *
				FROM {$wpdb->prefix}trackship_shipment
			WHERE 
				shipment_status NOT IN ('delivered', 'return_to_sender', 'label_cancelled')
				AND late_shipment_email = %d
				AND shipping_length > %d
				AND shipping_date > NOW() - INTERVAL 60 DAY
			LIMIT 10
		", 0, $day ));

		//Send email for late shipment
		$email_send = $this->late_shippment_email_trigger( $total_order, $count );

		foreach ( $total_order as $key => $orders ) {
			if ( in_array( 1, $email_send ) ) {
				$where = array(
					'order_id'			=> $orders->order_id,
					'tracking_number'	=> $orders->tracking_number,
				);
				$wpdb->update( $woo_trackship_shipment, array( 'late_shipment_email' => 1 ), $where );
			}
		}
		exit;
	}

	/**
	 * Code for send late shipment status email
	 */
	public function late_shippment_email_trigger( $orders, $count ) {
		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ) {
			return;
		}

		//Email Subject
		$subject = __( 'Late shipment', 'trackship-for-woocommerce' );
		// Email heading
		/* translators: %s: search for a count */
		$email_heading = sprintf( __( 'We detected %d late shipments:', 'trackship-for-woocommerce' ) , $count );

		//Email Content
		$email_content = __( 'The following shipments are late:', 'trackship-for-woocommerce' );
		$email_content .= wc_get_template_html( 'emails/late-shipment-email.php', array(
			'orders' => $orders,
		), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );

		$mailer = WC()->mailer();
		// create a new email
		$email = new WC_Email();

		// wrap the content with the email template and then add styles
		$email_content = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $email_content ) ) );

		$email_class = new WC_Email();
		$email_class->id = 'late_shipment';

		$email_to = get_trackship_settings( 'late_shipments_email_to', '{admin_email}' );
		$email_to = explode( ',', $email_to );
		$email_send = array();
		foreach ( $email_to as $email_addr ) {
			if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ) {
				return;
			}
			//string replace for '{admin_email}'
			$recipient = str_replace( '{admin_email}', get_option('admin_email'), $email_addr );
			//Send Email
			$response = $email_class->send( $recipient, $subject, $email_content, $email_class->get_headers(), [] );

			$email_send[] = $response;
			$arg = array(
				'order_id'			=> '',
				'order_number'		=> '',
				'user_id'			=> '',
				'tracking_number'	=> '',
				'date'				=> current_time( 'Y-m-d H:i:s' ),
				'to'				=> $recipient,
				'shipment_status'	=> 'Late shipment',
				'status'			=> $response,
				'status_msg'		=> $response ? 'Sent' : 'Not Sent',
				'type'				=> 'Email',
			);
			trackship_for_woocommerce()->ts_actions->update_notification_table( $arg );
		}
		return $email_send;
	}
}

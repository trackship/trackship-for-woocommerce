<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_TrackShip_On_Hold_Shipments {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;

	const CRON_HOOK = 'trackship_on_hold_shipments_hook';

	/**
	 * Get the class instance
	 *
	 * @since 1.0
	 * @return WC_TrackShip_On_Hold_Shipments
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

		$ts_actions = new WC_Trackship_Actions();

		$on_hold_admin_email_enable = get_trackship_settings( 'on_hold_admin_email_enable' );

		if ( !$on_hold_admin_email_enable || ! is_trackship_connected() ) {
			return;
		}

		//cron schedule added
		// add_action( 'wp_ajax_send_on_hold_shipments_email', array( $this, 'send_on_hold_shipments_email') );

		//Send on_hold Shipments Email
		add_action( self::CRON_HOOK, array( $this, 'send_on_hold_shipments_email' ) );

	}

	/**
	 * Remove the Cron
	 *
	 * @since 1.0.0
	 */
	public function remove_cron() {
		wp_clear_scheduled_hook( 'trackship_on_hold_shipments_hook' );
	}

	/**
	 * Setup the Cron
	 *
	 * @since 1.0.0
	 */
	public function setup_cron() {

		$daily_digest_time = get_trackship_settings('on_hold_shipments_digest_time');

		if ( !get_trackship_settings( 'on_hold_admin_email_enable' ) || wp_next_scheduled( self::CRON_HOOK ) ) {
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
	 * Send on_hold Shipments Email
	 *
	 */
	public function send_on_hold_shipments_email() {

		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ) {
			$logger = wc_get_logger();
			$context = array( 'source' => 'trackship' );
			$logger->info( 'On Hold Shipments email not sent. Upgrade your plan', $context );
			return;
		}
		global $wpdb;

		//total on_hold shipment count
		$count = $wpdb->get_var($wpdb->prepare("
			SELECT
				COUNT(*)
				FROM {$wpdb->prefix}trackship_shipment
			WHERE
				shipment_status = 'on_hold'
				AND on_hold_email = %d
		", 0 ));

		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) || 0 == $count ) {
			return;
		}

		// on_hold shipment query in trackship_shipment table
		$total_order = $wpdb->get_results($wpdb->prepare("
			SELECT *
				FROM {$wpdb->prefix}trackship_shipment
			WHERE
				shipment_status = 'on_hold'
				AND on_hold_email = %d
			LIMIT 10
		", 0 ));

		//Send email for on_hold shipment
		$email_send = $this->on_hold_shippment_email_trigger( $total_order, $count );

		foreach ( $total_order as $key => $orders ) {
			if ( in_array( 1, $email_send ) ) {
				$where = array(
					'order_id'			=> $orders->order_id,
					'tracking_number'	=> $orders->tracking_number,
				);
				$wpdb->update( $wpdb->prefix . 'trackship_shipment', array( 'on_hold_email' => 1 ), $where );
			}
		}
		exit;
	}

	/**
	 * Code for send on_hold shipment status email
	 */
	public function on_hold_shippment_email_trigger( $orders, $count ) {
		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ) {
			return;
		}
		$logger = wc_get_logger();
		$sent_to_admin = false;
		$plain_text = false;

		//Email Subject
		$subject = __( 'On Hold shipment', 'trackship-for-woocommerce' );
		// Email heading
		/* translators: %s: search for a count */
		$email_heading = sprintf( __( 'We detected %d On Hold shipments:', 'trackship-for-woocommerce' ) , $count );

		//Email Content
		$email_content = __( 'The following shipments are On Hold:', 'trackship-for-woocommerce' );
		$email_content .= wc_get_template_html( 'emails/on-hold-shipment-email.php', array(
			'orders' => $orders,
		), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );

		$mailer = WC()->mailer();
		// create a new email
		$email = new WC_Email();

		// wrap the content with the email template and then add styles
		$email_content = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $email_content ) ) );

		$email_class = new WC_Email();
		$email_class->id = 'on_hold_shipment';

		$email_to = get_trackship_settings( 'on_hold_shipments_email_to', '{admin_email}' );
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
				'shipment_status'	=> 'On Hold shipment',
				'status'			=> $response,
				'status_msg'		=> $response ? 'Sent' : 'Not Sent',
				'type'				=> 'Email',
			);
			trackship_for_woocommerce()->ts_actions->update_notification_table( $arg );
		}
		return $email_send;
	}
}

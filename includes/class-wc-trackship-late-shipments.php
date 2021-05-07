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

	const CRON_HOOK = 'ast_late_shipments_cron_hook';	
	
	/**
	 * Get the class instance
	 *
	 * @since  1.0
	 * @return smswoo_license
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
	 * @since  1.0
	 * @return  void
	*/
	public function __construct() {		
		$this->init();
	}		
	
	/*
	 * init function
	 *
	 * @since  1.0
	*/
	public function init() {
		$ts_actions = new WC_Trackship_Actions();
		
		$wcast_enable_late_shipments_email = $ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_enable_late_shipments_admin_email', '');
		
		$wc_ast_api_key = get_option('wc_ast_api_key');
		if ( !$wcast_enable_late_shipments_email || !$wc_ast_api_key ) {
			return;
		}
		
		//cron schedule added
		add_filter( 'cron_schedules', array( $this, 'late_shipments_cron_schedule') );				
		add_action( 'wp_ajax_send_late_shipments_email', array( $this, 'send_late_shipments_email') );
		add_action( 'wp_ajax_nopriv_send_late_shipments_email', array( $this, 'send_late_shipments_email') );
		
		//Send Late Shipments Email
		add_action( self::CRON_HOOK, array( $this, 'send_late_shipments_email' ) );				
		
		if (!wp_next_scheduled( self::CRON_HOOK ) ) {
			wp_schedule_event( time() , 'ast_late_shipments_cron_events', self::CRON_HOOK );			
		}
		
		//add_action( 'wp_ajax_test123', array( $this, 'test123_cb') );
	}
	
	/**
	 * Remove the Cron
	 *
	 * @since  1.0.0
	 */
	public function remove_cron() {
		wp_clear_scheduled_hook( self::CRON_HOOK );
	}

	/**
	 * Setup the Cron
	 * 
	 * @since  1.0.0
	 */
	public function setup_cron() {

		$late_shipments_email_settings = get_option('late_shipments_email_settings');
		
		$wcast_late_shipments_trigger_alert = isset( $late_shipments_email_settings['wcast_late_shipments_trigger_alert'] ) ? $late_shipments_email_settings['wcast_late_shipments_trigger_alert'] : '';						
		
		if ( 'daily_digest_on' == $wcast_late_shipments_trigger_alert ) {
			
			$wcast_late_shipments_daily_digest_time = isset( $late_shipments_email_settings['wcast_late_shipments_daily_digest_time'] ) ? $late_shipments_email_settings['wcast_late_shipments_daily_digest_time'] : '';
			
			// Create a Date Time object when the cron should run for the first time
			$first_cron = new DateTime( gmdate( 'Y-m-d' ) . ' ' . $wcast_late_shipments_daily_digest_time . ':00', new DateTimeZone( wc_timezone_string() ) );	
			
			
			$first_cron->setTimeZone(new DateTimeZone('GMT'));
			
			$time = new DateTime( gmdate( 'Y-m-d H:i:s' ), new DateTimeZone( wc_timezone_string() ) );
			
			if ( $time->getTimestamp() >  $first_cron->getTimestamp() ) {
				$first_cron->modify( '+1 day' );
			}

			wp_schedule_event( $first_cron->format( 'U' ) + $first_cron->getOffset(), 'daily', self::CRON_HOOK );					
		
		} else {
			if (!wp_next_scheduled( self::CRON_HOOK ) ) {
				wp_schedule_event( time() , 'ast_late_shipments_cron_events', self::CRON_HOOK );			
			}
		}
	}
	
	/*
	* add schedule for late shipments check
	*
	* @since  1.0
	*
	* @return  array
	*/
	public function late_shipments_cron_schedule( $schedules ) {
		
		$schedules[ 'ast_late_shipments_cron_events' ] = array(
			'interval' => 86400,
			'display'  => __( 'Every day' ),
		);
		return $schedules;
	}		
	
	/**
	 *
	 * Send Late Shipments Email
	 *
	 */
	public function send_late_shipments_email() {	
		
		$orders = new WP_Query(
			array(
				'post_type'			=> 'shop_order',
				'post_status'		=> array_keys( wc_get_order_statuses() ),
				'posts_per_page'	=> -1,
				'meta_key'			=> 'shipment_status',
				'meta_compare'		=> 'EXISTS', // The comparison argument
				// Using the date_query to filter posts from last 90 days
				'date_query'		=> array(
					array(
						'after' => '-90 days'
					)
				)
			)
		);
		
		$wcast_late_shipments_settings = new TSWC_Late_Shipments_Customizer_Email();
		
		$wcast_late_shipments_days = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', $wcast_late_shipments_settings->defaults['wcast_late_shipments_days'] );
		
		foreach ( $orders->posts as $order ) {	
			$order_object = new WC_Order( $order->ID );	
			$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $order_object->get_id() );
			
			foreach ( $shipment_status as $key => $tracker ) {
				$tracking_items = get_post_meta( $order_object->get_id(), '_wc_shipment_tracking_items', true );
							
				$shipment_length = $this->get_shipment_length($tracker);	
				
				if ( 'available_for_pickup' != $tracker['status'] && 'delivered' != $tracker['status'] ) {
					if ( $shipment_length >= $wcast_late_shipments_days) {		
						$late_shipments = get_post_meta( $order_object->get_id(), 'late_shipments_email', true );
						if ( isset( $late_shipments[$tracking_items[$key]['tracking_number']] ) ) {
							if ( 1 != $late_shipments[$tracking_items[$key]['tracking_number']]['email_send'] ) {
								$email_send = $this->late_shippment_email_trigger($order_object->get_id(), $order_object, $tracker, $tracking_items[$key]['tracking_number']);
								if ( $email_send ) {							
									$late_shipments_array[$tracking_items[$key]['tracking_number']] = array( 'email_send'    => '1' );
									update_post_meta( $order_object->get_id(), 'late_shipments_email', $late_shipments_array );	
								}	
							}
						} else {
							$email_send = $this->late_shippment_email_trigger($order_object->get_id(), $order_object, $tracker, $tracking_items[$key]['tracking_number']);
							if ( $email_send ) {							
								$late_shipments_array[$tracking_items[$key]['tracking_number']] = array( 'email_send'    => '1' );
								update_post_meta( $order_object->get_id(), 'late_shipments_email', $late_shipments_array );	
							}							
						}									
					}	
				}								
			}							
		}
		exit;
	}

	/*
	* get shiment lenth of tracker
	* return (int)days
	*/
	public function get_shipment_length( $ep_tracker ) {
		if ( empty( $ep_tracker['tracking_events'] ) ) {
			return 0;
		}
		if ( count( $ep_tracker['tracking_events'] ) == 0 ) {
			return 0;
		}
		
		$first = reset($ep_tracker['tracking_events']);
		$first_date = $first->datetime;
		$last = ( isset( $ep_tracker['tracking_destination_events'] ) && count( $ep_tracker['tracking_destination_events'] ) > 0 ) ? end($ep_tracker['tracking_destination_events']) : end($ep_tracker['tracking_events']);
		$last_date = $last->datetime;
		
		$status = $ep_tracker['status'];
		if ( 'delivered' != $status ) {
			$last_date = gmdate('Y-m-d H:i:s');
		}		
		
		$days = $this->get_num_of_days( $first_date, $last_date );		
		return $days;
	}
	
	/*
	* Get number of days from start date and end date
	*/
	public function get_num_of_days( $first_date, $last_date ) {
		$date1 = strtotime($first_date);
		$date2 = strtotime($last_date);
		$diff = abs($date2 - $date1);
		return gmdate( 'd', $diff );
	}

	/**
	 * Code for send shipment status email
	 */
	public function late_shippment_email_trigger( $order_id, $order, $tracker, $tracking_number ) {
		
		add_action( 'before_tracking_widget_email', array( $this, 'late_shipment_pre_text' ), 10, 2 );
		
		$logger = wc_get_logger();
		$sent_to_admin = false;
		$plain_text = false;
		$wcast_late_shipments_settings = new TSWC_Late_Shipments_Customizer_Email();
		/* translators: %s: search order number */
		$subject = sprintf( __( 'Late shipment for order #%d', 'trackship-for-woocommerce' ), $order->get_order_number() );
		$email_heading = __( 'Late shipments notification', 'trackship-for-woocommerce' );
		
		/* translators: %s: search for days */
		$email_content = sprintf( esc_html__( 'This following order were shipped %s days ago and are not delivered.', 'trackship-for-woocommerce' ), $this->get_shipment_length( $tracker ) );

		$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id, true );
		$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $order_id );
			
		foreach ( $tracking_items as $key => $item ) {
			if ( $item['tracking_number'] != $tracking_number ) {
				unset( $tracking_items[ $key ] );
			}
		}
		
		$email_content .= wc_get_template_html( 'emails/tracking-info.php', array( 
			'tracking_items' => $tracking_items,
			'shipment_status' => $shipment_status,
			'order_id' => $order_id,
			'show_shipment_status' => true,
			'new_status' => 'in_transit',
		), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
						
		$mailer = WC()->mailer();
		// create a new email
		$email = new WC_Email();
	
		// wrap the content with the email template and then add styles
		$email_content = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $email_content ) ) );
		//echo $email_content;exit;
		
		add_filter( 'wp_mail_from', array( wc_trackship_email_manager(), 'get_from_address' ) );
		add_filter( 'wp_mail_from_name', array( wc_trackship_email_manager(), 'get_from_name' ) );
		
		$email_to = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_email_to', $wcast_late_shipments_settings->defaults['wcast_late_shipments_email_to']);		
		$email_to = explode( ',', $email_to );
				
		foreach ( $email_to as $email_addr ) {
			$recipient = wc_trackship_email_manager()->email_to( $email_addr, $order, $order_id );
			$email_send = wp_mail( $recipient, $subject, $email_content, $email->get_headers() );
			$context = array( 'source' => 'trackship_late_shipments_email_log' );
			$logger->error( 'Order_Id: ' . $order_id . ' Late Shipments' . $email_send, $context );
			return $email_send;
		}		
	}
	
	/**
	 * Code for format email content 
	*/
	public function email_content( $email_content, $order_id, $order, $tracker ) {	
		$shipment_length = $this->get_shipment_length($tracker);		
		$shipment_status = apply_filters( 'trackship_status_filter', $tracker['status']);
		$est_delivery_date = $tracker['est_delivery_date'];
		$email_content = str_replace( '{shipment_length}', $shipment_length, $email_content );	
		$email_content = str_replace( '{shipment_status}', $shipment_status, $email_content );
		$email_content = str_replace( '{est_delivery_date}', $est_delivery_date, $email_content );		
		return $email_content;
	}
	
	public function late_shipment_pre_text( $tracking_item, $order_id ) {
		?>
		<?php /* translators: %s: search order id and date */ ?>
		<p><?php echo sprintf( esc_html__( 'Order #%1$d shipped on %2$s:', 'trackship-for-woocommerce' ), esc_html( $order_id ), esc_html( date_i18n( get_option('date_format'), $tracking_item['date_shipped'] ) ) ); ?></p>
		<?php
	}
}

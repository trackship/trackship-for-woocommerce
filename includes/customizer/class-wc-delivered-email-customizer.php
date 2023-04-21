<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Customizer Setup and Custom Controls
 *
 */

/**
 * Adds the individual sections, settings, and controls to the theme customizer
 */
class TSWC_Delivered_Customizer_Email {
	// Get our default values	
	public function __construct() {
		// Only proceed if this is own request.
		if ( ! self::is_own_preview_request() ) {
			return;
		}
		// Get our Customizer defaults
		$this->defaults = trackship_admin_customizer()->wcast_shipment_settings_defaults( 'delivered_status' );
		
		add_action( 'parse_request', array( $this, 'set_up_preview' ) );
	}
	
	/**
	 * Get blog name formatted for emails.
	 *
	 * @return string
	 */
	public function get_blogname() {
		return wp_specialchars_decode( get_option( 'blogname' ), ENT_QUOTES );
	}	
	
	/**
	 * Checks to see if we are opening our custom customizer preview
	 *
	 * @return bool
	 */
	public static function is_own_preview_request() {
		return isset( $_REQUEST['shipment-email-customizer-preview'] ) && 'delivered' === $_REQUEST['status'];
	}
	
	/**
	 * Set up preview
	 *
	 * @return void
	 */
	public function set_up_preview() {
		
		// Make sure this is own preview request.
		if ( ! self::is_own_preview_request() ) {
			return;
		}
		include trackship_for_woocommerce()->get_plugin_path() . '/includes/customizer/preview/delivered_preview.php';		
		exit;			
	}
	
	/**
	 * Code for preview of delivered email
	*/
	public function preview_delivered_email() {
		$preview_id = get_option( 'email_preview', 'mockup' );
		$order = trackship_admin_customizer()->get_wc_order_for_preview( $preview_id );	
		
		$email_heading = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_email_heading', $this->defaults['wcast_delivered_status_email_heading']);		
		$email_heading = str_replace( '{site_title}', $this->get_blogname(), $email_heading );
		$email_heading =  str_replace( '{order_number}', $order->get_order_number(), $email_heading );
		
		$email_content = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_email_content', $this->defaults['wcast_delivered_status_email_content']);
		$email_content = html_entity_decode( $email_content );
		
		$wcast_show_tracking_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_tracking_details', $this->defaults['wcast_delivered_status_show_tracking_details']);
		
		$wcast_show_order_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_order_details', $this->defaults['wcast_delivered_status_show_order_details']);

		$wcast_show_product_image = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_product_image', $this->defaults['wcast_delivered_status_show_product_image']);
		
		$wcast_show_shipping_address = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_show_shipping_address', $this->defaults['wcast_delivered_status_show_shipping_address']);
		
		$sent_to_admin = false;
		$plain_text = false;
		$email = '';
		
		$mailer = WC()->mailer();
				
		// get the preview email subject
		$email_heading = __( $email_heading, 'trackship-for-woocommerce' );
		//ob_start();
		
		$message = wc_trackship_email_manager()->email_content($email_content, $preview_id, $order);
		
		$wcast_delivered_status_analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_analytics_link', '');		
				
		if ( $wcast_delivered_status_analytics_link ) {	
			$regex = '#(<a href=")([^"]*)("[^>]*?>)#i';
			$message = preg_replace_callback($regex, array( $this, '_appendCampaignToString'), $message);	
		}
		
		$shipment_status = trackship_admin_customizer()->get_wc_shipment_status_for_preview( 'delivered', $preview_id );
		$tracking_items = trackship_admin_customizer()->get_tracking_items_for_preview( $preview_id );

		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items'	=> $tracking_items,
				'shipment_status'	=> $shipment_status,
				'order_id'			=> $preview_id,
				'show_shipment_status' => true,
				'new_status'		=> 'delivered',
				'ts4wc_preview'		=> true,
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items'	=> $tracking_items,
				'shipment_status'	=> $shipment_status,
				'order_id'			=> $preview_id,
				'show_shipment_status' => true,
				'new_status'		=> 'delivered',
				'ts4wc_preview'		=> true,
			), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
		}
		
		// Order detail template
		$message .= wc_get_template_html(
			'emails/tswc-email-order-details.php',
			array(
				'order'         => $order,
				'sent_to_admin' => $sent_to_admin,
				'plain_text'    => $plain_text,
				'email'         => $email,
				'wcast_show_product_image' => $wcast_show_product_image,
				'wcast_show_order_details' => $wcast_show_order_details,
				'ts4wc_preview' => true,
			),
			'woocommerce-advanced-shipment-tracking/', 
			trackship_for_woocommerce()->get_plugin_path() . '/templates/'
		);
		
		// Shipping Address template
		$message .= wc_get_template_html(
			'emails/shipping-email-addresses.php', array(
				'order'         => $order,
				'sent_to_admin' => $sent_to_admin,
				'ts4wc_preview' => true,
				'wcast_show_shipping_address' => $wcast_show_shipping_address,
			),
			'woocommerce-advanced-shipment-tracking/', 
			trackship_for_woocommerce()->get_plugin_path() . '/templates/'
		);

		// create a new email
		$email = new WC_Email();
		
		add_filter( 'wp_kses_allowed_html', array( trackship_admin_customizer(), 'my_allowed_tags' ) );
		add_filter( 'safe_style_css', array( trackship_admin_customizer(), 'safe_style_css_callback' ), 10, 1 );
		add_filter( 'woocommerce_email_styles', array( trackship_admin_customizer(), 'shipment_email_preview_css' ), 9999, 2 );

		add_filter( 'woocommerce_email_footer_text', array( $this, 'email_footer_text' ) );
		
		// wrap the content with the email template and then add styles
		$email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );
		echo $email_html = apply_filters( 'trackship_mail_content', $email_html, $email_heading );
	}

	/**
	 * Code for format email subject
	*/
	public function email_footer_text( $footer_text ) {
		$email_trackship_branding = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'shipment_email_settings', 'email_trackship_branding', 1);
		$class = !( $email_trackship_branding || in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) ? 'hide' : '';

		$tracking_number = isset( $this->tracking_number ) && $this->tracking_number ? $this->tracking_number : '';
		$track_url = 'https://track.trackship.com/track/' . $tracking_number;
		$trackship_branding_text = '<div class="trackship_branding ' . $class . '"><p><span style="vertical-align:middle;font-size: 14px;">Powered by <a href="' . $track_url . '" title="TrackShip" target="blank">TrackShip</a></span></p></div>';

		$unsubscribe = get_option( 'enable_email_widget' ) ? '<div style="text-align:center;"><a href="#">' . esc_html__( 'Unsubscribe', 'trackship-for-woocommerce' ) . '</a></div>' : '';

		$class1 = $email_trackship_branding || $unsubscribe || in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ? 'hide' : '';
		$default_footer = '<div class="default_footer ' . $class1 . '">' . $footer_text . '</div>';

		return $trackship_branding_text . $unsubscribe . $default_footer;
	}

	/**
	 * Code for append analytics link in email content
	*/
	public function _appendCampaignToString( $match ) {
		$wcast_delivered_status_analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_delivered_status_email_settings', 'wcast_delivered_status_analytics_link', '');
		
		$url = $match[2];
		if (strpos($url, '?') === false) {
			$url .= '?';
		}
		$url .= $wcast_delivered_status_analytics_link;
		return $match[1] . $url . $match[3];
	}	
}

/**
 * Initialise our Customizer settings
 */
new TSWC_Delivered_Customizer_Email();

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
class TSWC_Onhold_Customizer_Email {
	// Get our default values	
	public function __construct() {
		// Only proceed if this is own request.
		if ( ! self::is_own_preview_request() ) {
			return;
		}
		// Get our Customizer defaults
		$this->defaults = trackship_admin_customizer()->wcast_shipment_settings_defaults( 'onhold' );
		
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
		return isset( $_REQUEST['shipment-email-customizer-preview'] ) && 'on_hold' === $_REQUEST['status'];
	}

	/**
	 * Set up preview
	 *
	 * @return void
	 */
	public function set_up_preview() {
		
		include trackship_for_woocommerce()->get_plugin_path() . '/includes/customizer/preview/onhold_preview.php';		
		exit;			
	}
	
	/**
	 * Code for preview of in transit email
	*/
	public function preview_onhold_email() {
		// Load WooCommerce emails.
		$preview_id = get_option( 'email_preview', 'mockup' );
		$order = trackship_admin_customizer()->get_wc_order_for_preview( $preview_id );	
		
		$email_heading = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_onhold_email_settings', 'wcast_onhold_email_heading', $this->defaults['wcast_onhold_email_heading']);		
		$email_heading = str_replace( '{site_title}', $this->get_blogname(), $email_heading );
		$email_heading =  str_replace( '{order_number}', $order->get_order_number(), $email_heading );
		
		$email_content = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_onhold_email_settings', 'wcast_onhold_email_content', $this->defaults['wcast_onhold_email_content']);
		$email_content = html_entity_decode( $email_content );
		
		$wcast_show_order_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_onhold_email_settings', 'wcast_onhold_show_order_details', $this->defaults['wcast_onhold_show_order_details']);		
		
		$wcast_show_product_image = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_onhold_email_settings', 'wcast_onhold_show_product_image', $this->defaults['wcast_onhold_show_product_image']);

		$wcast_show_shipping_address = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_onhold_email_settings', 'wcast_onhold_show_shipping_address', $this->defaults['wcast_onhold_show_shipping_address']);		
		
		$sent_to_admin = false;
		$plain_text = false;
		$email = '';
		
		$mailer = WC()->mailer();
				
		// get the preview email subject
		$email_heading = __( $email_heading, 'trackship-for-woocommerce' );
		//ob_start();
		
		$message = wc_trackship_email_manager()->email_content($email_content, $preview_id, $order);
		
		$wcast_onhold_analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_onhold_email_settings', 'wcast_onhold_analytics_link', '');		
				
		if ( $wcast_onhold_analytics_link ) {	
			$regex = '#(<a href=")([^"]*)("[^>]*?>)#i';
			$message = preg_replace_callback($regex, array( $this, '_appendCampaignToString'), $message);	
		}
		
		$shipment_status = trackship_admin_customizer()->get_wc_shipment_status_for_preview( 'on_hold', $preview_id );
		$tracking_items = trackship_admin_customizer()->get_tracking_items_for_preview( $preview_id );

		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {				
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_items,
				'shipment_status' => $shipment_status,
				'order_id' => $preview_id,
				'show_shipment_status' => true,
				'new_status' => 'on_hold',
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_items,
				'shipment_status' => $shipment_status,
				'order_id' => $preview_id,
				'show_shipment_status' => true,
				'new_status' => 'on_hold',
			), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
		}
		
		if ( 1 == $wcast_show_order_details ) {			
			$message .= wc_get_template_html(
				'emails/tswc-email-order-details.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
					'plain_text'    => $plain_text,
					'email'         => $email,
					'wcast_show_product_image' => $wcast_show_product_image,
				),
				'woocommerce-advanced-shipment-tracking/', 
				trackship_for_woocommerce()->get_plugin_path() . '/templates/'
			);	
		}		
		
		if ( 1 == $wcast_show_shipping_address ) {
			$message .= wc_get_template_html(
				'emails/shipping-email-addresses.php',
				array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
				),
				'woocommerce-advanced-shipment-tracking/', 
				trackship_for_woocommerce()->get_plugin_path() . '/templates/'
			);
		}
			
		// create a new email
		$email = new WC_Email();
		
		add_filter( 'wp_kses_allowed_html', array( trackship_admin_customizer(), 'my_allowed_tags' ) );
		add_filter( 'safe_style_css', array( trackship_admin_customizer(), 'safe_style_css_callback' ), 10, 1 );
		add_filter( 'woocommerce_email_styles', array( trackship_admin_customizer(), 'shipment_email_preview_css' ), 9999, 2 );
		
		// wrap the content with the email template and then add styles
		$email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );
		echo wp_kses_post( $email_html );
	}
	
	/**
	 * Code for append analytics link in email content
	*/
	public function _appendCampaignToString( $match ) {
		$wcast_onhold_analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_onhold_email_settings', 'wcast_onhold_analytics_link', '');
		
		$url = $match[2];
		if (strpos($url, '?') === false) {
			$url .= '?';
		}
		$url .= $wcast_onhold_analytics_link;
		return $match[1] . $url . $match[3];
	}	
}

/**
 * Initialise our Customizer settings
 */
new TSWC_Onhold_Customizer_Email();

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
class TSWC_Email_Customizer_Preview {

	public $status;

	// Get our default values	
	public function __construct( $status = 'in_transit' ) {
		$this->status = $status;
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
	 * Set up preview
	 *
	 * @return void
	 */
	public function set_up_preview() {
		?>
		<head>
			<meta charset="<?php bloginfo('charset'); ?>" />
			<meta name="viewport" content="width=device-width" />
			<style type="text/css" id="ast_designer_custom_css">.woocommerce-store-notice.demo_store, .mfp-hide {display: none;}</style>
		</head>
		<body class="ast_preview_body" style="margin:0;">
			<div id="overlay"></div>
			<div id="ast_preview_wrapper" style="display: block;">
				<?php self::preview_email(); ?>
			</div>
			<?php do_action( 'woomail_footer' ); ?>
		</body>
		<?php
		exit;
	}

	/**
	 * Code for preview of in transit email
	*/
	public function preview_email() {
		// Load WooCommerce emails.

		$status = $this->status;

		$preview_id = get_option( 'email_preview', 'mockup' );
		$order = trackship_admin_customizer()->get_wc_order_for_preview( $preview_id );
		// print_r($order);
		$email_heading = get_trackship_email_settings( $status, 'heading' );
		$email_heading = str_replace( '{site_title}', $this->get_blogname(), $email_heading );
		$email_heading = str_replace( '{order_number}', $order->get_order_number(), $email_heading );
		$email_heading = str_replace( '{shipment_status}', 'In Transit', $email_heading );
		
		$email_content = get_trackship_email_settings( $status, 'content' );
		$email_content = html_entity_decode( $email_content );
		
		$wcast_show_order_details = get_trackship_email_settings( $status, 'show_order_details' );
		$wcast_show_product_image = get_trackship_email_settings( $status, 'show_product_image' );
		$wcast_show_shipping_address = get_trackship_email_settings( $status, 'show_shipping_address' );
		
		$sent_to_admin = false;
		$plain_text = false;
		$email = '';
		
		// get the preview email subject
		$email_heading = __( $email_heading, 'trackship-for-woocommerce' );
		//ob_start();
		
		$message = wc_trackship_email_manager()->email_content( $email_content, $preview_id, $order );
		
		$shipment_row = trackship_admin_customizer()->get_wc_shipment_row_for_preview( $status, $preview_id );
		$tracking_items = trackship_admin_customizer()->get_tracking_items_for_preview( $preview_id );
		
		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array(
				'tracking_items'	=> $tracking_items,
				'shipment_row'		=> $shipment_row,
				'order_id'			=> $preview_id,
				'show_shipment_status' => true,
				'new_status'		=> 'pickup_reminder' == $status ? 'available_for_pickup' : $status,
				'ts4wc_preview'		=> true,
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array(
				'tracking_items'	=> $tracking_items,
				'shipment_row'		=> $shipment_row,
				'order_id'			=> $preview_id,
				'show_shipment_status' => true,
				'new_status'		=> 'pickup_reminder' == $status ? 'available_for_pickup' : $status,
				'ts4wc_preview'		=> true,
			), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
		}

		// Order detail template
		$message .= wc_get_template_html(
			'emails/tswc-email-order-details.php',
			array(
				'order'			=> $order,
				'sent_to_admin'	=> $sent_to_admin,
				'plain_text'	=> $plain_text,
				'email'			=> $email,
				'wcast_show_product_image' => $wcast_show_product_image,
				'wcast_show_order_details' => $wcast_show_order_details,
				'new_status'	=> 'pickup_reminder' == $status ? 'available_for_pickup' : $status,
				'ts4wc_preview'	=> true,
			),
			'woocommerce-advanced-shipment-tracking/', 
			trackship_for_woocommerce()->get_plugin_path() . '/templates/'
		);
		
		if ( 'pickup_reminder' != $status ) {
			// Shipping Address template
			$message .= wc_get_template_html(
				'emails/shipping-email-addresses.php', array(
					'order'			=> $order,
					'sent_to_admin'	=> $sent_to_admin,
					'ts4wc_preview'	=> true,
					'wcast_show_shipping_address' => $wcast_show_shipping_address,
				),
				'woocommerce-advanced-shipment-tracking/', 
				trackship_for_woocommerce()->get_plugin_path() . '/templates/'
			);
		}
		
		$mailer = WC()->mailer();
		// create a new email
		$email = new WC_Email();
		
		add_filter( 'wp_kses_allowed_html', array( trackship_admin_customizer(), 'my_allowed_tags' ) );
		add_filter( 'safe_style_css', array( trackship_admin_customizer(), 'safe_style_css_callback' ), 10, 1 );
		add_filter( 'woocommerce_email_styles', array( trackship_admin_customizer(), 'shipment_email_preview_css' ), 9999, 2 );

		add_filter( 'woocommerce_email_footer_text', array( $this, 'email_footer_text' ) );
		
		// wrap the content with the email template and then add styles
		$email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );
		$email_html = apply_filters( 'trackship_mail_content', $email_html, $email_heading, $preview_id, 'shipment_email', $status );
		echo wp_kses_post($email_html);
	}

	/**
	 * Code for format email subject
	*/
	public function email_footer_text( $footer_text ) {
		$show_trackship_branding = get_trackship_email_settings( 'common_settings', 'show_trackship_branding', 1 );
		$class = !( $show_trackship_branding || in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan', 'Trial Ended' ) ) ) ? 'hide' : '';

		$trackship_branding_text = '<div class="tracking_widget_email trackship_branding ' . $class . '"><p style="margin: 0;"><span style="vertical-align:middle;font-size: 14px;">Powered by <a href="https://trackship.com" title="TrackShip" target="blank">TrackShip</a></span></p></div>';

		$unsubscribe = get_trackship_settings( 'enable_email_widget' ) ? '<div style="text-align:center;"><a href="#">' . esc_html__( 'Unsubscribe', 'trackship-for-woocommerce' ) . '</a></div>' : '';

		$class1 = $show_trackship_branding || $unsubscribe || in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan', 'Trial Ended' ) ) ? 'hide' : '';
		$default_footer = '<div class="default_footer ' . $class1 . '">' . $footer_text . '</div>';

		return $trackship_branding_text . $unsubscribe . $default_footer;
	}
}


/**
 * Initialise our Customizer settings
*/
new TSWC_Email_Customizer_Preview();

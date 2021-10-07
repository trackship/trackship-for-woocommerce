<?php
/**
 * Customizer Setup and Custom Controls
 *
 */

/**
 * Adds the individual sections, settings, and controls to the theme customizer
 */
class TSWC_Intransit_Customizer_Email {
	// Get our default values	
	public function __construct() {
		// Get our Customizer defaults
		$this->defaults = $this->wcast_generate_defaults();
		
		$wc_ast_api_key = get_option('wc_ast_api_key');

		if ( !$wc_ast_api_key ) {
			return;
		}
		
		// Register our sample default controls
		add_action( 'customize_register', array( $this, 'wcast_register_sample_default_controls' ) );
		
		// Only proceed if this is own request.
		if ( ! self::is_own_customizer_request() && ! self::is_own_preview_request() ) {
			return;
		}
		
		// Register our sections
		add_action( 'customize_register', array( trackship_customizer(), 'wcast_add_customizer_sections' ) );	
		
		// Remove unrelated components.
		add_filter( 'customize_loaded_components', array( trackship_customizer(), 'remove_unrelated_components' ), 99, 2 );

		// Remove unrelated sections.
		add_filter( 'customize_section_active', array( trackship_customizer(), 'remove_unrelated_sections' ), 10, 2 );	
		
		// Unhook divi front end.
		add_action( 'woomail_footer', array( trackship_customizer(), 'unhook_divi' ), 10 );

		// Unhook Flatsome js
		add_action( 'customize_preview_init', array( trackship_customizer(), 'unhook_flatsome' ), 50  );
		
		add_filter( 'customize_controls_enqueue_scripts', array( trackship_customizer(), 'enqueue_customizer_scripts' ) );				
		
		add_action( 'parse_request', array( $this, 'set_up_preview' ) );	
		
		add_action( 'customize_preview_init', array( $this, 'enqueue_preview_scripts' ) );	

	}
	
	/**
	 * Add css and js for preview
	*/
	public function enqueue_preview_scripts() {		 
		wp_enqueue_script('wcast-email-preview-scripts', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/preview-scripts.js', array('jquery', 'customize-preview'), trackship_for_woocommerce()->version, true);
		wp_enqueue_style('wcast-preview-styles', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/preview-styles.css', array(), trackship_for_woocommerce()->version  );
		// Send variables to Javascript
		$preview_id     = get_theme_mod('wcast_email_preview_order_id');
		wp_localize_script('wcast-email-preview-scripts', 'wcast_preview', array(
			'site_title'   => $this->get_blogname(),
			'order_number' => $preview_id,			
		));
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
		return isset( $_REQUEST['wcast-intransit-email-customizer-preview'] ) && '1' === $_REQUEST['wcast-intransit-email-customizer-preview'];
	}
	
	/**
	 * Checks to see if we are opening our custom customizer controls
	 *
	 * @return bool
	 */
	public static function is_own_customizer_request() {
		return isset( $_REQUEST['email'] ) && 'trackship_shipment_status_email' === $_REQUEST['email'] ;
	}	
	
	/**
	 * Get Customizer URL
	 *
	 */
	public static function get_customizer_url( $email, $shipment_status, $return_tab  ) {		
			$customizer_url = add_query_arg( array(
				'wcast-customizer' => '1',
				'email' => $email,
				'shipment_status' => $shipment_status,
				'autofocus[section]' => 'trackship_shipment_status_email',
				'url'                  => urlencode( add_query_arg( array( 'wcast-intransit-email-customizer-preview' => '1' ), home_url( '/' ) ) ),
				'return'               => urlencode( self::get_email_settings_page_url($return_tab) ),
			), admin_url( 'customize.php' ) );		

		return $customizer_url;
	}	
	
	/**
	 * Get WooCommerce email settings page URL
	 *
	 * @return string
	 */
	public static function get_email_settings_page_url( $return_tab ) {
		return admin_url( 'admin.php?page=trackship-for-woocommerce&tab=notifications' );
	}
	
	/**
	 * Code for initialize default value for customizer
	*/
	public function wcast_generate_defaults() {		
		$customizer_defaults = array(			
			'wcast_intransit_email_subject' => __( 'Your order #{order_number} is in transit', 'trackship-for-woocommerce' ),
			'wcast_intransit_email_heading' => __( 'In Transit', 'trackship-for-woocommerce' ),
			'wcast_intransit_email_content' => __( "Hi there. we thought you'd like to know that your recent order from {site_title} is in transit", 'trackship-for-woocommerce' ),				
			'wcast_enable_intransit_email'  => '',
			'wcast_intransit_email_to'  => 	'{customer_email}',			
			'wcast_intransit_show_order_details' => 1,			
			'wcast_intransit_hide_shipping_item_price' => 1,	
			'wcast_intransit_show_shipping_address' => 1,
			'wcast_intransit_email_code_block' => '',
			
		);

		return apply_filters( 'skyrocket_customizer_defaults', $customizer_defaults );
	}	
	
	/**
	 * Register our sample default controls
	 */
	public function wcast_register_sample_default_controls( $wp_customize ) {		
		/**
		* Load all our Customizer Custom Controls
		*/
		require_once trailingslashit( dirname(__FILE__) ) . 'custom-controls.php';
		
		$font_size_array[ '' ] = __( 'Select' );
		for ( $i = 10; $i <= 30; $i++ ) {
			$font_size_array[ $i ] = $i . 'px';
		}

		// Shipment status		
		$wp_customize->add_setting( 'wcast_shipment_status_type',
			array(
				'default' => 'mockup',
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Select_Custom_Control( $wp_customize, 'wcast_shipment_status_type',
			array(
				'label' => __( 'Shipment status', 'trackship-for-woocommerce' ),
				'description' => '',
				'section' => 'trackship_shipment_status_email',
				'input_attrs' => array(
					'placeholder' => __( 'Select shipment status', 'trackship-for-woocommerce' ),
					'class' => 'preview_shipment_status_type',
				),
				'choices' => array(
					'in_transit' => __( 'In Transit', 'trackship-for-woocommerce' ),
					'on_hold' => __( 'On Hold', 'trackship-for-woocommerce' ),
					'return_to_sender' => __( 'Return To Sender', 'trackship-for-woocommerce' ),
					'available_for_pickup' => __( 'Available For Pickup', 'trackship-for-woocommerce' ),
					'out_for_delivery' => __( 'Out For Delivery', 'trackship-for-woocommerce' ),
					'delivered' => __( 'Delivered', 'trackship-for-woocommerce' ),
					'failure' => __( 'Failed Attempt', 'trackship-for-woocommerce' ),
					'exception' => __( 'Exception', 'trackship-for-woocommerce' ),
				),
			)
		) );
		
		// Display Shipment Provider image/thumbnail
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_enable_intransit_email]',
			array(
				'default' => $this->defaults['wcast_enable_intransit_email'],
				'transport' => 'postMessage',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'wcast_intransit_email_settings[wcast_enable_intransit_email]',
			array(
				'label' => __( 'Enable In Transit email', 'trackship-for-woocommerce' ),
				'description' => '',
				'section' => 'trackship_shipment_status_email',
				'type' => 'checkbox',
				'active_callback' => array( $this, 'active_callback' ),
			)
		);
			
		// Header Text		
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_email_to]',
			array(
				'default' => $this->defaults['wcast_intransit_email_to'],
				'transport' => 'postMessage',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'wcast_intransit_email_settings[wcast_intransit_email_to]',
			array(
				'label' => __( 'Recipient(s)', 'trackship-for-woocommerce' ),
				'description' => esc_html__( 'Use the {customer_email} placeholder, you can add comma separated email addresses.', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => 'E.g. {customer.email}, admin@example.org',
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		);		
		
		// Header Text		
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_email_subject]',
			array(
				'default' => $this->defaults['wcast_intransit_email_subject'],
				'transport' => 'postMessage',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'wcast_intransit_email_settings[wcast_intransit_email_subject]',
			array(
				'label' => __( 'Email Subject', 'trackship-for-woocommerce' ),
				'description' => esc_html__( 'Available variables:', 'trackship-for-woocommerce' ) . ' {site_title}, {order_number}',
				'section' => 'trackship_shipment_status_email',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => __( $this->defaults['wcast_intransit_email_subject'], 'trackship-for-woocommerce' ),
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		);
		
		// Header Text		
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_email_heading]',
			array(
				'default' => $this->defaults['wcast_intransit_email_heading'],
				'transport' => 'refresh',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'wcast_intransit_email_settings[wcast_intransit_email_heading]',
			array(
				'label' => __( 'Email heading', 'trackship-for-woocommerce' ),
				'description' => esc_html__( 'Available variables:', 'trackship-for-woocommerce' ) . ' {site_title}, {order_number}',
				'section' => 'trackship_shipment_status_email',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => __( $this->defaults['wcast_intransit_email_heading'], 'trackship-for-woocommerce' ),
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		);
		
		// Test of TinyMCE control
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_email_content]',
			array(
				'default' => $this->defaults['wcast_intransit_email_content'],
				'transport' => 'refresh',
				'type'  => 'option',
				'sanitize_callback' => 'wp_kses_post'
			)
		);
		$wp_customize->add_control( new TrackShip_TinyMCE_Custom_Control( $wp_customize, 'wcast_intransit_email_settings[wcast_intransit_email_content]',
			array(
				'label' => __( 'Email content', 'trackship-for-woocommerce' ),
				'description' => '',
				'section' => 'trackship_shipment_status_email',
				'input_attrs' => array(
					'toolbar1' => 'bold italic bullist numlist alignleft aligncenter alignright link',
					'mediaButtons' => true,
					'placeholder' => __( $this->defaults['wcast_intransit_email_content'], 'trackship-for-woocommerce' ),
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		) );
		
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_email_code_block]',
			array(
				'default' => $this->defaults['wcast_intransit_email_code_block'],
				'transport' => 'postMessage',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( new TrackShip_Codeinfoblock_Control( $wp_customize, 'wcast_intransit_email_settings[wcast_intransit_email_code_block]',
			array(
				'label' => __( 'Available variables:', 'trackship-for-woocommerce' ),
				'description' => '<code>{site_title}<br>{customer_email}<br>{customer_first_name}<br>{customer_last_name}<br>{customer_company_name}<br>{customer_username}<br>{order_number}<br>{est_delivery_date}</code>',
				'section' => 'trackship_shipment_status_email',
				'active_callback' => array( $this, 'active_callback' ),				
			)
		) );
				
		// Display the Shipping items
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_show_order_details]',
			array(
				'default' => $this->defaults['wcast_intransit_show_order_details'],
				'transport' => 'refresh',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'wcast_intransit_email_settings[wcast_intransit_show_order_details]',
			array(
				'label' => __( 'Display the Shipping items', 'trackship-for-woocommerce' ),
				'description' => '',
				'section' => 'trackship_shipment_status_email',
				'type' => 'checkbox',
				'active_callback' => array( $this, 'active_callback' ),	
			)
		);

		// Display the shipping address
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_show_shipping_address]',
			array(
				'default' => $this->defaults['wcast_intransit_show_shipping_address'],
				'transport' => 'refresh',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'wcast_intransit_email_settings[wcast_intransit_show_shipping_address]',
			array(
				'label' => __( 'Display the shipping address', 'trackship-for-woocommerce' ),
				'description' => '',
				'section' => 'trackship_shipment_status_email',
				'type' => 'checkbox',
				'active_callback' => array( $this, 'active_callback' ),
			)
		);				
		
		// Google Analytics Heading
		$wp_customize->add_setting( 'wcast_intransit_email_settings[analytics_heading]',
			array(
				'default' => '',
				'transport' => 'postMessage',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Heading_Control( $wp_customize, 'wcast_intransit_email_settings[analytics_heading]',
			array(
				'label' => __( 'Google Analytics', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email',
				'active_callback' => array( $this, 'active_callback' ),		
			)
		) );
		
		// Google Analytics link tracking
		$wp_customize->add_setting( 'wcast_intransit_email_settings[wcast_intransit_analytics_link]',
			array(
				'default' => '',
				'transport' => 'refresh',
				'type'  => 'option',				
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'wcast_intransit_email_settings[wcast_intransit_analytics_link]',
			array(
				'label' => __( 'Google Analytics link tracking', 'trackship-for-woocommerce' ),
				'description' => esc_html__( 'This will be appended to URL in the email content', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => '',
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		);	
	}		
	
	public function active_callback() {
		if ( self::is_own_preview_request() ) {
			return true;
		} else {
			return false;
		}
	}
	
	public function active_callback_only_show_order_details() {
		
		$show_order_details = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_intransit_email_settings', 'wcast_intransit_show_order_details', $this->defaults['wcast_intransit_show_order_details']);		
		
		if ( self::is_own_preview_request() && $show_order_details ) {
			return true;
		} else {
			return false;
		}
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
		include trackship_for_woocommerce()->get_plugin_path() . '/includes/customizer/preview/intransit_preview.php';		
		exit;			
	}
	
	/**
	 * Code for preview of in transit email
	*/
	public function preview_intransit_email() {
		// Load WooCommerce emails.
		$preview_id     = 1;
		$order = trackship_customizer()->get_wc_order_for_preview( 'mockup' );				
		
		$email_heading = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_intransit_email_settings', 'wcast_intransit_email_heading', $this->defaults['wcast_intransit_email_heading']);		
		$email_heading = str_replace( '{site_title}', $this->get_blogname(), $email_heading );
		$email_heading = str_replace( '{order_number}', $order->get_order_number(), $email_heading );
		$email_heading = str_replace( '{shipment_status}', 'In Transit', $email_heading );
		
		$email_content = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_intransit_email_settings', 'wcast_intransit_email_content', $this->defaults['wcast_intransit_email_content']);				
		
		$wcast_show_order_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_intransit_email_settings', 'wcast_intransit_show_order_details', $this->defaults['wcast_intransit_show_order_details']);
		
		$wcast_show_shipping_address = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_intransit_email_settings', 'wcast_intransit_show_shipping_address', $this->defaults['wcast_intransit_show_shipping_address']);		
		
		$sent_to_admin = false;
		$plain_text = false;
		$email = '';
		
		// get the preview email subject
		$email_heading = __( $email_heading, 'trackship-for-woocommerce' );
		//ob_start();
		
		$message = wc_trackship_email_manager()->email_content( $email_content, $preview_id, $order );
		
		$wcast_intransit_analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_intransit_email_settings', 'wcast_intransit_analytics_link', '');		
		
		if ( $wcast_intransit_analytics_link ) {	
			$regex = '#(<a href=")([^"]*)("[^>]*?>)#i';
			$message = preg_replace_callback($regex, array( $this, '_appendCampaignToString'), $email_content);	
		}
		
		$shipment_status = trackship_customizer()->get_wc_shipment_status_for_preview( 'in_transit' );
		$tracking_items = trackship_customizer()->get_tracking_items_for_preview();		
		
		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {				
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_items,
				'shipment_status' => $shipment_status,
				'order_id' => $preview_id,
				'show_shipment_status' => true,
				'new_status' => 'in_transit',
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_items,
				'shipment_status' => $shipment_status,
				'order_id' => $preview_id,
				'show_shipment_status' => true,
				'new_status' => 'in_transit',
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
				),
				'woocommerce-advanced-shipment-tracking/', 
				trackship_for_woocommerce()->get_plugin_path() . '/templates/'
			);	
		}				
		
		if ( 1 == $wcast_show_shipping_address ) {
			$message .= wc_get_template_html(
				'emails/shipping-email-addresses.php', array(
					'order'         => $order,
					'sent_to_admin' => $sent_to_admin,
				),
				'woocommerce-advanced-shipment-tracking/', 
				trackship_for_woocommerce()->get_plugin_path() . '/templates/'
			);
		}
		
		$mailer = WC()->mailer();
		// create a new email
		$email = new WC_Email();
		
		add_filter( 'wp_kses_allowed_html', array( trackship_customizer(), 'my_allowed_tags' ) );
		add_filter( 'safe_style_css', array( trackship_customizer(), 'safe_style_css_callback' ), 10, 1 );
		
		// wrap the content with the email template and then add styles
		$email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );
		echo wp_kses_post( $email_html );
	}
	
	/**
	 * Code for append analytics link in email content
	*/
	public function _appendCampaignToString( $match ) {
		$wcast_intransit_analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'wcast_intransit_email_settings', 'wcast_intransit_analytics_link', '' );
		
		$url = $match[2];
		if (strpos($url, '?') === false) {
			$url .= '?';
		}
		$url .= $wcast_intransit_analytics_link;
		return $match[1] . $url . $match[3];
	}
}

/**
 * Initialise our Customizer settings
*/
new TSWC_Intransit_Customizer_Email();

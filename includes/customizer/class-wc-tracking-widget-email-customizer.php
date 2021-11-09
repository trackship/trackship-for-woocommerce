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
class TSWC_Tracking_widget_email_Customizer {
	// Get our default values	
	private static $order_ids  = null;
	
	public function __construct() {
		// Get our Customizer defaults
		$this->defaults = $this->wcast_generate_defaults();		
		
		// Register our sample default controls
		add_action( 'customize_register', array( $this, 'wcast_register_sample_default_controls' ) );
		
		// Only proceed if this is own request.
		if ( ! $this->is_own_customizer_request() && ! $this->is_own_preview_request() ) {
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
		 wp_enqueue_script('wcast-preview-scripts', trackship_for_woocommerce()->plugin_dir_url() . '/assets/js/preview-scripts.js', array('jquery', 'customize-preview'), trackship_for_woocommerce()->version, true);
		 wp_enqueue_style('wcast-preview-styles', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/preview-styles.css', array(), trackship_for_woocommerce()->version  );
		 $preview_id     = get_theme_mod('wcast_email_preview_order_id');
		 wp_localize_script('wcast-preview-scripts', 'wcast_preview', array(
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
	public function is_own_preview_request() {
		return isset( $_REQUEST['action'] ) && 'preview_tracking_email_widget' === $_REQUEST['action'];
	}
	
	/**
	 * Checks to see if we are opening our custom customizer controls
	 *
	 * @return bool
	 */
	public function is_own_customizer_request() {
		return isset( $_REQUEST['email'] ) && 'trackship_shipment_status_email_widget' === $_REQUEST['email'] ;
	}
	
	/**
	 * Get Customizer URL
	 *
	 */
	public function get_customizer_url( $email, $return_tab ) {	
		return add_query_arg( array(
			'wcast-customizer' => '1',
			'email' => $email,						
			'autofocus[section]' => 'trackship_shipment_status_email_widget',
			'url'                  => urlencode( add_query_arg( array( 'action' => 'preview_tracking_email_widget' ), home_url( '/' ) ) ),
			'return'               => urlencode( $this->get_email_settings_page_url( $return_tab ) ),								
		), admin_url( 'customize.php' ) );
	}
	
	/**
	 * Get WooCommerce email settings page URL
	 *
	 * @return string
	 */
	public function get_email_settings_page_url( $return_tab ) {
		return admin_url( 'admin.php?page=trackship-for-woocommerce&tab=tracking-page' );
	}
	
	/**
	 * Code for initialize default value for customizer
	*/	
	public function wcast_generate_defaults() {
		$customizer_defaults = array(
			'track_button_Text'				=> __( 'Track your order', 'trackship-for-woocommerce' ),
			'track_button_color'			=> '#3c4858',
			'track_button_text_color'		=> '#fff',
			'track_button_font_size'		=> 15,
			'widget_padding'				=> 20,
			'tracking_page_layout'			=> 't_layout_1',
			'track_button_border_radius'	=> 0,
			'border_color'					=> '#e8e8e8',
			'bg_color'						=> '#fff',
			'font_color'					=> '#333',
		);

		return apply_filters( 'ast_customizer_defaults', $customizer_defaults );
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
		
		//Widget border color  shipment_email_settings[widget_padding]
		$wp_customize->add_setting( 'shipment_email_settings[border_color]',
			array(
				'default' => $this->defaults['border_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'shipment_email_settings[border_color]', array(
			'label' => __( 'Widget border color', 'trackship-for-woocommerce' ),
			'section' => 'trackship_shipment_status_email_widget',
		)));
		
		//Widget Background color		
		$wp_customize->add_setting( 'shipment_email_settings[bg_color]',
			array(
				'default' => $this->defaults['bg_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'shipment_email_settings[bg_color]', array(
			'label' => __( 'Widget background color', 'trackship-for-woocommerce' ),
			'section' => 'trackship_shipment_status_email_widget',
		)));
		
		//Widget font color	
		$wp_customize->add_setting( 'shipment_email_settings[font_color]',
			array(
				'default' => $this->defaults['font_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'shipment_email_settings[font_color]', array(
			'label' => __( 'Widget Font color', 'trackship-for-woocommerce' ),
			'section' => 'trackship_shipment_status_email_widget',
		)));
		
		// Table content font widget padding
		$wp_customize->add_setting( 'shipment_email_settings[widget_padding]',
			array(
				'default' => $this->defaults['widget_padding'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Slider_Custom_Control( $wp_customize, 'shipment_email_settings[widget_padding]',
			array(
				'label' => __( 'Padding of widget', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email_widget',
				'input_attrs' => array(
					'default' => $this->defaults['widget_padding'],
					'step'  => 1,
					'min'   => 0,
					'max'   => 30,
				),
			)
		));
		
		//select progress bar design  
		$wp_customize->add_setting( 'shipment_email_settings[tracking_page_layout]',
			array(
				'default' => $this->defaults['tracking_page_layout'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Select_Custom_Control( $wp_customize, 'shipment_email_settings[tracking_page_layout]',
			array(
				'label' => __( 'Tracker Type', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email_widget',
				'input_attrs' => array(
					'placeholder' => __( 'Widget Tracker Type', 'trackship-for-woocommerce' ),
					'class' => '',
				),
				'choices' => array(
					't_layout_2' => __( 'Progress Bar', 'trackship-for-woocommerce' ),
					't_layout_1' => __( 'Icons', 'trackship-for-woocommerce' ),
					't_layout_3' => __( 'Single icon', 'trackship-for-woocommerce' ),
				),
			)
		) );
		
		// Test of Toggle Switch Custom Control
		$wp_customize->add_setting( 'shipment_email_settings[track_button_heading]',
			array(
				'default' => '',
				'transport' => 'postMessage',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Heading_Control( $wp_customize, 'shipment_email_settings[track_button_heading]',
			array(
				'label' => __( 'Track button', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email_widget',				
			)
		) );
		
		// Track button Text Text		
		$wp_customize->add_setting( 'shipment_email_settings[track_button_Text]',
			array(
				'default' => $this->defaults['track_button_Text'],
				'transport' => 'refresh',
				'type'  => 'option',
				'sanitize_callback' => ''
			)
		);
		$wp_customize->add_control( 'shipment_email_settings[track_button_Text]',
			array(
				'label' => __( 'Track button Text', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email_widget',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => __( $this->defaults['track_button_Text'], 'trackship-for-woocommerce' ),
				),			
			)
		);
		
		// Button font size
		// Add our Text Radio Button setting and Custom Control for controlling alignment of icons
		$wp_customize->add_setting( 'shipment_email_settings[track_button_font_size]',
			array(
				'default' => $this->defaults['track_button_font_size'],
				'transport' => 'refresh',
				'type' => 'option',
				'sanitize_callback' => 'ast_radio_sanitization'
			)
		);
		$wp_customize->add_control( new TrackShip_Text_Radio_Button_Custom_Control( $wp_customize, 'shipment_email_settings[track_button_font_size]',
			array(
				'label' => __( 'Button size', 'ast-pro' ),				
				'section' => 'trackship_shipment_status_email_widget',
				'choices' => array(
					15 => __( 'Normal', 'ast-pro' ),
					20 => __( 'Large', 'ast-pro'  )
				),
			)
		) );
				
		// Button color
		$wp_customize->add_setting( 'shipment_email_settings[track_button_color]',
			array(
				'default' => $this->defaults['track_button_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( 'shipment_email_settings[track_button_color]',
			array(
				'label' => __( 'Button color', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email_widget',
				'type' => 'color',
			)
		);
		
		// Button font color
		$wp_customize->add_setting( 'shipment_email_settings[track_button_text_color]',
			array(
				'default' => $this->defaults['track_button_text_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( 'shipment_email_settings[track_button_text_color]',
			array(
				'label' => __( 'Button font color', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email_widget',
				'type' => 'color',
			)
		);
		
		// Button radius
		$wp_customize->add_setting( 'shipment_email_settings[track_button_border_radius]',
			array(
				'default' => $this->defaults['track_button_border_radius'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Slider_Custom_Control( $wp_customize, 'shipment_email_settings[track_button_border_radius]',
			array(
				'label' => __( 'Button radius', 'trackship-for-woocommerce' ),
				'section' => 'trackship_shipment_status_email_widget',
				'input_attrs' => array(
					'default' => $this->defaults['track_button_border_radius'],
					'step'  => 1,
					'min'   => 0,
					'max'   => 10,
				),
			)
		));
			
	}
	
	public function active_callback() {
		if ( self::is_own_preview_request() ) {
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
		include trackship_for_woocommerce()->get_plugin_path() . '/includes/customizer/preview/outfordelivery_preview.php';		
		exit;		
	}
	
	/**
	 * Code for preview of out for delivery email
	*/	
	public function preview_outfordelivery_email() {
		// Load WooCommerce emails.
		$preview_id     = 1;
		$order = trackship_customizer()->get_wc_order_for_preview( 'mockup' );
		
		$email_heading = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_outfordelivery_email_settings', 'wcast_outfordelivery_email_heading', $this->defaults['wcast_outfordelivery_email_heading']);
		$email_heading = str_replace( '{site_title}', $this->get_blogname(), $email_heading );
		$email_heading =  str_replace( '{order_number}', $order->get_order_number(), $email_heading );
		
		$email_content = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_outfordelivery_email_settings', 'wcast_outfordelivery_email_content', $this->defaults['wcast_outfordelivery_email_content']);
		
		$wcast_show_order_details = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_outfordelivery_email_settings', 'wcast_outfordelivery_show_order_details', $this->defaults['wcast_outfordelivery_show_order_details']);	
		
		$wcast_show_shipping_address = trackship_for_woocommerce()->ts_actions->get_checkbox_option_value_from_array('wcast_outfordelivery_email_settings', 'wcast_outfordelivery_show_shipping_address', $this->defaults['wcast_outfordelivery_show_shipping_address']);
		
		$sent_to_admin = false;
		$plain_text = false;
		$email = '';
		
		
		$mailer = WC()->mailer();
				
		// get the preview email subject
		$email_heading = __( $email_heading, 'trackship-for-woocommerce' );
		//ob_start();
		
		$message = wc_trackship_email_manager()->email_content($email_content, $preview_id, $order);
		
		$wcast_outfordelivery_analytics_link = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('wcast_outfordelivery_email_settings', 'wcast_outfordelivery_analytics_link', '');	
				
		if ( $wcast_outfordelivery_analytics_link ) {	
			$regex = '#(<a href=")([^"]*)("[^>]*?>)#i';
			$message = preg_replace_callback($regex, array( $this, '_appendCampaignToString'), $message);	
		}
		
		$shipment_status = trackship_customizer()->get_wc_shipment_status_for_preview( 'out_for_delivery' );
		$tracking_items = trackship_customizer()->get_tracking_items_for_preview();

		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {				
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_items,
				'shipment_status' => $shipment_status,
				'order_id' => $preview_id,
				'show_shipment_status' => true,
				'new_status' => 'out_for_delivery',
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$message .= wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_items,
				'shipment_status' => $shipment_status,
				'order_id' => $preview_id,
				'show_shipment_status' => true,
				'new_status' => 'out_for_delivery',
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
		
		// create a new email
		$email = new WC_Email();
		
		add_filter( 'wp_kses_allowed_html', array( trackship_customizer(), 'my_allowed_tags' ) );
		add_filter( 'safe_style_css', array( trackship_customizer(), 'safe_style_css_callback' ), 10, 1 );
		
		// wrap the content with the email template and then add styles
		$email_html = apply_filters( 'woocommerce_mail_content', $email->style_inline( $mailer->wrap_message( $email_heading, $message ) ) );
		echo wp_kses_post( $email_html );
	}	
}

/**
 * Initialise our Customizer settings
*/
new TSWC_Tracking_Widget_Email_Customizer();

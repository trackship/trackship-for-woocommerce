<?php
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
		return isset( $_REQUEST['email'] ) && 'ast_tracking_page_section' === $_REQUEST['email'] ;
	}
	
	/**
	 * Get Customizer URL
	 *
	 */
	public function get_customizer_url( $email, $return_tab ) {	
		return add_query_arg( array(
			'wcast-customizer' => '1',
			'email' => $email,						
			'autofocus[section]' => 'ast_tracking_page_section',
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
			'widget_padding'				=> 15,
			'track_button_border_radius'	=> 3,
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
				'section' => 'ast_tracking_page_section',
				'input_attrs' => array(
					'default' => $this->defaults['widget_padding'],
					'step'  => 1,
					'min'   => 10,
					'max'   => 30,
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		));
		
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
				'section' => 'ast_tracking_page_section',				
				'active_callback' => array( $this, 'active_callback' ),
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
				'section' => 'ast_tracking_page_section',
				'type' => 'text',
				'input_attrs' => array(
					'class' => '',
					'style' => '',
					'placeholder' => __( $this->defaults['track_button_Text'], 'trackship-for-woocommerce' ),
				),
				'active_callback' => array( $this, 'active_callback' ),				
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
				'section' => 'ast_tracking_page_section',
				'choices' => array(
					15 => __( 'Normal', 'ast-pro' ),
					20 => __( 'Large', 'ast-pro'  )
				),
				'active_callback' => array( $this, 'active_callback' ),
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
				'section' => 'ast_tracking_page_section',
				'type' => 'color',
				'active_callback' => array( $this, 'active_callback' ),
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
				'section' => 'ast_tracking_page_section',
				'type' => 'color',
				'active_callback' => array( $this, 'active_callback' ),
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
				'section' => 'ast_tracking_page_section',
				'input_attrs' => array(
					'default' => $this->defaults['track_button_border_radius'],
					'step'  => 1,
					'min'   => 0,
					'max'   => 10,
				),
				'active_callback' => array( $this, 'active_callback' ),
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
		include trackship_for_woocommerce()->get_plugin_path() . '/includes/customizer/preview/intransit_preview.php';		
		exit;		
	}
	
	/**
	 * Code for preview of in transit email
	*/
	public function preview_intransit_email() {
		// Load WooCommerce emails.
		$preview_id     = get_theme_mod( 'wcast_intransit_email_preview_order_id' );
		
		$sent_to_admin = false;
		$plain_text = false;
		$email = '';
		
		if ( '' == $preview_id || 'mockup' == $preview_id ) {
			echo '<div style="padding: 35px 40px; background-color: white;">';
				esc_html_e( 'Please select order to preview.', 'trackship-for-woocommerce' );
			echo '</div>';
			return;
		}		
		
		$order = wc_get_order( $preview_id );
		
		if ( ! $order ) {
			echo '<div style="padding: 35px 40px; background-color: white;">';
				esc_html_e( 'Please select order to preview.', 'trackship-for-woocommerce' );
			echo '</div>';
			return;
		}
		
		// get the preview email subject
		$email_heading = __( 'Out for Delivery', 'trackship-for-woocommerce' );
						
		//$shipment_status = trackship_for_woocommerce()->actions->get_shipment_status( $preview_id );
		
		//echo '<pre>';print_r(trackship_for_woocommerce()->get_tracking_items( $preview_id ));echo '</pre>';
		$shipment_status = array();
		$shipment_status[0]['est_delivery_date'] = '9410803699300126968507';
		$shipment_status[0]['status'] = 'out_for_delivery';
		$shipment_status[0]['tracking_page'] = '#';
		
		$tracking_item = array();
		$tracking_item[0]['tracking_provider'] = 'usps';
		$tracking_item[0]['tracking_number'] = '9410803699300126968507';
		$tracking_item[0]['date_shipped'] = '1623801600';
		$tracking_item[0]['formatted_tracking_provider'] = 'USPS';
		$tracking_item[0]['formatted_tracking_link'] = '#';
		$tracking_item[0]['ast_tracking_link'] = '#';
		$tracking_item[0]['tracking_provider_image'] = '';
		
		$local_template	= get_stylesheet_directory() . '/woocommerce/emails/tracking-info.php';			
		
		if ( file_exists( $local_template ) && is_writable( $local_template ) ) {				
			$message = wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_item,
				'shipment_status' => $shipment_status,
				'order_id' => 1,
				'show_shipment_status' => true,
				'new_status' => 'out_for_delivery',
			), 'woocommerce-advanced-shipment-tracking/', get_stylesheet_directory() . '/woocommerce/' );
		} else {
			$message = wc_get_template_html( 'emails/tracking-info.php', array( 
				'tracking_items' => $tracking_item,
				'shipment_status' => $shipment_status,
				'order_id' => $preview_id,
				'show_shipment_status' => true,
				'new_status' => 'out_for_delivery',
			), 'woocommerce-advanced-shipment-tracking/', trackship_for_woocommerce()->get_plugin_path() . '/templates/' );
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
}

/**
 * Initialise our Customizer settings
*/
new TSWC_Tracking_Widget_Email_Customizer();

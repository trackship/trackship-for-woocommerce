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
class TSWC_Tracking_Page_Customizer {
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
		
		//add_action( 'parse_request', array( $this, 'set_up_preview' ) );	
		
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
		return isset( $_REQUEST['action'] ) && 'preview_tracking_page' === $_REQUEST['action'];
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
			'url'                  => urlencode( add_query_arg( array( 'action' => 'preview_tracking_page' ), home_url( '/' ) ) ),
			'return'               => urlencode( $this->get_email_settings_page_url( $return_tab ) ),								
		), admin_url( 'customize.php' ) );
	}
	
	/**
	 * Get WooCommerce email settings page URL
	 *
	 * @return string
	 */
	public function get_email_settings_page_url( $return_tab ) {
		return admin_url( 'admin.php?page=trackship-for-woocommerce&tab=settings' );
	}
	
	/**
	 * Code for initialize default value for customizer
	*/	
	public function wcast_generate_defaults() {
		$customizer_defaults = array(
			'wc_ast_select_tracking_page_layout' => 't_layout_1',
			'wc_ast_select_border_color' => '#cccccc',
			'wc_ast_select_bg_color' => '#fafafa',
			'wc_ast_hide_tracking_provider_image' => 0,
			'wc_ast_link_to_shipping_provider' => 1,
			'wc_ast_remove_trackship_branding' => 0,
			'wc_ast_hide_tracking_events' => 2,
			'wc_ast_select_font_color' => '#333',
			'widget_padding' => 15,
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
		
		$class = in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ? 'disable_branding' : '';
		$font_size_array[ '' ] = __( 'Select' );
		for ( $i = 10; $i <= 30; $i++ ) {
			$font_size_array[ $i ] = $i . 'px';
		}		
		
		//Widget Background color		
		$wp_customize->add_setting( 'wc_ast_select_bg_color',
			array(
				'default' => $this->defaults['wc_ast_select_bg_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wc_ast_select_bg_color', array(
			'label' => __( 'Widget background color', 'trackship-for-woocommerce' ),
			'section' => 'ast_tracking_page_section',
		)));
		
		//Widget font color	
		$wp_customize->add_setting( 'wc_ast_select_font_color',
			array(
				'default' => $this->defaults['wc_ast_select_font_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wc_ast_select_font_color', array(
			'label' => __( 'Widget Font color', 'trackship-for-woocommerce' ),
			'section' => 'ast_tracking_page_section',
		)));
		
		//Widget border color
		$wp_customize->add_setting( 'wc_ast_select_border_color',
			array(
				'default' => $this->defaults['wc_ast_select_border_color'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new WP_Customize_Color_Control( $wp_customize, 'wc_ast_select_border_color', array(
			'label' => __( 'Widget border color', 'trackship-for-woocommerce' ),
			'section' => 'ast_tracking_page_section',
		)));
		
		// Table content font widget padding  
		$wp_customize->add_setting( 'wc_ast_select_widget_padding',
			array(
				'default' => $this->defaults['widget_padding'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Slider_Custom_Control( $wp_customize, 'wc_ast_select_widget_padding',
			array(
				'label' => __( 'Padding of widget', 'trackship-for-woocommerce' ),
				'section' => 'ast_tracking_page_section',
				'input_attrs' => array(
					'default' => $this->defaults['widget_padding'],
					'step'  => 1,
					'min'   => 10,
					'max'   => 30,
				),
			)
		));
		
		//select progress bar design
		$wp_customize->add_setting( 'wc_ast_select_tracking_page_layout',
			array(
				'default' => $this->defaults['wc_ast_select_tracking_page_layout'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Select_Custom_Control( $wp_customize, 'wc_ast_select_tracking_page_layout',
			array(
				'label' => __( 'Tracker Type', 'trackship-for-woocommerce' ),
				'section' => 'ast_tracking_page_section',
				'input_attrs' => array(
					'placeholder' => __( 'Widget Tracker Type', 'trackship-for-woocommerce' ),
					'class' => '',
				),
				'choices' => array(
					't_layout_2' => __( 'Simple', 'trackship-for-woocommerce' ),
					't_layout_1' => __( 'Icons', 'trackship-for-woocommerce' ),
				),
			)
		) );
		
		// Heading Control
		$wp_customize->add_setting( 'wc_ast_select_display_option_heading',
			array(
				'default' => '',
				'transport' => 'postMessage',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_Heading_Control( $wp_customize, 'wc_ast_select_display_option_heading',
			array(
				'label' => __( 'Display Options', 'trackship-for-woocommerce' ),
				'section' => 'ast_tracking_page_section',				
			)
		) );
		
		//Hide tracking event 
		$wp_customize->add_setting( 'wc_ast_hide_tracking_events',
			array(
				'default' => $this->defaults['wc_ast_hide_tracking_events'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		
		$wp_customize->add_control( new TrackShip_Select_Custom_Control( $wp_customize, 'wc_ast_hide_tracking_events',
			array(
				'label' => __( 'Events Display Type', 'trackship-for-woocommerce' ),						
				'section' => 'ast_tracking_page_section',
				'input_attrs' => array(
					'placeholder' => __( 'Events Display Type', 'trackship-for-woocommerce' ),
					'class' => '',
				),
				'choices' => array(
					0 => __( 'Show all Events', 'trackship-for-woocommerce' ),
					1 => __( 'Hide tracking events', 'trackship-for-woocommerce' ),					
					2 => __( 'Show last event & expand', 'trackship-for-woocommerce' ),					
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		) );
		
		//enable link
		$wp_customize->add_setting( 'wc_ast_link_to_shipping_provider',
			array(
				'default' => $this->defaults['wc_ast_link_to_shipping_provider'],
				'transport' => 'postMessage',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( new TrackShip_checkbox_Custom_Control( $wp_customize, 'wc_ast_link_to_shipping_provider',
			array(
				'label' => __( 'Enable Tracking # link to Carrier', 'trackship-for-woocommerce' ),				
				'section' => 'ast_tracking_page_section',
				//'type' => 'checkbox',
				'input_attrs' => array(
					'class' => $class,
				),
				'active_callback' => array( $this, 'active_callback' ),			
			)
		) );
		
		//hide tracking provider image	
		$wp_customize->add_setting( 'wc_ast_hide_tracking_provider_image',
			array(
				'default' => $this->defaults['wc_ast_hide_tracking_provider_image'],
				'transport' => 'refresh',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);
		$wp_customize->add_control( 'wc_ast_hide_tracking_provider_image',
			array(
				'label' => __( 'Hide the Shipping Provider logo', 'trackship-for-woocommerce' ),				
				'section' => 'ast_tracking_page_section',
				'type' => 'checkbox',
				'active_callback' => array( $this, 'active_callback' ),				
			)
		);
		
		
		//remove trackship branding
		$wp_customize->add_setting( 'wc_ast_remove_trackship_branding',
			array(
				'default' => $this->defaults['wc_ast_remove_trackship_branding'],
				'transport' => 'postMessage',
				'sanitize_callback' => '',
				'type' => 'option',
			)
		);		
		$wp_customize->add_control( new TrackShip_checkbox_Custom_Control( $wp_customize, 'wc_ast_remove_trackship_branding',
			array(
				'label' => __( 'Hide TrackShip Branding', 'trackship-for-woocommerce' ),						
				'section' => 'ast_tracking_page_section',
				'input_attrs' => array(
					'class' => $class,
				),
				'active_callback' => array( $this, 'active_callback' ),
			)
		) );
						
	}
	
	public function active_callback() {
		if ( self::is_own_preview_request() ) {
			return true;
		} else {
			return false;
		}
	}
}

/**
 * Initialise our Customizer settings
*/
new TSWC_Tracking_Page_Customizer();

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_Trackship_Customizer {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {

	}
	
	public function my_allowed_tags( $tags ) {
		$tags['style'] = array( 'type' => true, );
		return $tags;
	}
	
	public function safe_style_css_callback( $styles ) {
		 $styles[] = 'display';
		return $styles;
	}
	
	/**
	 * Register the Customizer sections
	 */
	public function wcast_add_customizer_sections( $wp_customize ) {
		
		/**
		* Tracking Page Customizer Section
		*/
		$wp_customize->add_section( 'ast_tracking_page_section',
			array(
				'title' => __( 'Tracking Widget', 'trackship-for-woocommerce' ),
				'description' => ''
			)
		);
		
		$wp_customize->add_section( 'trackship_shipment_status_email',
			array(
				'title' => __( 'Email notification', 'trackship-for-woocommerce' ),
				'description' => '',				
			)
		);	

	}
	
	/**
	 * Add css and js for customizer
	*/
	public function enqueue_customizer_scripts() {
		if ( isset( $_REQUEST['wcast-customizer'] ) && '1' === $_REQUEST[ 'wcast-customizer' ] ) {
			wp_enqueue_style( 'wp-color-picker' );
			wp_enqueue_style('wcast-customizer-styles', trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/customizer-styles.css', array(), trackship_for_woocommerce()->version  );			
			wp_enqueue_script('wcast-customizer-scripts', trackship_for_woocommerce()->plugin_dir_url() . 'assets/js/customizer-scripts.js', array('jquery', 'customize-controls','wp-color-picker'), trackship_for_woocommerce()->version, true);
			
			$shipment_status = 'in_transit';
			if ( isset( $_REQUEST['shipment_status'] ) ) {
				$shipment_status = sanitize_text_field( $_REQUEST['shipment_status'] );
			}
			
			$r_mail = isset ($_REQUEST['email']) ? sanitize_text_field( $_REQUEST['email'] ) : '';
			// Send variables to Javascript
			wp_localize_script( 'wcast-customizer-scripts', 'wcast_customizer', array(
				'ajax_url'									=> admin_url('admin-ajax.php'),
				'shipment_status'							=> $shipment_status,
				'tracking_page_preview_url'					=> $this->get_tracking_preview_url(),
				'tracking_widget_email_preview_url'			=> $this->get_tracking_widget_email_preview_url(),				
				'customer_failure_preview_url'				=> $this->get_customer_failure_preview_url(),
				'customer_exception_preview_url'			=> $this->get_customer_exception_preview_url(),
				'customer_intransit_preview_url'			=> $this->get_customer_intransit_preview_url(),
				'customer_onhold_preview_url'				=> $this->get_customer_onhold_preview_url(),
				'customer_outfordelivery_preview_url'		=> $this->get_customer_outfordelivery_preview_url(),
				'customer_delivered_preview_url'			=> $this->get_customer_delivered_preview_url(),
				'customer_returntosender_preview_url'		=> $this->get_customer_returntosender_preview_url(),
				'customer_availableforpickup_preview_url'	=> $this->get_customer_availableforpickup_preview_url(),
				'customizer_title'							=> 'TrackShip',
				'trigger_click'								=> '#accordion-section-' . $r_mail . ' h3', $r_mail
			) );	

			wp_localize_script( 'wp-color-picker', 'wpColorPickerL10n', array(
				'clear'            => __( 'Clear', 'trackship-for-woocommerce' ),
				'clearAriaLabel'   => __( 'Clear color', 'trackship-for-woocommerce' ),
				'defaultString'    => __( 'Default', 'trackship-for-woocommerce' ),
				'defaultAriaLabel' => __( 'Select default color', 'trackship-for-woocommerce' ),
				'pick'             => __( 'Select Color', 'trackship-for-woocommerce' ),
				'defaultLabel'     => __( 'Color value', 'trackship-for-woocommerce' ),
			) );	
		}
	}
	
	/**
	 * Get Customizer URL
	 *
	 */
	public static function get_tracking_preview_url() {		
			$tracking_preview_url = add_query_arg( array(
				'action' => 'preview_tracking_page',
			), home_url( '' ) );		

		return $tracking_preview_url;
	}	
	
	public static function get_tracking_widget_email_preview_url() {		
			$tracking_widget_email_preview_url = add_query_arg( array(
				'action' => 'preview_tracking_email_widget',
			), home_url( '' ) );		

		return $tracking_widget_email_preview_url;
	}
		
	
	/**
	 * Get Tracking page preview URL
	 *
	 */
	public static function get_customer_failure_preview_url() {		
			$customer_failure_preview_url = add_query_arg( array(
				'wcast-failure-email-customizer-preview' => '1',
			), home_url( '' ) );		

		return $customer_failure_preview_url;
	}
	
	/**
	 * Get Exception Shipment status preview URL
	 *
	 */
	public function get_customer_exception_preview_url() {
		return add_query_arg( array(
			'wcast-exception-email-customizer-preview' => '1',
		), home_url( '' ) );
	}
	
	/**
	 * Get Tracking page preview URL
	 *
	 */
	public static function get_customer_intransit_preview_url() {		
			$customer_intransit_preview_url = add_query_arg( array(
				'wcast-intransit-email-customizer-preview' => '1',
			), home_url( '' ) );		

		return $customer_intransit_preview_url;
	}
	
	/**
	 * Get Tracking page preview URL
	 *
	 */
	public static function get_customer_onhold_preview_url() {		
			$customer_onhold_preview_url = add_query_arg( array(
				'wcast-onhold-email-customizer-preview' => '1',
			), home_url( '' ) );		

		return $customer_onhold_preview_url;
	}
	
	/**
	 * Get Tracking page preview URL
	 *
	 */
	public static function get_customer_outfordelivery_preview_url() {		
			$customer_intransit_preview_url = add_query_arg( array(
				'wcast-outfordelivery-email-customizer-preview' => '1',
			), home_url( '' ) );		

		return $customer_intransit_preview_url;
	}
	
	/**
	 * Get Tracking page preview URL
	 *
	 */
	public static function get_customer_delivered_preview_url() {		
			$customer_intransit_preview_url = add_query_arg( array(
				'wcast-delivered-email-customizer-preview' => '1',
			), home_url( '' ) );		

		return $customer_intransit_preview_url;
	}
	
	/**
	 * Get Tracking page preview URL
	 *
	 */
	public static function get_customer_returntosender_preview_url() {		
			$customer_intransit_preview_url = add_query_arg( array(
				'wcast-returntosender-email-customizer-preview' => '1',
			), home_url( '' ) );		

		return $customer_intransit_preview_url;
	}
	
	/**
	 * Get Tracking page preview URL
	 *
	 */
	public static function get_customer_availableforpickup_preview_url() {		
			$customer_intransit_preview_url = add_query_arg( array(
				'wcast-availableforpickup-email-customizer-preview' => '1',
			), home_url( '' ) );		

		return $customer_intransit_preview_url;
	}

	/**
	 * Remove unrelated components
	 *
	 * @param array $components
	 * @param object $wp_customize
	 * @return array
	 */
	public function remove_unrelated_components( $components, $wp_customize ) {
	
		// Iterate over components
		foreach ($components as $component_key => $component) {
			
			// Check if current component is own component
			if ( ! $this->is_own_component( $component ) ) {
				unset($components[$component_key]);
			}
		}
		
		// Return remaining components
		return $components;
	}
	
	/**
	 * Remove unrelated sections
	 *
	 * @param bool $active
	 * @param object $section
	 * @return bool
	 */
	public function remove_unrelated_sections( $active, $section ) {
		
		// Check if current section is own section
		if ( ! $this->is_own_section( $section->id ) ) {
			return false;
		}
		
		// We can override $active completely since this runs only on own Customizer requests
		return true;
	}

	/**
	* Remove unrelated controls
	*
	* @param bool $active
	* @param object $control
	* @return bool
	*/
	public function remove_unrelated_controls( $active, $control ) {
		
		// Check if current control belongs to own section
		if ( ! self::is_own_section( $control->section ) ) {
			return false;
		}

		// We can override $active completely since this runs only on own Customizer requests
		return $active;
	}

	/**
	* Check if current component is own component
	*
	* @param string $component
	* @return bool
	*/
	public static function is_own_component( $component ) {
		return false;
	}

	/**
	* Check if current section is own section
	*
	* @param string $key
	* @return bool
	*/
	public static function is_own_section( $key ) {
				
		if ( 'ast_tracking_page_section' === $key || 'trackship_shipment_status_email' === $key ) {
			return true;
		}

		// Section not found
		return false;
	}
	
	/*
	 * Unhook flatsome front end.
	 */
	public function unhook_flatsome() {
		// Unhook flatsome issue.
		wp_dequeue_style( 'flatsome-customizer-preview' );
		wp_dequeue_script( 'flatsome-customizer-frontend-js' );
	}
	
	/*
	 * Unhook Divi front end.
	 */
	public function unhook_divi() {
		// Divi Theme issue.
		remove_action( 'wp_footer', 'et_builder_get_modules_js_data' );
		remove_action( 'et_customizer_footer_preview', 'et_load_social_icons' );
	}
	
	/**
	 * Get Order Ids
	 *
	 * @return array
	 */
	public static function get_order_ids() {		
		$order_array = array();
		$order_array['mockup'] = __( 'Select order to preview', 'trackship-for-woocommerce' );
		
		$orders = wc_get_orders( array(
			'limit'        => 20,
			'orderby'      => 'date',
			'order'        => 'DESC',
			'meta_key'     => '_wc_shipment_tracking_items', // The postmeta key field
			'meta_compare' => 'EXISTS', // The comparison argument
		));	
			
		foreach ( $orders as $order ) {								
			$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order->get_id() );
			if ( $tracking_items ) {
				$order_array[ $order->get_id() ] = $order->get_id() . ' - ' . $order->get_billing_first_name() . ' ' . $order->get_billing_last_name();					
			}				
		}
		return $order_array;
	}
	
	/**
	 * Code for initialize default value for customizer
	*/
	public function get_defaults( $key ) {
		
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
			'track_button_Text' => __( 'Track your order', 'trackship-for-woocommerce' ),
			'track_button_font_size' => 16,
			'track_button_color' => '#3c4758',
			'track_button_text_color' => '#fff',
			'track_button_border_radius' => 3,
			'widget_padding' => 15,
		);
		
		return isset ( $customizer_defaults[ $key ] ) ? $customizer_defaults[ $key ] : null;

	}
	
	/*
	* get customizer settings
	*/
	public function get_value( $array, $key ) {
		$array_data = get_option( $array );
		return ( isset( $array_data[$key] ) && !empty( $array_data[$key] ) ) ? $array_data[$key] : $this->get_defaults( $key );
	}
	
	/*
	* Return checkbox option value for customizer
	*/
	public function get_checkbox_option_value_from_array( $array, $key, $default_value) {		
		$array_data = get_option($array);	
		$value = '';
		
		if ( isset( $array_data[$key] ) ) {
			$value = $array_data[$key];				
			return $value;
		}							
		if ( '' == $value ) {
			$value = $default_value;
		}		
		return $value;
	}
	
}

/**
 * Returns an instance of WC_Trackship_Customizer.
 *
 * @since 1.0.0
 * @version 1.0.0
 *
 * @return WC_Trackship_Customizer
*/
function trackship_customizer() {
	static $instance;

	if ( ! isset( $instance ) ) {		
		$instance = new WC_Trackship_Customizer();
	}

	return $instance;
}

/**
 * Register this class globally.
 *
 * Backward compatibility.
*/
trackship_customizer();

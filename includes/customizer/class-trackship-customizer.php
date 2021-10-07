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
				'title' => __( 'Tracking Page Widget', 'trackship-for-woocommerce' ),
				'description' => '',
				'priority' => 1,
			)
		);
		
		$wp_customize->add_panel( 'trackship_shipment_status_email_panel',
			array(
				'title' => __( 'Email Notifications', 'trackship-for-woocommerce' ),
				'description' => '',
				'priority' => 2,
			)
		);
		
		$wp_customize->add_section( 'trackship_shipment_status_email_widget',
			array(
				'title' => __( 'Tracking Widget', 'trackship-for-woocommerce' ),
				'description' => '',	
				'panel' => 'trackship_shipment_status_email_panel',	
				'priority' => 1,	
			)
		);
		
		$wp_customize->add_section( 'trackship_shipment_status_email',
			array(
				'title' => __( 'Email Type & Text', 'trackship-for-woocommerce' ),
				'description' => '',	
				'panel' => 'trackship_shipment_status_email_panel',			
				'priority' => 2,
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
				'email_customizer_title'					=> 'Email Notifications',
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
				
		if ( 'ast_tracking_page_section' === $key || 'trackship_shipment_status_email' === $key || 'trackship_shipment_status_email_widget' === $key ) {
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
	
	/**
	 * Get WooCommerce order for preview
	 *	 
	 * @param string $order_status
	 * @return object
	 */
	public function get_wc_order_for_preview( $order_status = null, $order_id = null ) {
		if ( ! empty( $order_id ) && 'mockup' != $order_id ) {
			return wc_get_order( $order_id );
		} else {			

			// Instantiate order object
			$order = new WC_Order();			
			
			// Other order properties
			$order->set_props( array(
				'id'                 => 1,
				'status'             => ( null === $order_status ? 'processing' : $order_status ),
				'shipping_first_name' => 'Sherlock',
				'shipping_last_name'  => 'Holmes',
				'shipping_company'    => 'Detectives Ltd.',
				'shipping_address_1'  => '221B Baker Street',
				'shipping_city'       => 'London',
				'shipping_postcode'   => 'NW1 6XE',
				'shipping_country'    => 'GB',
				'billing_first_name' => 'Sherlock',
				'billing_last_name'  => 'Holmes',
				'billing_company'    => 'Detectives Ltd.',
				'billing_address_1'  => '221B Baker Street',
				'billing_city'       => 'London',
				'billing_postcode'   => 'NW1 6XE',
				'billing_country'    => 'GB',
				'billing_email'      => 'sherlock@holmes.co.uk',
				'billing_phone'      => '02079304832',
				'date_created'       => gmdate( 'Y-m-d H:i:s' ),
				'total'              => 24.90,				
			) );

			// Item #1
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'     => 'A Study in Scarlet',
				'subtotal' => '9.95',
				'sku'      => 'kwd_ex_1',
			) );
			$order->add_item( $order_item );

			// Item #2
			$order_item = new WC_Order_Item_Product();
			$order_item->set_props( array(
				'name'     => 'The Hound of the Baskervilles',
				'subtotal' => '14.95',
				'sku'      => 'kwd_ex_2',
			) );
			$order->add_item( $order_item );						

			// Return mockup order
			return $order;
		}
	}
	
	public function get_wc_shipment_status_for_preview( $status = 'in_transit' ) {
		$shipment_status = array();
		$shipment_status[] = array(
			'status_date' => '2021-07-27 15:28:02',
			'est_delivery_date' => '',
			'status' => $status,
			'tracking_events' => array(),
			'tracking_page' => '',
		);
		return $shipment_status;
	}
	
	public function get_tracking_items_for_preview() {
		$tracking_items = array();
		$tracking_items[] = array(
			'tracking_provider' => 'usps',
			'tracking_number' => '4208001392612927',
			'formatted_tracking_provider' => 'USPS',			
		);
		return $tracking_items;
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

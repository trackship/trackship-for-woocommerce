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
class TSWC_Late_Shipments_Customizer_Email {
	// Get our default values	
	public function __construct() {
		// Get our Customizer defaults
		$this->defaults = $this->wcast_generate_defaults();
		
		$wc_ast_api_key = get_option('wc_ast_api_key');
		if (!$wc_ast_api_key) {
			return;
		}	
	}		
	
	/**
	 * Code for initialize default value for customizer
	*/
	public function wcast_generate_defaults() {		
		$customizer_defaults = array(			
			'wcast_late_shipments_email_subject' => 'Late shipment for order #{order_number}',
			'wcast_late_shipments_email_heading' => __( 'Late shipment', 'trackship-for-woocommerce' ),
			'wcast_late_shipments_email_content' => 'This order was shipped {shipment_length} days ago, the shipment status is {shipment_status} and its est. delivery date is {est_delivery_date}.',
			'wcast_enable_late_shipments_admin_email'  => '',
			'wcast_late_shipments_days' => 7,
			'wcast_late_shipments_email_to'  => '{admin_email}',
			'wcast_late_shipments_show_tracking_details' => '',
			'wcast_late_shipments_show_order_details' => '',
			'wcast_late_shipments_show_billing_address' => '',
			'wcast_late_shipments_show_shipping_address' => '',
			'wcast_late_shipments_email_code_block' => '',
		);

		return apply_filters( 'ast_customizer_defaults', $customizer_defaults );
	}			
}
/**
 * Initialise our Customizer settings
*/
new TSWC_Late_Shipments_Customizer_Email();

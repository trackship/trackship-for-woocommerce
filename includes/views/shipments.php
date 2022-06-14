<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$nonce = wp_create_nonce( 'wc_ast_tools');
?>
<input type="hidden" id="wc_ast_dashboard_tab" name="wc_ast_dashboard_tab" value="<?php echo esc_attr( $nonce ); ?>" />
<?php
$ship_status = array(
	'active'				=> __( 'All Shipments', 'trackship-for-woocommerce' ),
	'in_transit'			=> __( 'In Transit', 'trackship-for-woocommerce' ),
	'out_for_delivery'		=> __( 'Out For Delivery', 'trackship-for-woocommerce' ),
	'pre_transit'			=> __( 'Pre Transit', 'trackship-for-woocommerce' ),
	'exception'				=> __( 'Exception', 'trackship-for-woocommerce' ),
	'delivered'				=> __( 'Delivered', 'trackship-for-woocommerce' ),
	'return_to_sender'		=> __( 'Return To Sender', 'trackship-for-woocommerce' ),
	'available_for_pickup'	=> __( 'Available For Pickup', 'trackship-for-woocommerce' ),
	'late_shipment'			=> __( 'Late Shipments', 'trackship-for-woocommerce' ),
	'tracking_issues'		=> __( 'Tracking Issues', 'trackship-for-woocommerce' ),
);
?>
<div>
	<span class="shipment_status">
		<select class="select_option" name="shipment_status" id="shipment_status">
			<?php foreach ( $ship_status as $key => $val ) { ?>
				<option value="<?php echo esc_html( $key ); ?>"><?php echo esc_html( $val ); ?></option>
			<?php } ?>
		</select>
	</span>
	<?php
	global $wpdb;
	$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';
	$all_providers = $wpdb->get_results( "SELECT shipping_provider FROM {$woo_trackship_shipment} WHERE shipping_provider NOT LIKE ( '%NULL%') GROUP BY shipping_provider" );
	?>
	<span class="shipping_provider">
		<select class="select_option" name="shipping_provider" id="shipping_provider">
			<option value="all"><?php esc_html_e( 'All shipping providers', 'trackship-for-woocommerce' ); ?></option>
			<?php foreach ( $all_providers as $provider ) { ?>
				<?php $formatted_provider = trackship_for_woocommerce()->actions->get_provider_name( $provider->shipping_provider ); ?>
				<?php $provider_name = isset($formatted_provider) && $formatted_provider ? $formatted_provider : $provider->shipping_provider; ?>
				<option value="<?php echo esc_html( $provider->shipping_provider ); ?>"><?php echo esc_html( $provider_name ); ?></option>
		<?php } ?>
		</select>
	</span>
	<button class="serch_button" type="button"><?php esc_html_e( 'Search', 'trackship-for-woocommerce' ); ?></button>
	<span class="shipment_search_bar">
		<input type="text" id="search_bar" name="search_bar" placeholder="">
		<span class="dashicons dashicons-no"></span>
	</span>  
</div>
<?php require_once( trackship_for_woocommerce()->get_plugin_path() . '/includes/shipments/views/trackship_shipments.php' ); ?>

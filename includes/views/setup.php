<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="setup_tab">
	<div class="p15 inner_div">
		<h4 class="setup_heading"><?php esc_html_e( 'WooCommerce Fulfillments', 'trackship-for-woocommerce' ); ?></h4>
		<div class="p15 inner_div before_fulfillments">
			<h4 class="setup_heading"><?php echo '🔗 '; ?><?php esc_html_e( 'TrackShip is compatible with WooCommerce Fulfillments', 'trackship-for-woocommerce' ); ?></h4>
			<span class="setup_span"><?php esc_html_e( 'Fulfillments let TrackShip read shipment data natively and keep tracking in sync.', 'trackship-for-woocommerce' ); ?></span>
			<div><a class="setup_docs_link" href="https://docs.trackship.com/docs/trackship-for-woocommerce/compatibility/#shipment-tracking" target="_blank"><?php esc_html_e( 'Learn more →', 'trackship-for-woocommerce' ); ?></a></div>
		</div>
		<div class="p15 inner_div mb0 before_fulfillments">
			<h4 class="setup_heading"><?php esc_html_e( 'Enable WooCommerce Fulfillments', 'trackship-for-woocommerce' ); ?></h4>
			<span class="setup_span"><?php esc_html_e( "We'll enable Fulfillments for you. This step can't be reverted from TrackShip (WooCommerce controls it).", 'trackship-for-woocommerce' ); ?></span>
			<span class="setup_span setup_warning">
				<?php esc_html_e( 'Heads up: This is a one-way step. You must enable Fulfillments to continue.', 'trackship-for-woocommerce' ); ?>
			</span>
			<div><button class="button-primary button-trackship ts_enable_fulfillments"><?php esc_html_e( 'Enable Fulfillments', 'trackship-for-woocommerce' ); ?></button></div>
		</div>
		<div class="p15 inner_div mb0 after_fulfillments" style="display: none;">
			<h4 class="setup_heading"><?php echo '✅ '; ?><?php esc_html_e( 'WooCommerce Fulfillments enabled', 'trackship-for-woocommerce' ); ?></h4>
			<span class="setup_span" style="padding-bottom: 0;"><?php esc_html_e( 'TrackShip will now sync fulfillment-based shipments automatically.', 'trackship-for-woocommerce' ); ?></span>
		</div>
	</div>
</div>
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$is_fulfillments = trackship_for_woocommerce()->is_active_fulfillments();
?>
<div class="setup_tab">

	<div class="ts-setup-card before_fulfillments" <?php echo $is_fulfillments ? 'style="display:none;"' : ''; ?>>
		<div class="ts-setup-card__icon">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13" rx="2"/><path d="M16 8h4l3 5v3h-7V8z"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
		</div>
		<div class="ts-setup-card__body">
			<h4 class="ts-setup-card__title"><?php esc_html_e( 'WooCommerce Fulfillments', 'trackship-for-woocommerce' ); ?></h4>
			<p class="ts-setup-card__desc"><?php esc_html_e( 'Fulfillments let TrackShip read shipment data natively from WooCommerce and keep tracking in sync automatically — no extra configuration needed.', 'trackship-for-woocommerce' ); ?></p>
			<div class="ts-setup-card__actions">
				<button class="button-primary button-trackship ts_enable_fulfillments"><?php esc_html_e( 'Enable Fulfillments', 'trackship-for-woocommerce' ); ?></button>
				<a class="ts-setup-card__link" href="https://docs.trackship.com/docs/trackship-for-woocommerce/compatibility/#shipment-tracking" target="_blank"><?php esc_html_e( 'Learn more →', 'trackship-for-woocommerce' ); ?></a>
			</div>
		</div>
	</div>

	<div class="ts-setup-card ts-setup-card--enabled after_fulfillments" <?php echo $is_fulfillments ? '' : 'style="display:none;"'; ?>>
		<div class="ts-setup-card__icon ts-setup-card__icon--green">
			<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
		</div>
		<div class="ts-setup-card__body">
			<h4 class="ts-setup-card__title"><?php esc_html_e( 'WooCommerce Fulfillments Enabled', 'trackship-for-woocommerce' ); ?></h4>
			<p class="ts-setup-card__desc"><?php esc_html_e( 'TrackShip is syncing fulfillment-based shipments automatically. All new fulfillments will be tracked in real time.', 'trackship-for-woocommerce' ); ?></p>
			<div class="ts-setup-card__actions">
				<button class="button ts_disable_fulfillments"><?php esc_html_e( 'Disable Fulfillments', 'trackship-for-woocommerce' ); ?></button>
				<a class="ts-setup-card__link" href="https://docs.trackship.com/docs/trackship-for-woocommerce/compatibility/#shipment-tracking" target="_blank"><?php esc_html_e( 'Learn more →', 'trackship-for-woocommerce' ); ?></a>
			</div>
		</div>
	</div>

</div>

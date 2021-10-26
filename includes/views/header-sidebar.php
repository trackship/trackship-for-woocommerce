<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="woocommerce-layout__activity-panel-tabs">
	<button type="button" id="activity-panel-tab-help" class="components-button woocommerce-layout__activity-panel-tab"><span class="dashicons dashicons-menu"></span></button>
</div>
<div class="woocommerce-layout__activity-panel-wrapper">
	<div class="woocommerce-layout__activity-panel-content" id="activity-panel-true">
		<div class="woocommerce-layout__activity-panel-header">
			<div class="woocommerce-layout__inbox-title">
				<p class="css-activity-panel-Text"><?php esc_html_e( 'Documentation', 'trackship-for-woocommerce' ); ?></p>
			</div>
		</div>
		<div>
			<ul class="woocommerce-list woocommerce-quick-links__list">
				<?php foreach ( $menu_items as $item ) { ?>
					<li class="woocommerce-list__item has-action">
						<a href="<?php echo esc_url( $item['link'] ); ?>" class="woocommerce-list__item-inner" target="_blank">
							<div class="woocommerce-list__item-before">
								<img class="ts4wc_help_logo" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/<?php echo esc_html( $item['image'] ); ?>">
							</div>
							<div class="woocommerce-list__item-text">
								<span class="woocommerce-list__item-title">
									<div class="woocommerce-list-Text">
										<?php esc_html_e( $item['label'] ); ?>
									</div>
								</span>
							</div>
							<div class="woocommerce-list__item-after"> <span class="dashicons dashicons-arrow-right-alt2"></span> </div>
						</a>
					</li>
				<?php } ?>
				<li class="woocommerce-list__item has-action ts4wc_version">
					<div class="woocommerce-list__item-text" style="padding: 16px 24px;">
						<span class="woocommerce-list__item-title">
							<div class="woocommerce-list-Text">
								<?php esc_html_e( 'TrackShip for WooCommerce Version - ' . $version ); ?>
							</div>
						</span>
					</div>
				</li>
			</ul>
		</div>
	</div>
</div>

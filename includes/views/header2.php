<?php 
$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
$tittle = 'trackship-shipments' == $page ? __( 'Shipments', 'trackship-for-woocommerce' ) : __( 'Dashboard', 'trackship-for-woocommerce' );
$version = trackship_for_woocommerce()->version;
$menu_items = array(
	array(
		'label' => __( 'Get Support', 'trackship-for-woocommerce' ),
		'link' => 'https://trackship.info/support/?support=1',
		'image' => 'get-support-icon.svg',
	),
	array(
		'label' =>__( 'Documentation', 'trackship-for-woocommerce' ),
		'link' => 'https://trackship.info/documentation/',
		'image' => 'documentation-icon.svg',
	),
);
?> 
<div class="zorem-layout__header" style="height:60px; padding:15px;">
	<div>
		<span style="font-size:15px"><span style="color:#3858e9;"><?php esc_html_e( 'Fulfillment', 'trackship-for-woocommerce' ); ?></span> > <?php echo esc_html($tittle); ?></span>
	</div>
	<div style="float:right;">
		<h1 class="zorem-layout__header-breadcrumbs"><img class="ts4wc_logo_header" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png"></h1>
	</div>
</div>
<div class="fullfillment_header">
	<h2 class="fullfillment_header_h2"><?php echo esc_html($tittle); ?></h2>
	<span class="woocommerce-layout__activity-panel">
		<div class="woocommerce-layout__activity-panel-tabs">
			<button type="button" id="activity-panel-tab-help" class="components-button woocommerce-layout__activity-panel-tab"> <span class="dashicons dashicons-menu"></span></button>
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
	</span>
</div>
<style>
.woocommerce-layout__activity-panel-tabs {
	height: 50px;
}
.woocommerce-layout__activity-panel-tabs .woocommerce-layout__activity-panel-tab {
	height: 50px;
	background: transparent;
}
.woocommerce-layout__activity-panel {
	display: contents;
}
</style>

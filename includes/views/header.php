<?php
$version = trackship_for_woocommerce()->version;
$menu_items = array(
	array(
		'label' => __( 'Get Support', 'trackship-for-woocommerce' ),
		'link' => 'https://trackship.info/support/?support=1',
	),
	array(
		'label' =>__( 'Documentation', 'trackship-for-woocommerce' ),
		'link' => 'https://trackship.info/documentation/',
	),
);
?>
<div class="zorem-layout__header">
	<h1 class="zorem-layout__header-breadcrumbs"><img class="ts4wc_logo_header" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png"></h1>
	<div class="woocommerce-layout__activity-panel">
		<div class="woocommerce-layout__activity-panel-tabs">
			<button type="button" id="activity-panel-tab-help" class="components-button woocommerce-layout__activity-panel-tab"> <span class="dashicons dashicons-editor-help"></span><?php esc_html_e( 'Help', 'trackship-for-woocommerce' ); ?></button>
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
									<div class="woocommerce-list__item-before"> <span class="dashicons dashicons-media-document"></span> </div>
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
                            <div class="woocommerce-list__item-text">
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
	</div>
</div>
<style>
.zorem-layout__header * {
	box-sizing: border-box;
}
.woocommerce-layout__activity-panel-tabs {
	width: 100%;
	display: flex;
	height: 60px;
	justify-content: flex-end;
}
.woocommerce-layout__activity-panel-tabs .dashicons{
	width: 24px;
	height: 24px;
	font-size: 24px;
	color: #59c889;
}
.woocommerce-layout__activity-panel-tabs .woocommerce-layout__activity-panel-tab {
	display: flex;
	flex-direction: column;
	justify-content: center;
	align-items: center;
	position: relative;
	border: none;
	outline: none;
	cursor: pointer;
	background-color: #fff;
	max-width: -webkit-min-content;
	max-width: min-content;
	min-width: 80px;
	width: 100%;
	height: 60px;
	color: #757575;
	white-space: nowrap;
}
.woocommerce-layout__activity-panel-tabs .woocommerce-layout__activity-panel-tab:before {
	background-color: #005b9a;
	bottom: 0;
	content: "";
	height: 0;
	opacity: 0;
	transition-property: height,opacity;
	transition-duration: .3s;
	transition-timing-function: ease-in-out;
	left: 0;
	position: absolute;
	right: 0;
}
.woocommerce-layout__activity-panel-tabs .woocommerce-layout__activity-panel-tab:hover {
	background-color: #f0f0f0;
	box-shadow: none;
}
.woocommerce-layout__activity-panel-tabs .woocommerce-layout__activity-panel-tab.is-active{
	color: #1e1e1e;
	box-shadow: none;
}
.woocommerce-layout__activity-panel-tabs .woocommerce-layout__activity-panel-tab.is-active:before{
	height: 3px;
	opacity: 1;
}
.woocommerce-layout__activity-panel-wrapper {
	height: calc(100vh - 166px);
	background: #f0f0f0;
	width: 510px;
	transform: translateX(100%);
	transition-property: transform box-shadow;
	transition-duration: .3s;
	transition-timing-function: ease-in-out;
	position: fixed;
	right: 0;
	top: 166px;
	z-index: 1000;
	overflow-x: hidden;
	overflow-y: auto;
}

.woocommerce-layout__activity-panel-wrapper.is-open {
	transform: none;
	box-shadow: 0 12px 12px 0 rgb(85 93 102 / 30%);
}
.woocommerce-layout__activity-panel-wrapper.is-switching {
	animation: tabSwitch;
	animation-duration: .3s;
}
.woocommerce-layout__activity-panel-header {
	height: 50px;
	background: #e0e0e0;
	padding: 16px;
	display: flex;
	justify-content: space-between;
	align-items: center;
}
.woocommerce-layout__inbox-title {
	color: #1e1e1e;
	display: flex;
	align-items: center;
}
.css-activity-panel-Text {
	box-sizing: border-box;
	margin: 0px;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	font-weight: 400;
	font-size: 20px;
	line-height: 28px;
}
.woocommerce-list {
	margin: 0;
	padding: 0;
}
.woocommerce-list__item {
	display: flex;
	align-items: center;
	margin-bottom: 0;
	background-color: #fff;
}
.woocommerce-list__item:not(:first-child) {
	border-top: 1px solid #f0f0f0;
}
.woocommerce-list__item.has-action {
	cursor: pointer;
}
.woocommerce-list__item > .woocommerce-list__item-inner {
	text-decoration: none;
	width: 100%;
	display: flex;
	align-items: center;
	padding: 16px 24px;
}
.woocommerce-list__item .woocommerce-list__item-before {
	margin-right: 20px;
	display: flex;
	align-items: center;
}
.woocommerce-list__item .woocommerce-list__item-title {
	color: #005b9a;
}
.woocommerce-list-Text {
	box-sizing: border-box;
	margin: 0px;
	font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
	font-weight: 600;
	font-size: 14px;
	line-height: 20px;
}
.woocommerce-list__item .woocommerce-list__item-after {
	margin-left: 16px;
	display: flex;
	align-items: center;
	margin-left: auto;
}
.woocommerce-list__item.has-action.ts4wc_version .woocommerce-list__item-text {
	padding: 16px 24px;
}
@media (min-width: 783px) {
	.woocommerce-layout__activity-panel-wrapper {
		height: calc(100vh - 92px);
		top: 92px;
	}
	.woocommerce-layout__activity-panel-header {
		padding: 16px 24px;
	}
}
</style>
<script>
jQuery( document ).on( "click", "#activity-panel-tab-help", function() {
	jQuery(this).addClass( 'is-active' );
	jQuery( '.woocommerce-layout__activity-panel-wrapper' ).addClass( 'is-open is-switching' );
});

jQuery(document).click(function(){
	var $trigger = jQuery(".woocommerce-layout__activity-panel");
	if($trigger !== event.target && !$trigger.has(event.target).length){
		jQuery('#activity-panel-tab-help').removeClass( 'is-active' );
		jQuery( '.woocommerce-layout__activity-panel-wrapper' ).removeClass( 'is-open is-switching' );
	}   
});
</script> 

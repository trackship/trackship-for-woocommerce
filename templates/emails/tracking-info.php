<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TrackShip for WooCommerce
 *
 * Shows tracking information in the HTML Shipment status email
 *
 * @package trackship-for-woocommerce/templates/email
 * @version 1.0
 */
if ( $tracking_items ) : 
	$track_button_Text = trackship_admin_customizer()->get_value( 'shipment_email_settings', 'track_button_Text' );
	$tracking_page_layout = trackship_admin_customizer()->get_value( 'shipment_email_settings', 'tracking_page_layout' );
	$text_align = is_rtl() ? 'right' : 'left';
	$border_color = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'shipment_email_settings', 'border_color', '#e8e8e8');
	$link_color = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'shipment_email_settings', 'link_color', '');
	$background_color = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'shipment_email_settings', 'bg_color', '#fff');
	$font_color = trackship_for_woocommerce()->ts_actions->get_option_value_from_array( 'shipment_email_settings', 'font_color', '#333');
	?>
	<div class="tracking_info">
		<div class="tracking_list">
			<?php foreach ( $tracking_items as $key => $tracking_item ) { ?>
				<?php
				$ship_status = $new_status;
				$tracking_link = isset( $shipment_status[ $key ][ 'tracking_page' ] ) && get_option( 'wc_ast_use_tracking_page', 1 ) ? $shipment_status[ $key ][ 'tracking_page' ] : $tracking_item[ 'formatted_tracking_link' ];

				do_action( 'before_tracking_widget_email', $tracking_item, $order_id );
				?>
				<div class="tracking_index display-table">
					<div style="display: table;width: 100%;">
						<div class="display-table-cell v-align-top" >
							<p style="margin-bottom:0;">
								<?php 
								if ( $ship_status ) {
									if ( in_array( $ship_status, array( 'pending_trackship', 'pending', 'carrier_unsupported', 'unknown' ) ) ) {
										echo '<span class="shipment_status shipped" >';
										esc_html_e( 'Shipped', 'trackship-for-woocommerce' );
										echo '</span>';
									} else {
										?>
										<p style="margin: 5px 0 0;"><span class="tracking_info"><?php echo esc_html( $tracking_item['formatted_tracking_provider'] ); ?> <a href="<?php echo esc_url( $tracking_link ); ?>" style="text-decoration:none"><?php echo esc_html( $tracking_item['tracking_number'] ); ?></a></span></p>
										<div class="shipment_status <?php echo esc_html( $ship_status ); ?>">
											<?php
											echo '<span class="' . esc_html( $ship_status ) . '">';
											esc_html_e( apply_filters( 'trackship_status_filter', $ship_status ) );
											echo '</span>';
											?>
										</div>
										<?php
									}
								}
								$show_est_delivery_date = apply_filters( 'show_est_delivery_date', true, $tracking_item['formatted_tracking_provider'] );
								$est_delivery_date = isset( $shipment_status[$key]['est_delivery_date'] ) ? $shipment_status[$key]['est_delivery_date'] : false;
								if ( $est_delivery_date && $show_est_delivery_date ) {
									echo '<p style="margin: 0;"><span class="est_delivery_date">';
									esc_html_e( 'Est. Delivery Date', 'trackship-for-woocommerce' );
									echo ': <b>';
									echo esc_html( date_i18n( 'l, M d', strtotime( $est_delivery_date ) ) );
									echo '</b>';
									echo '</span></p>';
								}
								?>
							</p>
							<?php if ( 'shipped' != $ship_status ) { ?>
								<?php if ( in_array( $tracking_page_layout, array( 't_layout_1', 't_layout_3', 't_layout_2' ) ) ) { ?>
									<?php $icon_layout = 't_layout_1' == $tracking_page_layout ? '-widget.png' : '-widget-v2.png'; ?>
									<?php $icon_layout = 't_layout_2' == $tracking_page_layout ? '-widget-v4.png' : $icon_layout; ?>
									<div class="widget_progress_bar mobile_version" style="width:100%;margin: 15px 0 10px;">
										<?php $widget_icon_url = trackship_for_woocommerce()->plugin_dir_url() . 'assets/images/widget-icon/' . esc_html( $ship_status ) . esc_html( $icon_layout ); ?>
										<img style="width:100%;" src="<?php echo esc_url( $widget_icon_url ); ?>">
									</div>
								<?php } else { ?>
									<div class="tracker-progress-bar mobile_version <?php echo esc_html( $tracking_page_layout ); ?>">
										<div class="progress">
											<div class="progress-bar <?php echo esc_html( $ship_status ); ?>" ></div>
										</div>
									</div>
								<?php } ?>
							<?php } ?>
						</div>
						<div class="display-table-cell" >
							<?php if ( 'delivered' != $ship_status ) { ?>
								<a href="<?php echo esc_url( $tracking_link ); ?>" class="track_your_order"><?php esc_html_e( $track_button_Text ); ?></a>
							<?php } ?>
						</div>
					</div>
					<div style="display:block;"></div>
					<?php if ( 'shipped' != $ship_status ) { ?>
						<?php if ( in_array( $tracking_page_layout, array( 't_layout_1', 't_layout_3', 't_layout_2' ) ) ) { ?>
							<?php $icon_layout = 't_layout_1' == $tracking_page_layout ? '-widget.png' : '-widget-v2.png'; ?>
							<?php $icon_layout = 't_layout_2' == $tracking_page_layout ? '-widget-v4.png' : $icon_layout; ?>
							<div class="widget_progress_bar desktop_version" style="width:100%;margin: 15px 0 10px;">
								<?php $widget_icon_url = trackship_for_woocommerce()->plugin_dir_url() . 'assets/images/widget-icon/' . esc_html( $ship_status ) . esc_html( $icon_layout ); ?>
								<img style="width:100%;" src="<?php echo esc_url( $widget_icon_url ); ?>">
							</div>
						<?php } else { ?>
							<div class="tracker-progress-bar desktop_version <?php echo esc_html( $tracking_page_layout ); ?>">
								<div class="progress">
									<div class="progress-bar <?php echo esc_html( $ship_status ); ?>" ></div>
								</div>
							</div>
						<?php } ?>
					<?php } ?>
				</div>
			<?php } ?>
		</div>
	</div>
	<style>
	<?php if ( $link_color ) { ?>
		div.tracking_index.display-table .tracking_info a { color: <?php echo esc_html( $link_color ); ?>!important; }
	<?php } ?>
	#ts-email-widget-wrapper{max-width: 500px;margin: 50px auto;font-family: "Helvetica Neue", Helvetica, Roboto, Arial, sans-serif;font-size: 14px;line-height: 150%;}
	.tracker-progress-bar .progress {
		background-color: #f5f5f5;
		margin-top: 10px;
		border-radius: 5px;
		border: 1px solid #eee;
		overflow: hidden;
	}
	/*.progress-bar.out_for_delivery{background-color: #90ca5e;width:67%;height:40px;}
	.progress-bar.in_transit{background-color: #53c3bd;width:33%;height:40px;}
	.progress-bar.failure{background-color: #cd2128;width:33%;height:40px;}
	.progress-bar.return_to_sender{background-color: #cd2128;width:67%;height:40px;}
	.progress-bar.exception{background-color: #cd2128;width:66%;height:40px;}
	.progress-bar.on_hold{background-color: #ffde00;width:33%;height:40px;}
	.progress-bar.available_for_pickup{background-color: #f49d1d;width:67%;height:40px;}
	.progress-bar.delivered{background-color: #0f8042;width:100%;height:40px;}*/
	ul.tracking_list{padding: 0;list-style: none;}
	ul.tracking_list .tracking_list_li{margin-bottom: 5px;}
	ul.tracking_list .tracking_list_li .product_list_ul{padding-left: 10px;}
	ul.tracking_list .tracking_list_li .tracking_list_div{border-bottom:1px solid #e0e0e0;}
	.tracking_index {
		border: 1px solid #cccccc;
		margin-bottom: 20px;
		padding: <?php echo esc_html( trackship_admin_customizer()->get_value( 'shipment_email_settings', 'widget_padding' ) ); ?>px;
		background: <?php echo esc_html( $background_color ); ?>;
		display:block;
		color: <?php echo esc_html( $font_color ); ?>;
		border-color: <?php echo esc_html( $border_color ); ?>;
	}
	a.track_your_order {
		border-radius: <?php echo esc_html( trackship_admin_customizer()->get_value( 'shipment_email_settings', 'track_button_border_radius' ) ); ?>px;
		text-decoration: none;
		color: <?php echo esc_html( trackship_admin_customizer()->get_value( 'shipment_email_settings', 'track_button_text_color' ) ); ?>;
		background: <?php echo esc_html( trackship_admin_customizer()->get_value( 'shipment_email_settings', 'track_button_color' ) ); ?>;
		display: block;text-align: center;
		<?php echo 20 == trackship_admin_customizer()->get_value( 'shipment_email_settings', 'track_button_font_size' ) ? 'padding: 12px 20px;' : 'padding: 10px 15px;'; ?>
	}
	.shipment_status {font-size: 24px;margin: 10px 0;display: inline-block;color: #333;vertical-align: middle;font-weight:500;}
	.mb-0{margin:0;}
	.v-align-top{vertical-align:top;}
	span.est_delivery_date { margin-top: 5px; display: inline-block; }
	@media screen and (max-width: 460px) {
		.desktop_version{display:none;}
		.mobile_version,.display-table{display:block;}
		.display-table-cell{display:block;margin-top: 10px;}
		.track_your_order{display: block !important;text-align: center;}
	}
	@media screen and (min-width: 461px) {
		.display-table{display:table !important;width:100%;box-sizing: border-box;}
		.display-table-cell{display:table-cell;}
		.track_your_order{float: right;display:inline-block;}
		.mobile_version{display:none;}
		.desktop_version{display:block;}
	}
</style>

<?php
endif;

/*
*
*/
do_action( 'after_tracking_widget_email', $order_id, $new_status );

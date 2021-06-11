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
	$track_button_Text = trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_Text' );
	$text_align = is_rtl() ? 'right' : 'left'; 
	?>
	<h2>Tracking widget</h2>
	<div class="tracking_info">
		<div class="tracking_list">
			<?php foreach ( $tracking_items as $key => $tracking_item ) { ?>
				<?php
					//$ship_status = isset( $shipment_status[ $key ][ 'status' ] ) ? $shipment_status[ $key ][ 'status' ] : false;
					$ship_status = $new_status;
					$tracking_link = isset( $shipment_status[ $key ][ 'tracking_page' ] ) && get_option( 'wc_ast_use_tracking_page', 1 ) ? $shipment_status[ $key ][ 'tracking_page' ] : $tracking_item[ 'formatted_tracking_link' ];
					do_action( 'before_tracking_widget_email', $tracking_item, $order_id );
				?>
				<div class="tracking_index display-table">
					<div class="display-table-cell v-align-top" >
						<p style="margin-bottom:0;">
							<?php 
							if ( $ship_status ) {
								if ( in_array( $ship_status, array( 'pending_trackship', 'pending', 'carrier_unsupported', 'unknown' ) ) ) {
									echo '<span class="shipment_status shipped" >';
										esc_html_e( 'Shipped' );
									echo '</span>';
								} else {
									$icon_url = trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/icons/' . esc_html( $ship_status ) . '-o.png';
									if ( $ship_status == 'exception' ) {
										$icon_url = trackship_for_woocommerce()->plugin_dir_url() . 'assets/css/icons/failure-o.png';
									}
                                    ?>
                                    <p style="margin: 5px 0 0;"><span class="tracking_info"><?php echo esc_html( $tracking_item['formatted_tracking_provider'] ); ?> <a href="<?php echo esc_url( $tracking_link ); ?>" style="text-decoration:none"><?php echo esc_html( $tracking_item['tracking_number'] ); ?></a></span></p>
                                    <div class="shipment_status <?php echo esc_html( $ship_status ); ?>">
                                        <?php
                                        //echo '<img src="' . $icon_url . '" style="width:20px;">';
										echo '<span class="' . esc_html( $ship_status ) . '">';
                                            esc_html_e( apply_filters( 'trackship_status_filter', $ship_status ) );
                                        echo '</span>'; ?>
									</div>
								<?php }
							}
							
							$est_delivery_date = isset( $shipment_status[$key]['est_delivery_date'] ) ? $shipment_status[$key]['est_delivery_date'] : false;
							if ( $est_delivery_date ) {
								echo '<p style="margin: 0;"><span class="est_delivery_date">';
								echo 'Est. delivery: ' . '<b>' . esc_html( gmdate( 'l, M d', strtotime( $est_delivery_date ) ) ) . '</b>';
								echo '</span></p>';
							}
							?>
						</p>
                        
					</div>
					<div class="display-table-cell" >
						<?php if ( 'delivered' != $ship_status ) { ?>
							<a href="<?php echo esc_url( $tracking_link ); ?>" class="track_your_order"><?php esc_html_e( $track_button_Text ); ?></a>
						<?php } ?>
					</div>
					<div style="display:block;"></div>
				</div>
			<?php } ?>
		</div>
	</div>
	
	<style>
	ul.tracking_list{padding: 0;list-style: none;}
	ul.tracking_list .tracking_list_li{margin-bottom: 5px;}
	ul.tracking_list .tracking_list_li .product_list_ul{padding-left: 10px;}
	ul.tracking_list .tracking_list_li .tracking_list_div{border-bottom:1px solid #e0e0e0;}
	.tracking_index {border: 1px solid #e0e0e0;margin-bottom: 20px;padding: 20px;background: #fafafa;display:block;}
	a.track_your_order {
		padding: 7px 12px;
		border-radius: 3px;
		font-weight: 600;text-decoration: none;
		color: <?php echo esc_html( trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_text_color' ) ); ?>;
		background: <?php echo esc_html( trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_color' ) ); ?>;
		font-size: <?php echo esc_html( trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_font_size' ) ); ?>px;
		display: block;text-align: center;
	}
	.shipment_status {font-size: 24px;margin: 10px 0 0;display: inline-block;color: #53c3bd;vertical-align: middle;}
	.shipment_status .shipped {color: #03a9f4;}
	.shipment_status .on_hold {color: #ffd700;}
	.shipment_status .return_to_sender {color: #951621;}
	.shipment_status .available_for_pickup {color: #f49d1d;}
	.shipment_status .out_for_delivery {color: #95CB65;}
	.shipment_status .delivered {color: #0F8042;}
	.shipment_status .failure {color: #CD2128;}
	.shipment_status .exception {color: #cd2128;}
	.mb-0{margin:0;}
	.v-align-top{vertical-align:top;}
	span.est_delivery_date { margin-top: 5px; display: inline-block; }
	</style>

<style>
	@media screen and (max-width: 460px) {
		.display-table{display:block;}
		.display-table-cell{display:block;}
		.track_your_order{display: block !important;text-align: center;}
	}
	@media screen and (min-width: 461px) {
		.display-table{display:table !important;width:100%;box-sizing: border-box;}
		.display-table-cell{display:table-cell;}
		.track_your_order{float: right;display:inline-block;}
	}
</style>

<?php
endif;

/*
*
*/
do_action( 'after_tracking_widget_email', $order_id, $new_status );

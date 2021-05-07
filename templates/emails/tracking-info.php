<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Shipment Tracking
 *
 * Shows tracking information in the HTML order email
 *
 * @author  WooThemes
 * @package WooCommerce Shipment Tracking/templates/email
 * @version 1.6.4
 */
if ( $tracking_items ) : 
	$track_button_Text = trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_Text' );
	$text_align = is_rtl() ? 'right' : 'left'; 
	?>
    <div class="tracking_info">
		<div class="tracking_list">
        	<?php foreach ( $tracking_items as $key => $tracking_item ) { ?>
            	<?php
					$status = isset( $shipment_status[ $key ][ 'status' ] ) ? $shipment_status[ $key ][ 'status' ] : false;
					$tracking_link = isset( $shipment_status[ $key ][ 'tracking_page' ] ) ? $shipment_status[ $key ][ 'tracking_page' ] : $tracking_item[ 'formatted_tracking_link' ];
					do_action( 'before_tracking_widget_email', $tracking_item, $order_id );
                ?>
            	<div class="tracking_index display-table">
                	<div class="display-table-cell v-align-top" >
                    	<span class="tracking_info"><?php echo $tracking_item['formatted_tracking_provider'];?> - <?php echo $tracking_item['tracking_number'];?></span>
                        <p style="margin-bottom:0;">
                            <?php if( $status ) {
							    if ( in_array( $status, array( 'pending_trackship', 'pending', 'carrier_unsupported', 'unknown' ) ) ) {
                                    echo '<span class="shipment_status shipped" >';
										esc_html_e( 'Shipped' );
									echo '</span>';
                                } else {
									$icon_url = trackship_for_woocommerce()->plugin_dir_url() .'/assets/css/icons/' . esc_html( $status ) . '-o.png';
                                    echo '<span class="shipment_status ' . esc_html( $status ) . ' " >';
										esc_html_e( apply_filters( 'trackship_status_filter', $status ) );
									echo '</span>';
                                }
							}
							
                            $est_delivery_date = isset( $shipment_status[$key]['est_delivery_date'] ) ? $shipment_status[$key]['est_delivery_date'] : false;
                            if( $est_delivery_date ){
                                echo '<span class="est_delivery_date">';
                                echo 'Est. delivery: '.date( "l, M d", strtotime( $est_delivery_date ) );
                                echo '</span>';
                            }
                            ?>
                        </p>
                    </div>
                    <div class="display-table-cell" >
						<?php if( $status != 'delivered' ) { ?>
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
		color: <?php echo trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_text_color' )?>;
		background: <?php echo trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_color' )?>;
		font-size: <?php echo trackship_customizer()->get_value( 'shipment_email_settings', 'track_button_font_size' )?>px;
		display: block;text-align: center;margin-top: 10px;
	}
	span.shipment_status {font-size: 24px;margin: 10px 0px 0;display: block;color: #53c3bd;}
	span.shipment_status.shipped {color: #03a9f4;}
	span.shipment_status.on_hold {color: #feeb77;}
	span.shipment_status.return_to_sender {color: #A8414A;}
	span.shipment_status.available_for_pickup {color: #ff9800;}
	span.shipment_status.out_for_delivery {color: #95CB65;}
	span.shipment_status.delivered {color: #0F8042;}
	span.shipment_status.failed_attempt {color: #CD2128;}
	span.shipment_status.exception {color: #cd2128;}
	.mb-0{margin:0;}
	.v-align-top{vertical-align:top;}
	</style>


<style type="text/css">
	.column{float:left;width:100%;}
    @media screen and (max-width: 350px) {
        .three-col .column {
            max-width: 100% !important;
        }
    }
    @media screen and (min-width: 351px) and (max-width: 460px) {
        .three-col .column {
            max-width: 50% !important;
        }
    }
    @media screen and (max-width: 460px) {
        .two-col .column,
        .two-col img {
            max-width: 100% !important;
        }
		.display-table{display:block;}
		.display-table-cell{display:block;}
		.track_your_order{display: block !important;text-align: center;}
    }
    @media screen and (min-width: 461px) {
        .three-col .column {
            max-width: 33.3% !important;
        }
        .two-col .column {
            max-width: 50% !important;
        }
        .sidebar .small {
            max-width: 16% !important;
        }
        .sidebar .large {
            max-width: 84% !important;
        }
		.display-table{display:table !important;width:100%;box-sizing: border-box;}
		.display-table-cell{display:table-cell;}
		.track_your_order{float: right;display:inline-block;}
    }
</style>

<?php
endif;

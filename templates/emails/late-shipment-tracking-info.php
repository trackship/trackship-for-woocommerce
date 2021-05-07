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
    <table border="1" cellspacing="0" cellpadding="6" width="100%" border="1" style="border-color:#e0e0e0;">
    	<?php foreach ( $tracking_items as $key => $tracking_item ) { ?>
        	<?php
				$status = isset( $shipment_status[ $key ][ 'status' ] ) ? $shipment_status[ $key ][ 'status' ] : false;
				$tracking_link = isset( $shipment_status[ $key ][ 'tracking_page' ] ) ? $shipment_status[ $key ][ 'tracking_page' ] : $tracking_item[ 'formatted_tracking_link' ];
            ?>
            <tr>
            	<td><a href="<?php echo get_edit_post_link( $order_id ); ?>"># <?php echo $order_id; ?></a></td>
                <td><?php echo $tracking_item['tracking_number'];?> <br> <?php echo $tracking_item['formatted_tracking_provider']; ?></td>
                <td><?php echo date_i18n( get_option('date_format'), $tracking_item['date_shipped'] ); ?></td>
                <td><?php esc_html_e( apply_filters( 'trackship_status_filter', $status ) ); ?></td>
                <td><a href="<?php echo esc_url( $tracking_link ); ?>" ><?php esc_html_e( 'Track', 'trackship-for-woocommerce' ); ?></a></td>
            </tr>
        <?php } ?>
    </table>
<?php
endif;

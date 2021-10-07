<?php
/**
 * Order details table shown in emails.
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/emails/email-order-details.php.
 *
 * HOWEVER, on occasion WooCommerce will need to update template files and you
 * (the theme developer) will need to copy the new files to your theme to
 * maintain compatibility. We try to do this as little as possible, but it does
 * happen. When this occurs the version of the template file will be bumped and
 * the readme will list any important changes.
 *
 * @see https://docs.woocommerce.com/document/template-structure/
 * @package WooCommerce/Templates/Emails
 * @version 3.3.1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$text_align = is_rtl() ? 'right' : 'left'; 
$margin_side = is_rtl() ? 'left' : 'right';

do_action( 'wcast_email_before_order_table', $order, $sent_to_admin, $plain_text, $email );

$table_font_size = '';
$kt_woomail = get_option( 'kt_woomail' );
if ( !empty($kt_woomail) && isset( $kt_woomail['font_size'] ) ) {
	$table_font_size = 'font-size:' . $kt_woomail['font_size'] . 'px';
}
	?>
	<h2><?php esc_html_e( 'Items in this shipment', 'trackship-for-woocommerce' ); ?></h2>
	<?php foreach ( $tracking_items as $tracking_item ) { ?>		
		<div style="margin-bottom: 20px;">			
			<table class="td" cellspacing="0" cellpadding="6" style="background-color: transparent;width: 100%; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif;border:0;<?php echo esc_html( $table_font_size ); ?>" border="0">
				<tbody>
					<?php 
					if ( isset( $tracking_item['products_list'] ) ) {
						foreach ( $tracking_item['products_list'] as $products_list ) {								
							$product = wc_get_product( $products_list->product ); 
							$sku           = '';
							$purchase_note = '';
							$image         = '';
							$image_size = array( 64, 64 );
						
							if ( is_object( $product ) ) {
								$sku           = $product->get_sku();
								$purchase_note = $product->get_purchase_note();
								$image         = $product->get_image( $image_size );
							}
							?>
							<tr>
								<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;border-left:0;border:0;border-bottom:1px solid #e0e0e0;padding: 12px 5px;width: 70px;">
									<?php echo wp_kses_post( $image ); ?>
								</td>								
								<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;border-left:0;border:0;border-bottom:1px solid #e0e0e0;padding: 12px 5px;">
									<?php
									// Product name.
									echo wp_kses_post( $product->get_name() );
									echo ' x ';
									esc_html_e( $products_list->qty );
									?>
								</td>	
							</tr>	
							<?php
						}
					}					
					?>
				</tbody>
			</table>
		</div>	
	<?php } ?>	
</div>
<?php do_action( 'wcast_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>
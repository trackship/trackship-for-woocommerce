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

$wcast_partial_shipped_customizer_settings = new wcast_partial_shipped_customizer_email();
$wcast_customizer_settings = new wcast_initialise_customizer_settings();
$ast = new WC_Advanced_Shipment_Tracking_Actions();

$display_product_images = $ast->get_checkbox_option_value_from_array( 'woocommerce_customer_partial_shipped_order_settings', 'display_product_images', $wcast_partial_shipped_customizer_settings->defaults['display_product_images'] );

$button_background_color = $ast->get_option_value_from_array( 'tracking_info_settings', 'fluid_button_background_color', $wcast_customizer_settings->defaults['fluid_button_background_color'] );
$button_font_color = $ast->get_option_value_from_array( 'tracking_info_settings', 'fluid_button_font_color', $wcast_customizer_settings->defaults['fluid_button_font_color'] );
$button_font_size = $ast->get_option_value_from_array( 'tracking_info_settings', 'fluid_button_font_size', $wcast_customizer_settings->defaults['fluid_button_font_size'] );
$button_padding = $ast->get_option_value_from_array( 'tracking_info_settings', 'fluid_button_padding', $wcast_customizer_settings->defaults['fluid_button_padding'] );
$button_radius = $ast->get_option_value_from_array( 'tracking_info_settings', 'fluid_button_radius', $wcast_customizer_settings->defaults['fluid_button_radius'] );
?>
<style>
a.button.track-button {
	background: <?php echo esc_html( $button_background_color ); ?>;
	color: <?php echo esc_html( $button_font_color ); ?>;
	padding: <?php echo esc_html( $button_padding ); ?>px;
	text-decoration: none;
	display: inline-block;
	border-radius: <?php echo esc_html( $button_radius ); ?>px;
	margin-top: 0;
	font-size: <?php echo esc_html( $button_font_size ); ?>px;
	text-align: center;
	float: right;
}
</style>
<br>
	<?php 
	$total_trackings = count( $tracking_items );
	$num = 1;
	foreach ( $tracking_items as $tracking_item ) {
		
		if ( $total_trackings > 1) {
			?>
			<p style="margin: 0 0 5px;" class="shipment_heading">
				<?php /* translators: %s: search for number of shipments */ ?>
				<strong><?php printf( esc_html__( 'Shipment %1$s out of %2$s', 'trackship-for-woocommerce' ), esc_html( $num ), esc_html( $total_trackings ) ); ?></strong>
			</p>
		<?php } ?>
		<div>
			<span>
				<?php echo esc_html( $tracking_item['formatted_tracking_provider'] ); ?>
			</span>
			<a style="margin: 0 10px;text-decoration: none;" href='<?php echo esc_url( $tracking_item['formatted_tracking_link'] ); ?>'>
				<span><?php echo esc_html( $tracking_item['tracking_number'] ); ?></span>
			</a>
			<a class="button track-button" href='<?php esc_html( $tracking_item['formatted_tracking_link'] ); ?>'>
				<span><?php esc_html_e( 'Track', 'trackship-for-woocommerce' ); ?></span>
			</a>    
		</div>
			
		<div style="margin:20px 0;">
			<strong style="border-bottom:1px solid #e0e0e0;    display: block;padding-bottom: 5px;"><?php esc_html_e( $shipping_items_heading ); ?></strong>
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
								<?php if ( $display_product_images ) { ?>
									<td class="td" style="text-align:<?php echo esc_attr( $text_align ); ?>; vertical-align: middle; font-family: 'Helvetica Neue', Helvetica, Roboto, Arial, sans-serif; word-wrap:break-word;border-left:0;border:0;border-bottom:1px solid #e0e0e0;padding: 12px 5px;width: 70px;">
										<?php echo wp_kses_post( $image ); ?>
									</td>
								<?php } ?>
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
					$num++;	
					?>
				</tbody>
			</table>
		</div>	
	<?php } ?>	
</div>
<?php do_action( 'wcast_email_after_order_table', $order, $sent_to_admin, $plain_text, $email ); ?>

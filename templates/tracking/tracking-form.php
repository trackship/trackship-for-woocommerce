<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * The template for displaying Tracking Form 
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/tracking/tracking-form.php
 * 
*/
$tracking_page_defaults = trackship_admin_customizer();
$border_color = get_option( 'wc_ast_select_border_color', $tracking_page_defaults->defaults['wc_ast_select_border_color'] );
$link_color = get_option( 'wc_ast_select_link_color', $tracking_page_defaults->defaults['wc_ast_select_link_color'] );
$remove_section = apply_filters( 'remove_order_id_section', false )
?>
<style>
.order_track_form input.ts_from_input:checked + label {
    color: <?php echo esc_html( $link_color ); ?>;
    border-bottom: 3px solid <?php echo esc_html( $link_color ); ?>;
    margin-bottom: -3px;
}
.order_track_form {
    max-width: 500px;
    margin: 0 auto 20px;
    border: 1px solid <?php echo esc_html( $border_color ); ?>;
    min-height: 330px;
}
</style>
<div class="track-order-section">
	<form method="post" class="order_track_form">
		<div class="search_order_form">
			<?php if ( !$remove_section ) { ?>
				<div style="margin: 5px 20px 0;">
					<input id="for_order_number" type="radio" name="ts_tracking_form" class="ts_from_input" data-name="order_id_email" checked>
					<label for="for_order_number" class="ts_from_label"><?php esc_html_e( 'Order Number', 'trackship-for-woocommerce' ); ?></label>
					<input id="for_tracking_number" type="radio" name="ts_tracking_form" class="ts_from_input" data-name="by_tracking_number">
					<label for="for_tracking_number" class="ts_from_label"><?php esc_html_e( 'Tracking Number', 'trackship-for-woocommerce' ); ?></label>
				</div>
				<div class="order_id_email tracking_form">
					<p><?php echo esc_html( apply_filters( 'ast_tracking_page_front_text', __( 'To track your order, enter your order number and email address:', 'trackship-for-woocommerce' ) ) ); ?></p>
					<p class="form-row"><label for="order_id"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_order_label', __( 'Order ID', 'trackship-for-woocommerce' ) ) ); ?></label> <input class="input-text" type="text" name="order_id" id="order_id" value="" placeholder="<?php esc_html_e( 'Order Number', 'trackship-for-woocommerce' ); ?>"></p>
					<p class="form-row"><label for="order_email"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_order_email_label', __( 'Order Email', 'trackship-for-woocommerce' ) ) ); ?></label> <input class="input-text" type="text" name="order_email" id="order_email" value="" placeholder="<?php esc_html_e( 'Email address', 'trackship-for-woocommerce' ); ?>"></p>
					<p class="form-row"  style="margin-bottom:0;"><button type="submit" class="button btn btn-secondary" name="track" value="Track"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_track_label', __( 'Track Order', 'trackship-for-woocommerce' ) ) ); ?></button></p>
				</div>
			<?php } else { ?>
				<style>
					.search_order_form .by_tracking_number.tracking_form { display:block; }
					form.order_track_form {min-height:auto;}
				</style>
			<?php } ?>
			<div class="by_tracking_number tracking_form">
				<p><?php echo esc_html( apply_filters( 'ast_tracking_page_traking_number_front_text', __( 'Enter the tracking number for your order:', 'trackship-for-woocommerce' ) ) ); ?></p>
				<p class="form-row"><label for="order_tracking_number"><?php echo esc_html( apply_filters( 'tracking_page_tracking_number_label', __( 'Tracking Number', 'trackship-for-woocommerce' ) ) ); ?></label><input class="input-text" type="text" name="order_tracking_number" id="order_tracking_number" value="" placeholder="<?php esc_html_e( 'Order tracking number.', 'trackship-for-woocommerce' ); ?>"></p>
				<p class="form-row" style="margin-bottom:0;"><button type="submit" class="button btn btn-secondary" name="track" value="Track"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_track_label', __( 'Track Order', 'trackship-for-woocommerce' ) ) ); ?></button></p>
			</div>

		</div>
		<div class="clear"></div>
		<input type="hidden" name="action" value="get_tracking_info">
		<input type="hidden" name="fronted" value="yes">
		<div class="track_fail_msg" style="display:none;color: red;padding: 0 20px 15px;"></div>
		<?php wp_nonce_field( 'tracking_form' ); ?>
	</form>
</div>

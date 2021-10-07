<?php
/**
 * The template for displaying Tracking Form 
 *
 * This template can be overridden by copying it to yourtheme/woocommerce/tracking/tracking-form.php
 * 
 */
?> 
<div class="track-order-section">
	<form method="post" class="order_track_form">			
		
		<div class="search_order_form" style="width: 100%;">
			<div class="order_id_email">
            	<p><?php echo esc_html( apply_filters( 'ast_tracking_page_front_text', __( 'To track your order, enter your order number and email address:', 'trackship-for-woocommerce' ) ) ); ?></p>
				<p class="form-row"><label for="order_id"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_order_label', __( 'Order ID', 'trackship-for-woocommerce' ) ) ); ?></label> <input class="input-text" type="text" name="order_id" id="order_id" value="" placeholder="<?php esc_html_e( 'Order Number', 'trackship-for-woocommerce' ); ?>"></p>
				<p class="form-row"><label for="order_email"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_order_email_label', __( 'Order Email', 'trackship-for-woocommerce' ) ) ); ?></label> <input class="input-text" type="text" name="order_email" id="order_email" value="" placeholder="<?php esc_html_e( 'Email address', 'trackship-for-woocommerce' ); ?>"></p>
                <p class="form-row"><button type="submit" class="button btn btn-secondary" name="track" value="Track"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_track_label', __( 'Track Order', 'trackship-for-woocommerce' ) ) ); ?></button></p>
			</div>
			<div class="by_tracking_number">
            	<p><?php echo esc_html( apply_filters( 'ast_tracking_page_traking_number_front_text', __( 'Or, enter the tracking number for your order:', 'trackship-for-woocommerce' ) ) ); ?></p>
				<p class="form-row"><label for="order_tracking_number"><?php echo esc_html( apply_filters( 'tracking_page_tracking_number_label', __( 'Tracking number', 'trackship-for-woocommerce' ) ) ); ?></label><input class="input-text" type="text" name="order_tracking_number" id="order_tracking_number" value="" placeholder="<?php esc_html_e( 'Order tracking number.', 'trackship-for-woocommerce' ); ?>"></p>
                <p class="form-row"><button type="submit" class="button btn btn-secondary" name="track" value="Track"><?php echo esc_html( apply_filters( 'ast_tracking_page_front_track_label', __( 'Track Order', 'trackship-for-woocommerce' ) ) ); ?></button></p>
			</div>
		</div>
        <div class="clear"></div>
		<input type="hidden" name="action" value="get_tracking_info">
		<div class="track_fail_msg" style="display:none;color: red;"></div>
		<?php wp_nonce_field( 'tracking_form' ); ?>
	</form>
</div>

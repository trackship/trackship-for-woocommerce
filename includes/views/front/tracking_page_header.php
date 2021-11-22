<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tracking-header">
	<?php
	do_action( 'trackship_tracking_header_before', $order->get_id(), $tracker, $provider_name, $tracking_number );
	?>

	<div class="tracking_number_wrap">
		<span class="wc_order_id">
			<a href="<?php echo esc_url( $order->get_view_order_url() ); ?>" target="_blank"><?php echo esc_html( '#' . $order->get_order_number() ); ?></a>
		</span>

		<?php if ( ! $hide_tracking_provider_image && $provider_image ) { ?>
			<div class="provider_image_div" >
				<img class="provider_image" src="<?php echo esc_url( $provider_image ); ?>">
			</div>
		<?php } ?>

		<div class="tracking_number_div">
			<ul>			
				<li>
					<span class="tracking_page_provider_name"><?php echo esc_html( apply_filters( 'ast_provider_title', $provider_name ) ); ?></span>
					<?php if ( ( $wc_ast_link_to_shipping_provider || in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) && $formatted_tracking_link ) { ?>
						<a href="<?php echo esc_url( $formatted_tracking_link ); ?>" target="blank"><strong><?php esc_html_e( $tracking_number ); ?></strong></a>	
					<?php } else { ?>
						<strong><?php esc_html_e( $tracking_number ); ?></strong>	
					<?php } ?>
				</li>
			</ul>
		</div>
	</div>
	<div class="shipment_status_heading <?php esc_html_e( $tracker->ep_status ); ?>">
		<?php
		if ( in_array( $tracker->ep_status, array( 'pending_trackship', 'pending', 'carrier_unsupported', 'unknown', 'balance_zero' ) ) ) {
			esc_html_e( 'Shipped', 'trackship-for-woocommerce' );
		} else {
			esc_html_e( apply_filters( 'trackship_status_filter', $tracker->ep_status ) );
		}
		?>
	</div>

	<?php if ( $tracker->est_delivery_date ) { ?>
		<span class="est-delivery-date tracking-number">
			<?php esc_html_e( 'Est. Delivery Date', 'trackship-for-woocommerce' ); ?> : 
			<strong><?php esc_html_e( date_i18n( 'l, M d', strtotime( $tracker->est_delivery_date ) ) ); ?></strong>
		</span>
	<?php } ?>
</div>

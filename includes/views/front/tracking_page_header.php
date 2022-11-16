<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="tracking-header">
	<?php
	// to be removed after 2-3 version - action has been added in 1.3.4 -- action trackship_tracking_header_before
	do_action( 'trackship_tracking_header_before', $order->get_id(), $tracker, $provider_name, $tracking_number );
	$row = trackship_for_woocommerce()->actions->get_tracking_shipment_row( $order->get_id(), $tracking_number );
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
				<?php if ( !$hide_last_mile && $row->delivery_number ) { ?>
					<li class="last_mile_tracking_number">
						<span><?php esc_html_e( 'Delivery tracking Number', 'trackship-for-woocommerce' ); ?></span>
						<strong> <?php echo esc_html( $row->delivery_number ) ?></strong>
					</li>
				<?php } ?>
			</ul>
		</div>	
	</div>
	<?php if ( !$hide_from_to && isset( $row->origin_country ) && $row->origin_country && $row->destination_country && $row->destination_country != $row->origin_country ) { ?>
		<div class="shipping_from_to">
			<span class="shipping_from"><?php echo esc_html( WC()->countries->countries[ $row->origin_country ] ) ?></span>
			<img class="shipping_to_img" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/arrow.png">
			<span class="shipping_to"><?php echo esc_html( WC()->countries->countries[ $row->destination_country ] ) ?></span>
		</div>
	<?php } ?>
	<div class="shipment_status_heading <?php esc_html_e( $tracker->ep_status ); ?>">
		<?php
		if ( in_array( $tracker->ep_status, array( 'pending_trackship', 'pending', 'carrier_unsupported', 'unknown', 'insufficient_balance', 'invalid_tracking', '' ) ) ) {
			esc_html_e( 'Shipped', 'trackship-for-woocommerce' );
		} else {
			$message = isset( $trackind_detail_by_status_rev[0]->message ) ? $trackind_detail_by_status_rev[0]->message : '';
			$tracker_status = str_contains( $message, 'Delivered, Parcel Locker') ? 'Delivered, Parcel Locker' : $tracker->ep_status;
			esc_html_e( apply_filters( 'trackship_status_filter', $tracker_status ) );
		}
		?>
	</div>
	<?php $show_est_delivery_date = apply_filters( 'show_est_delivery_date', true, $provider_name ); ?>
	<?php if ( $tracker->est_delivery_date && $show_est_delivery_date ) { ?>
		<span class="est-delivery-date tracking-number">
			<?php esc_html_e( 'Est. Delivery Date', 'trackship-for-woocommerce' ); ?> : 
			<strong><?php esc_html_e( date_i18n( 'l, M d', strtotime( $tracker->est_delivery_date ) ) ); ?></strong>
		</span>
	<?php } ?>
</div>

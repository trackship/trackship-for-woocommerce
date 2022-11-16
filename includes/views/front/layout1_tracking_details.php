<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$class = 1 == $hide_tracking_events ? 'checked' : '';
$fronted = isset( $_POST['fronted'] ) ? $_POST['fronted'] : '';
$tab_array = [];
$labels_name = array(
	'tracking_progress_label' => __( 'Shipment Progress', 'trackship-for-woocommerce' ),
	'items_label' => apply_filters( 'tracking_page_products_tab', __( 'Items in this shipment', 'trackship-for-woocommerce' ) ),
	'notifications_label' => __( 'Notifications', 'trackship-for-woocommerce' ),
);
$labels_name = apply_filters( 'tracking_page_tab_label', $labels_name );
if ( 1 != $hide_tracking_events ) {
	$tab_array[ 'tracking_events_details' ] = array(
		'label'	=> $labels_name['tracking_progress_label'],
		'class'	=> 'tracking_detail_label checked',
	);
}
if ( function_exists( 'ast_pro' ) ) {
	$tracking_items = trackship_for_woocommerce()->get_tracking_items( $order_id );
	$tpi_order = ast_pro()->ast_tpi->check_if_tpi_order( $tracking_items, wc_get_order( $order_id ) );
	if ( $tpi_order ){
		$tab_array[ 'product_details' ] = array(
			'label'	=> $labels_name['items_label'],
			'class'	=> $class,
		);
	}
}
if ( ( !is_admin() && get_option( 'enable_email_widget' ) ) || ( 'yes' == $fronted && get_option( 'enable_email_widget' ) ) ) {
	$tab_array['shipment_status_notifications'] = array(
		'label'	=> $labels_name['notifications_label'],
	);
}
?>

<div class="tracking_event_tab_view">
	<?php if ( isset( $tab_array['tracking_events_details'] ) ) { ?>
		<div data-label="tracking_events_details" class="heading_panel tracking_detail_label <?php isset($tab_array['tracking_events_details']['class']) ? esc_attr_e($tab_array['tracking_events_details']['class']) : ''; ?>">
			<?php echo esc_html( $tab_array['tracking_events_details']['label'] ); ?>
			<span class="accordian-arrow right"></span>
		</div>
	<?php } ?>
	<div class="content_panel tracking-details tracking_events_details">
		<?php if ( 2 == $hide_tracking_events || is_wc_endpoint_url( 'order-received' ) ) { ?>
			<?php if ( !empty( $tracking_details_by_date ) ) { ?>
				<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
					<div class="tracking_destination_details_by_date">
						<h4 style=""><?php esc_html_e( 'Destination Details', 'trackship-for-woocommerce' ); ?></h4>
						<ul class="timeline new-details">	
							<?php
							$a = 1; 
							foreach ( $trackind_destination_detail_by_status_rev as $key => $value ) { 
								if ( $a > 1) {
									break;
								}
								$date = gmdate( 'Y-m-d', strtotime( $value->datetime ) );
								?>
								<li>
									<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
									<p>
									<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
									<?php $tracking_location_city = apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ); ?>
									<span><?php echo ( null != $tracking_location_city ) ? ' - ' : ''; ?><?php echo esc_html( $tracking_location_city ); ?></span>
									</p>					
								</li>						
							<?php $a++; } ?>
						</ul>	
						
						<ul class="timeline old-details" style="display:none;">	
							<?php 
							$a = 1;	
							foreach ( $trackind_destination_detail_by_status_rev as $key => $value ) {
								if ( $a <= 1 ) {
									$a++;
									continue;
								}
								$date = gmdate('Y-m-d', strtotime( $value->datetime ) );
								?>
								<li>
									<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
									<p>
									<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
									<?php $tracking_location_city = apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ); ?>
									<span><?php echo ( null != $tracking_location_city ) ? ' - ' : ''; ?><?php echo esc_html( $tracking_location_city ); ?></span>
									</p>					
								</li>						
							<?php $a++; } ?>
						</ul>	
					</div>
				<?php } ?>
				<div class="tracking_details_by_date">
					
					<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
						<h4 class="" style=""><?php esc_html_e( 'Origin Details', 'trackship-for-woocommerce' ); ?></h4>
					<?php } ?> 
					
					<ul class="timeline new-details">	
						<?php
						$a = 1; 
						foreach ( $trackind_detail_by_status_rev as $key => $value ) { 
							if ( $a > 1) {
								break;
							}
							$date = gmdate('Y-m-d', strtotime($value->datetime));
							?>
							<li>
								<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
								<p>
									<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
									<?php $tracking_location_city = apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ); ?>
									<span><?php echo ( null != $tracking_location_city ) ? ' - ' : ''; ?><?php echo esc_html( $tracking_location_city ); ?></span>
								</p>					
							</li>						
						<?php $a++; } ?>
					</ul>	
					
					<ul class="timeline old-details" style="display:none;">	
						<?php 
						$a = 1;	
						foreach ( $trackind_detail_by_status_rev as $key => $value ) {
							if ( $a <= 1 ) {
								$a++;
								continue;
							}
							$date = gmdate( 'Y-m-d', strtotime($value->datetime ) );
							?>
							<li>
								<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
								<p>
									<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
									<?php $tracking_location_city = apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ); ?>
								<span><?php echo ( null != $tracking_location_city ) ? ' - ' : ''; ?><?php echo esc_html( $tracking_location_city ); ?></span>
								</p>					
							</li>						
						<?php $a++; } ?>
					</ul>	
					
				</div>
				<div class="view_hide_old_details_div">
					<a class="view_old_details" href="javaScript:void(0);" style="display: inline;"><?php esc_html_e( 'view more', 'trackship-for-woocommerce' ); ?></a>
					<a class="hide_old_details" href="javaScript:void(0);" style="display: none;"><?php esc_html_e( 'view less', 'trackship-for-woocommerce' ); ?></a>	
				</div>
			<?php } ?>
		<?php } else { ?>
			<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
				<div class="tracking_destination_details_by_date">
					<h4 style=""><?php esc_html_e( 'Destination Details', 'trackship-for-woocommerce' ); ?></h4>
					<ul class="timeline">	
						<?php
						foreach ( $trackind_destination_detail_by_status_rev as $key => $value ) {
							$date = gmdate('Y-m-d', strtotime($value->datetime));
							?>
							<li>
								<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
								<p>
									<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
									<?php $tracking_location_city = apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ); ?>
									<span><?php echo ( null != $tracking_location_city ) ? ' - ' : ''; ?><?php echo esc_html( $tracking_location_city ); ?></span>
								</p>
							</li>
						<?php } ?>
					</ul>
				</div>
			<?php } ?>
			<div class="tracking_details_by_date">
						<?php if ( !empty( $trackind_destination_detail_by_status_rev ) ) { ?>
							<h4 class="" style=""><?php esc_html_e( 'Origin Details', 'trackship-for-woocommerce' ); ?></h4>
						<?php } ?>
						<ul class="timeline">
							<?php
							foreach ( $trackind_detail_by_status_rev as $key => $value ) { 
								$date = gmdate( 'Y-m-d', strtotime( $value->datetime ) );
								?>
								<li>
									<strong><?php echo esc_html( date_i18n( get_option( 'date_format' ), strtotime($date) ) ); ?> <?php echo esc_html( date_i18n( get_option( 'time_format' ), strtotime($value->datetime) ) ); ?></strong>
									<p>
									<?php echo wp_kses_post( apply_filters( 'trackship_tracking_event_description', $value->message ) ); ?>
									<?php $tracking_location_city = apply_filters( 'trackship_tracking_event_location', $value->tracking_location->city ); ?>
									<span><?php echo ( null != $tracking_location_city ) ? ' - ' : ''; ?><?php echo esc_html( $tracking_location_city ); ?></span>
									</p>
								</li>						
							<?php } ?>
						</ul>	
			</div>
		<?php } ?>
	</div>
	<?php if ( isset( $tab_array['product_details'] ) ) { ?>
		<div data-label="product_details" class="heading_panel <?php isset($tab_array['product_details']['class']) ? esc_attr_e($tab_array['product_details']['class']) : ''; ?>">
			<?php echo esc_html( $tab_array['product_details']['label'] ); ?>
			<span class="accordian-arrow right"></span>
		</div>
	<?php } ?>
	<div class="content_panel product_details"><?php $this->get_products_detail_in_shipment( $order_id, $tracker, $tracking_provider, $tracking_number ); ?></div>
	<?php if ( isset( $tab_array['shipment_status_notifications'] ) ) { ?>
		<div data-label="shipment_status_notifications" class="heading_panel <?php isset($tab_array['shipment_status_notifications']['class']) ? esc_attr_e($tab_array['shipment_status_notifications']['class']) : ''; ?>">
			<?php echo esc_html( $tab_array['shipment_status_notifications']['label'] ); ?>
			<span class="accordian-arrow right"></span>
		</div>
	<?php } ?>
	<div class="content_panel shipment_status_notifications"><?php $this->get_notifications_option( $order_id ); ?></div>
</div>

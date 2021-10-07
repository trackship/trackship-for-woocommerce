<?php
global $wpdb;
$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';

$late_shipments_email_settings = get_option( 'late_shipments_email_settings' );
$late_shipments_days = isset( $late_shipments_email_settings['wcast_late_shipments_days'] ) ? $late_shipments_email_settings['wcast_late_shipments_days'] : '7';
$days = $late_shipments_days - 1 ;
$total_shipment = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row");
$active_shipment = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status NOT LIKE ( '%delivered%')");
$delivered_shipment = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%delivered%')");
$late_shipment = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status NOT LIKE ( '%delivered%') AND shipping_length > {$days}");
$return_to_sender_shipment = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%return_to_sender%')");
$avg_shipment_length = $wpdb->get_var( "SELECT ROUND(AVG(shipping_length)) as avg_shipping_length FROM {$woo_trackship_shipment}" );
$tracking_issues = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row	
		WHERE 
			shipment_status NOT LIKE ( '%delivered%')
			AND shipment_status NOT LIKE ( '%pre_transit%')
			AND shipment_status NOT LIKE ( '%in_transit%')
			AND shipment_status NOT LIKE ( '%out_for_delivery%')
			AND shipment_status NOT LIKE ( '%return_to_sender%')
			AND shipment_status NOT LIKE ( '%available_for_pickup%')
			AND shipment_status NOT LIKE ( '%exception%')
");
$args = array(
	'status' => 'wc-processing',
	'limit'	 => -1,
	'return' => 'ids',
);			
$orders = wc_get_orders( $args );		
$unfulfilled_orders = count( $orders );

$first_line = array(
	'total_shipments' => array(
		'title' => __( 'Total Shipments', 'trackship-for-woocommerce' ),
		'image' => 'pre_transit-color.png',
		'count' => $total_shipment,
		'link'  => '',
	),
	'active_shipment' => array(
		'title' => __( 'Active Shipments', 'trackship-for-woocommerce' ),
		'image' => 'in_transit-color.png',
		'count' => $active_shipment,
		'link'  => '',
	),
	'unfulfilled_orders' => array(
		'title' => __( 'Unfulfilled Orders', 'trackship-for-woocommerce' ),
		'image' => 'on_hold-color.png',
		'count' => $unfulfilled_orders,
		'link'  => admin_url('edit.php?post_status=wc-processing&post_type=shop_order'),
	),
	'avg_shipment_length' => array(
		'title' => __( 'Avg. Shipping Time', 'trackship-for-woocommerce' ),
		'image' => 'shipped-color.png',
		'count' => $avg_shipment_length,
		'link'  => '',
	)
);
$second_line = array(
	'delivered_shipment' => array(
		'title' => __( 'Delivered', 'trackship-for-woocommerce' ),
		'image' => 'delivered-color.png',
		'count' => $delivered_shipment,
		'link'  => '',
	),
	'tracking_issues' => array(
		'title' => __( 'Tracking Issues', 'trackship-for-woocommerce' ),
		'image' => 'unknown-color.png',
		'count' => $tracking_issues,
		'link'  => '',
	),
	'late_shipment' => array(
		'title' => __( 'Late Shipments', 'trackship-for-woocommerce' ),
		'image' => 'late-shipment.png',
		'count' => $late_shipment,
		'link'  => '',
	),
	'return_to_sender_shipment' => array(
		'title' => __( 'Return to Sender', 'trackship-for-woocommerce' ),
		'image' => 'return_to_sender-color.png',
		'count' => $return_to_sender_shipment,
		'link'  => '',
	)
);

$start_date = gmdate('Y-m-d 00:00:00', strtotime( 'today - 29 days' ) );
$total_shipment_30 = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipping_date > %s", $start_date ) );
$pre_transit_shipment_30 = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%pre_transit%') AND shipping_date > %s", $start_date ) );
$in_transit_shipment_30 = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%in_transit%') AND shipping_date > %s", $start_date ) );
$return_to_sender_shipment_30 = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%return_to_sender%') AND shipping_date > %s", $start_date ) );
$available_for_pickup_shipment_30 = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%available_for_pickup%') AND shipping_date > %s", $start_date ) );
$out_for_delivery_shipment_30 = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%out_for_delivery%') AND shipping_date > %s", $start_date ) );
$delivered_shipment_30 = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( %s ) AND shipping_date > %s", '%delivered%', $start_date ) );
$shipment_by_status = array(
	'pre_transit' => array(
		'title'			=> __( 'Pre Transit', 'trackship-for-woocommerce' ),
		'count'			=> $pre_transit_shipment_30,
		'percentage'	=> $this->calculate_percent( $pre_transit_shipment_30, $total_shipment_30),
		'color'			=> '#35609a'
	),
	'in_transit' => array(
		'title'			=> __( 'In Transit', 'trackship-for-woocommerce' ),
		'count'			=> $in_transit_shipment_30,
		'percentage'	=> $this->calculate_percent( $in_transit_shipment_30, $total_shipment_30),
		'color'			=> '#52c3be'
	),
	'return_to_sender' => array(
		'title'			=> __( 'Returned To Sender', 'trackship-for-woocommerce' ),
		'count'			=> $return_to_sender_shipment_30,
		'percentage'	=> $this->calculate_percent( $return_to_sender_shipment_30, $total_shipment_30),
		'color'			=> '#cd2128'
	),
	'available_for_pickup' => array(
		'title'			=> __( 'Avaialable for pickup', 'trackship-for-woocommerce' ),
		'count'			=> $available_for_pickup_shipment_30,
		'percentage'	=> $this->calculate_percent( $available_for_pickup_shipment_30, $total_shipment_30),
		'color'			=> '#f49d1d'
	),
	'out_for_delivery' => array(
		'title'			=> __( 'Out For Delivery', 'trackship-for-woocommerce' ),
		'count'			=> $out_for_delivery_shipment_30,
		'percentage'	=> $this->calculate_percent( $out_for_delivery_shipment_30, $total_shipment_30),
		'color'			=> '#8fc95c'
	),
	'delivered' => array(
		'title'			=> __( 'Delivered', 'trackship-for-woocommerce' ),
		'count'			=> $delivered_shipment_30,
		'percentage'	=> $this->calculate_percent( $delivered_shipment_30, $total_shipment_30),
		'color'			=> '#09d3ac'
	)
);
$current_plan = get_option( 'user_plan' );
?>
<input class="dashboard_hidden_field" type="hidden" value="<?php echo esc_html($current_plan); ?>">
<div class="fullfillment_dashboard">
	<?php foreach ( $first_line as $key => $value ) { ?>
		<div class="fullfillment_dashboard_section <?php echo esc_html( $key ); ?>">
			<div class="fullfillment_details">
				<div class="fullfillment_count"><?php echo esc_html( $value['count'] ); ?></div>
				<span class="fullfillment_status"><?php echo esc_html( $value['title'] ); ?></span>
				<?php if ( $value['link'] ) { ?>
					<div style="padding-top: 5px;"><a href="<?php echo esc_url( $value['link'] ); ?>"><?php esc_html_e( 'View all', 'trackship-for-woocommerce' ); ?></a></div>
			<?php } ?>
			</div>
			<div class="fullfillment_image"><img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/<?php echo esc_html( $value['image'] ); ?>"></div>
		</div>
	<?php } ?>
</div>
<div class="fullfillment_dashboard">
	<?php foreach ( $second_line as $key => $value ) { ?>
		<div class="fullfillment_dashboard_section <?php echo esc_html( $key ); ?>">
			<div class="fullfillment_details">
				<div class="fullfillment_count"><?php echo esc_html( $value['count'] ); ?></div>
				<span class="fullfillment_status"><?php echo esc_html( $value['title'] ); ?></span>
				<?php if ( $value['link'] ) { ?>
					<div style="padding-top: 5px;"><a href="<?php echo esc_url( $value['link'] ); ?>"><?php esc_html_e( 'View all', 'trackship-for-woocommerce' ); ?></a></div>
				<?php } ?>
			</div>
			<div class="fullfillment_image"><img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/<?php echo esc_html( $value['image'] ); ?>"></div>
		</div>
	<?php } ?>
</div>
<div class="fullfillment_dashboard">
	<div class="fullfillment_dashboard_by_status">
		<table class="fullfillment_detail_by_status fullfillment_table">
			<thead>
				<tr style="border-top: 0;">
					<th colspan="3"><h3 style="margin:0"><?php esc_html_e( 'Shipments by status', 'trackship-for-woocommerce' ); ?></h3></th>
					<td><label class="label_30days"><?php esc_html_e( 'Last 30 days', 'trackship-for-woocommerce' ); ?></label></td>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $shipment_by_status as $key => $value ) { ?>
					<?php $percentage = str_replace( array( '(', ')' ), '', $value['percentage'] ); ?>
					<tr class="<?php echo esc_html( $key ); ?>">
						<td><?php echo esc_html( $value['title'] ); ?></td>
						<th><?php echo esc_html( $value['count'] ); ?></th>
						<th class="shipment_percentage"><?php echo esc_html( $percentage ); ?></th>
						<td style="width:40%"><div class="progress_bar" style="width:<?php echo esc_html( $percentage ); ?>;background:<?php echo esc_html( $value['color'] ); ?>;"></div></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<a class="analytics_link" href="<?php echo esc_url( admin_url('admin.php?page=wc-admin&path=%2Fanalytics%2Ftrackship-analytics') ); ?>"><?php esc_html_e( 'View analytics', 'trackship-for-woocommerce' ); ?></a>
	</div>
	<div class="fullfillment_dashboard_by_carrier">
		<table class="fullfillment_detail_by_carrier fullfillment_table">
			<thead>
				<tr style="border-top: 0;">
					<th colspan="3"><h3 style="margin:0"><?php esc_html_e( 'Shipments by Carrier', 'trackship-for-woocommerce' ); ?></h3></th>
					<td><label class="label_30days"><?php esc_html_e( 'Last 30 days', 'trackship-for-woocommerce' ); ?></label></td>
				</tr>
			</thead>
			<tbody>
				<tr>
					<td><?php esc_html_e( 'Provider', 'trackship-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( 'Shipments', 'trackship-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( '% of total', 'trackship-for-woocommerce' ); ?></td>
					<td><?php esc_html_e( 'Avg. shipping time', 'trackship-for-woocommerce' ); ?></td>
				</tr>
				<?php
				$all_providers = $wpdb->get_results( $wpdb->prepare( "SELECT COUNT(shipping_provider) as counts, shipping_provider, AVG(shipping_length) as average FROM {$woo_trackship_shipment} WHERE shipping_provider NOT LIKE ( '%NULL%') AND shipping_date > %s GROUP BY shipping_provider", $start_date ) );
				foreach ( $all_providers as $provider ) {
					$formatted_provider = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $wpdb->prefix . 'woo_shippment_provider WHERE ts_slug = %s', $provider->shipping_provider ) );
					$percentage_30 = $this->calculate_percent( $provider->counts, $total_shipment_30);
					$percentage_30 = str_replace( array( '(', ')' ), '', $percentage_30 );
					?>
					<tr>
						<td><?php echo esc_html( $formatted_provider->provider_name ); ?></td>
						<td><strong><?php echo esc_html( $provider->counts ); ?></strong></td>
						<td><strong class="shipment_percentage"><?php echo esc_html( $percentage_30 ); ?></strong></td>
						<td><?php echo esc_html( (int) $provider->average . ' days' ); ?></td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<a class="analytics_link" href="<?php echo esc_url( admin_url('admin.php?page=wc-admin&path=%2Fanalytics%2Ftrackship-analytics') ); ?>"><?php esc_html_e( 'View analytics', 'trackship-for-woocommerce' ); ?></a>
	</div>
</div>

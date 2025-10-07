<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $wpdb->prefix . 'trackship_shipment' ) ) ) {
	esc_html_e( 'TrackShip Shipments database table does not exist.', 'trackship-for-woocommerce' );
	return;
}

if ( !$wpdb->query( $wpdb->prepare( 'show tables like %s', $wpdb->prefix . 'trackship_shipment_meta' ) ) ) {
	esc_html_e( 'TrackShip Shipments meta database table does not exist.', 'trackship-for-woocommerce' );
	return;
}

$nonce = wp_create_nonce( 'ts_tools');
?>
<input type="hidden" id="ts_tools" name="ts_tools" value="<?php echo esc_attr( $nonce ); ?>" />
<?php
$ship_status = array(
	'all_ship'				=> __( 'All Shipments', 'trackship-for-woocommerce' ),
	'active'				=> __( 'Active Shipments', 'trackship-for-woocommerce' ),
	'in_transit'			=> __( 'In Transit', 'trackship-for-woocommerce' ),
	'out_for_delivery'		=> __( 'Out For Delivery', 'trackship-for-woocommerce' ),
	'pre_transit'			=> __( 'Pre Transit', 'trackship-for-woocommerce' ),
	'exception'				=> __( 'Exception', 'trackship-for-woocommerce' ),
	'on_hold'				=> __( 'On Hold', 'trackship-for-woocommerce' ),
	'delivered'				=> __( 'Delivered', 'trackship-for-woocommerce' ),
	'return_to_sender'		=> __( 'Return To Sender', 'trackship-for-woocommerce' ),
	'available_for_pickup'	=> __( 'Available For Pickup', 'trackship-for-woocommerce' ),
	'failure'				=> __( 'Delivery Failure', 'trackship-for-woocommerce' ),
	'expired'				=> __( 'Expired', 'trackship-for-woocommerce' ),
	'late_shipment'			=> __( 'Late Shipments', 'trackship-for-woocommerce' ),
	'active_late'			=> __( 'Active Late Shipments', 'trackship-for-woocommerce' ),
	'unknown'				=> __( 'Unknown', 'trackship-for-woocommerce' ),
	'label_cancelled'		=> __( 'Label Cancelled', 'trackship-for-woocommerce' ),
	'invalid_tracking'		=> __( 'Invalid Tracking', 'trackship-for-woocommerce' ),
	'invalid_carrier'		=> __( 'Invalid Carrier', 'trackship-for-woocommerce' ),
	'carrier_unsupported'	=> __( 'Carrier Unsupported', 'trackship-for-woocommerce' ),
	'tracking_issues'		=> __( 'Tracking Issues', 'trackship-for-woocommerce' ),
	'pending_trackship'		=> __( 'Pending Update', 'trackship-for-woocommerce' ),
);
$columns = array(
	1 => 'Order',
	2 => 'Order date',
	3 => 'Shipping date',
	4 => 'Updated at',
	5 => 'Tracking Number',
	6 => 'Shipping carrier',
	7 => 'Shipment status',
	8 => 'Ship from',
	9 => 'Ship to',
	10 => 'Ship State',
	11 => 'Ship City',
	12 => 'Latest Event Date',
	13 => 'Last Event',
	14 => 'Customer',
	15 => 'Shipping time',
	16 => 'Delivery date',
	17 => 'Delivery number',
);
$url_status = isset( $_GET['status'] ) ? sanitize_text_field( $_GET['status'] ) : '';
$url_provider = isset( $_GET['provider'] ) ? sanitize_text_field( $_GET['provider'] ) : '';

$res = $wpdb->get_results( "SELECT shipment_status, COUNT(*) AS status_count FROM {$wpdb->prefix}trackship_shipment GROUP BY shipment_status", ARRAY_A );
$statuses = array_column($res, 'shipment_status');
$status_count = array_column($res, 'status_count');
$shipment_count = array_combine($statuses, $status_count); // combine the two arrays using shipment_status as keys
$late_ship_day = get_trackship_settings( 'late_shipments_days', 7);
$days = $late_ship_day - 1 ;
$issues_count = $wpdb->get_row( $wpdb->prepare( "SELECT
	COUNT(*) AS all_ship,
	SUM( IF( shipment_status != ( 'delivered'), 1, 0 ) ) as active,
	SUM( IF(shipment_status NOT IN ( 'delivered', 'in_transit', 'out_for_delivery', 'pre_transit', 'exception', 'return_to_sender', 'available_for_pickup' ) OR pending_status IS NOT NULL, 1, 0) ) as tracking_issues,
	SUM( IF(shipping_length > %d, 1, 0) ) as late_shipment,
	SUM( IF(shipping_length > %d AND shipment_status NOT IN ('delivered', 'return_to_sender'), 1, 0) ) as active_late,
	SUM( IF(pending_status = 'pending_trackship', 1, 0) ) as pending_trackship,
	SUM( IF(pending_status = 'carrier_unsupported', 1, 0) ) as carrier_unsupported
FROM {$wpdb->prefix}trackship_shipment", $days, $days), ARRAY_A);

$shipment_count = array_merge($shipment_count, $issues_count);

$res = $wpdb->get_results( "SELECT shipping_provider, COUNT(*) AS provider_count FROM {$wpdb->prefix}trackship_shipment GROUP BY shipping_provider", ARRAY_A );
$provider_array = array_column($res, 'shipping_provider');
$provider_count_array = array_column($res, 'provider_count');
$provider_count = array_combine($provider_array, $provider_count_array);
?>
<div>
	<span class="shipment_date_range">
		<input type="text" class="select_option" id="shipment_date_range" placeholder="Select date range" />
		<input type="hidden" id="shipment_start_date_range">
		<input type="hidden" id="shipment_end_date_range">
	</span>

	<span class="shipment_status">
		<select class="select_option" name="shipment_status" id="shipment_status">
			<?php foreach ( $ship_status as $key => $val ) { ?>
				<?php $count = isset($shipment_count[$key]) ? $shipment_count[$key] : 0; ?>
				<option value="<?php echo esc_html( $key ); ?>" <?php echo $url_status == $key ? 'selected' : ''; ?>><?php echo esc_html( $val . ' (' . $count . ') ' ); ?></option>
			<?php } ?>
		</select>
	</span>
	<?php
	$all_providers = $wpdb->get_results( $wpdb->prepare("SELECT shipping_provider FROM {$wpdb->prefix}trackship_shipment WHERE shipping_provider NOT LIKE ( %s ) GROUP BY shipping_provider", '%NULL%' ) );
	?>
	<span class="shipping_provider">
		<select class="select_option" name="shipping_provider" id="shipping_provider">
			<option value="all"><?php esc_html_e( 'All shipping providers', 'trackship-for-woocommerce' ); ?></option>
			<?php foreach ( $all_providers as $provider ) { ?>
				<?php $count = isset($provider_count[$provider->shipping_provider]) ? $provider_count[$provider->shipping_provider] : 0; ?>
				<?php $formatted_provider = trackship_for_woocommerce()->actions->get_provider_name( $provider->shipping_provider ); ?>
				<?php $provider_name = isset($formatted_provider) && $formatted_provider ? $formatted_provider : $provider->shipping_provider; ?>
				<option value="<?php echo esc_html( $provider->shipping_provider ); ?>" <?php echo $url_provider == $provider->shipping_provider ? 'selected' : ''; ?>><?php echo esc_html( $provider_name . ' (' . $count . ') ' ); ?></option>
		<?php } ?>
		</select>
	</span>
</div>
<div class="bulk_action_div">
	<select class="select_option" name="bulk_actions" id="bulk_actions">
		<option><?php esc_html_e( 'Bulk actions', 'trackship-for-woocommerce' ); ?></option>
		<option value="get_shipment_status"><?php esc_html_e( 'Get shipment status', 'trackship-for-woocommerce' ); ?></option>
	</select>
	<button class="bulk_action_button button-trackship button-primary" type="button"><?php esc_html_e( 'Apply', 'trackship-for-woocommerce' ); ?></button>
</div>
<div class="filters_div">
	<span class="filter_data status_filter"><span class="status_name"></span><span class="dashicons dashicons-no-alt"></span></span>
	<span class="filter_data provider_filter"><span class="provider_name"></span><span class="dashicons dashicons-no-alt"></span></span>
</div>
<div class="shipments_custom_data custom_data">
	<span class="shipment_search_bar">
		<input type="text" id="search_bar" name="search_bar" placeholder="<?php esc_html_e( 'Search by Tracking Number, Shipping carrier, Order number', 'trackship-for-woocommerce' ); ?>">
		<span class="dashicons dashicons-no"></span>
		<span class="dashicons dashicons-search serch_icon"></span>
	</span>
	<span class="export_shipment"><span class="dashicons dashicons-download" title="CSV download"></span></span>
	<span class="more_info_shipment">
		<span class="dashicons dashicons-ellipsis"></span>
		<div class="popover__content">
			<?php foreach ( $columns as $key => $val) { ?>
				<div class="column_toogle">
					<input type="hidden" name="<?php echo 'column_' . esc_attr($key); ?>" value="0"/>
					<input class="tgl tgl-flat" id="<?php echo 'column_' . esc_attr($key); ?>" name="<?php echo 'column_' . esc_attr($key); ?>" data-number="<?php echo esc_attr($key); ?>" type="checkbox" checked value="1"/>
					<label class="tgl-btn tgl-btn-green" for="<?php echo 'column_' . esc_attr($key); ?>"></label>
					<label for="<?php echo 'column_' . esc_attr($key); ?>"><span><?php echo esc_html($val); ?></span></label>
				</div>
			<?php } ?>
		</div>
	</span>
</div>
<div class="trackship_admin_content">
	<section class="trackship_analytics_section">
		<div class="woocommerce trackship_admin_layout">
			<div class="">
				<input type="hidden" id="nonce_trackship_shipments" value="<?php echo esc_attr( wp_create_nonce( '_trackship_shipments' ) ); ?>">
				<table class="widefat dataTable fixed fullfilments_table hover" cellspacing="0" id="active_shipments_table" style="width: 100%;">
					<thead>
						<tr class="tabel_heading_th">
							<th id="columnname" class="manage-column column-columnname" scope="col"><input type="checkbox" class="all_checkboxes"></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Order', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Order date', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipped date', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Updated at', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('Tracking Number', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipping carrier', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipment status', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Ship from', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Ship to', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Ship State', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Ship City', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('Latest Event Date', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('Latest Event', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Customer', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipping time', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('Delivery date', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('Delivery number', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Actions', 'trackship-for-woocommerce'); ?></th>
						</tr>
					</thead>
					<tbody></tbody>
				</table>
			</div>
		</div>
	</section>
</div>
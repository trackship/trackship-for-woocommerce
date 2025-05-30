<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( !get_trackship_settings( 'wc_admin_notice', '') ) {
	if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ) {
		trackship_for_woocommerce()->wc_admin_notice->admin_notices_for_TrackShip_pro();
	}
	trackship_for_woocommerce()->wc_admin_notice->admin_notices_for_TrackShip_review();
	update_trackship_settings( 'wc_admin_notice', 'true');
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

$late_ship_day = get_trackship_settings( 'late_shipments_days', 7);
$days = $late_ship_day - 1 ;
$from_date = gmdate('Y-m-d', strtotime('-30 days'));

$results = $wpdb->get_row($wpdb->prepare("
SELECT
	SUM( IF( shipping_length > %d, 1, 0 ) ) as late_shipment,
	SUM( IF(shipment_status NOT IN ( %s, %s, %s, %s, %s, %s, %s ) OR pending_status IS NOT NULL, 1, 0) ) as tracking_issues,
	SUM( IF( shipment_status = 'return_to_sender', 1, 0 ) ) as return_to_sender_shipment
FROM {$wpdb->prefix}trackship_shipment
WHERE shipping_date >= %s", $days, 'delivered', 'in_transit', 'out_for_delivery', 'pre_transit', 'exception', 'return_to_sender', 'available_for_pickup', $from_date ), ARRAY_A);

$late_shipment = $results['late_shipment'];
$tracking_issues = $results['tracking_issues'];
$return_to_sender_shipment = $results['return_to_sender_shipment'];

$completed_order_with_tracking = trackship_for_woocommerce()->admin->completed_order_with_tracking();
$completed_order_with_zero_balance = trackship_for_woocommerce()->admin->completed_order_with_zero_balance();
$completed_order_with_do_connection = trackship_for_woocommerce()->admin->completed_order_with_do_connection();
$unsent_shipments = $completed_order_with_tracking + $completed_order_with_zero_balance + $completed_order_with_do_connection;

$this_month = gmdate('Y-m-01 00:00:00' );
$last_30 = gmdate('Y-m-d 00:00:00', strtotime( 'today - 29 days' ) );
$last_60 = gmdate('Y-m-d 00:00:00', strtotime( 'today - 59 days' ) );

$action_needed = array(
	'unsent_shipments' => array(
		'title' => __( "shipments from the last 30 days that haven't been automatically tracked. To start tracking these past orders, simply click here", 'trackship-for-woocommerce' ),
		'count' => $unsent_shipments,
		'link' => admin_url( 'admin.php?page=trackship-for-woocommerce&tab=tools' ),
	),
	'late_shipment' => array(
		'title' => __( 'Late Shipments for last 30 days', 'trackship-for-woocommerce' ),
		'count' => $late_shipment,
	),
	'tracking_issues' => array(
		'title' => __( 'Tracking Issues for last 30 days', 'trackship-for-woocommerce' ),
		'count' => $tracking_issues,
	),
	'return_to_sender' => array(
		'title' => __( 'Return To Sender for last 30 days', 'trackship-for-woocommerce' ),
		'count' => $return_to_sender_shipment,
	),
);
$first_line = array(
	'total_shipment' => array(
		'title' => __( 'Total Shipments', 'trackship-for-woocommerce' ),
		'image' => 'pre-transit-v1.png',
	),
	'active_shipment' => array(
		'title' => __( 'Active', 'trackship-for-woocommerce' ),
		'image' => 'in-transit-v1.png',
	),
	'delivered_shipment' => array(
		'title' => __( 'Delivered', 'trackship-for-woocommerce' ),
		'image' => 'delivered-v1.png',
	),
	'tracking_issues' => array(
		'title' => __( 'Tracking Issues', 'trackship-for-woocommerce' ),
		'image' => 'label_cancelled-v1.png',
	),
);
$array = array(
	'month_to_date' => array(
		'label'	=> __( 'Month to date', 'trackship-for-woocommerce' ),
		'time'	=> $this_month,
		'class' => 'first_label',
	),
	'last_30' => array(
		'label'	=> __( 'Last 30 days', 'trackship-for-woocommerce' ),
		'time'	=> $last_30,
		'class' => 'not_show',
	),
	'last_60' => array(
		'label'	=> __( 'Last 60 days', 'trackship-for-woocommerce' ),
		'time'	=> $last_60,
		'class' => 'not_show',
	),
);
$url = 'https://api.trackship.com/v1/user-plan/get';
$args['body'] = json_encode( [ 'user_key' => get_trackship_key() ] );
$response = wp_remote_post( $url, $args );
$plan_data = is_wp_error( $response ) ? [] : json_decode( $response[ 'body' ] );
$current_plan = $plan_data->subscription_plan;
$current_balance = $plan_data->tracker_balance;
update_option( 'user_plan', $current_plan );
update_option( 'trackers_balance', $current_balance );
$nonce = wp_create_nonce( 'ts_tools');
$store_text = in_array( $current_plan, array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ? __( 'Upgrade to Pro', 'trackship-for-woocommerce' ) : __( 'Account Dashboard', 'trackship-for-woocommerce' );
$store_url = in_array( $current_plan, array( 'Free 50', 'No active plan', 'Trial Ended' ) ) ? 'https://my.trackship.com/settings/?utm_source=wpadmin&utm_medium=trackship&utm_campaign=upgrade#billing' : 'https://my.trackship.com/?utm_source=wpadmin&utm_medium=trackship&utm_campaign=dashboard';
?>
<input type="hidden" id="ts_tools" name="ts_tools" value="<?php echo esc_attr( $nonce ); ?>" />
<input class="dashboard_hidden_field" type="hidden" value="<?php echo esc_html($current_plan); ?>">
<?php if ( ! trackship_for_woocommerce()->is_ast_active() ) { ?>
	<div class="ts_ast_notice">
		<div>
			<div class="ast_activate_message">
				<strong>
					<p style="font-size:16px;"><?php esc_html_e('You must have a Shipment Tracking plugin installed to use TrackShip for WooCommerce.', 'trackship-for-woocommerce'); ?></p>
				</strong>
				<p><?php esc_html_e( "Include shipment tracking details in your WooCommerce orders, enabling customers to effortlessly monitor their orders. Shipment tracking information will be accessible within customers' accounts, located in the order section, and will also be included in the WooCommerce order completion email.", 'trackship-for-woocommerce' ); ?></p>
			</div>
			<button class="button-primary button-trackship btn_large">
				<a href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=search&s=AST&plugin-search-input=Search+Plugins' ) ); ?>" class="" target="_blank">
					<span><?php esc_html_e( 'Install Shipment Tracking plugin', 'trackship-for-woocommerce' ); ?></span>
					<span class="dashicons dashicons-arrow-right-alt2"></span>
				</a>
			</button>
		</div>
	</div>
<?php } ?>
<div class="fullfillment_dashboard">
	<div class="fullfillment_dashboard_section">
		<div class="fullfillment_dashboard_status">
			<div class="ts_billing_plan_status">
				<div class="ts_tracker_balance">
					<img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/ts-balance.png">
					<div class="ts_plan_details"><strong><?php echo esc_html( get_option('trackers_balance') ); ?></strong></div>
					<span class="ts_plan_details_bottom"><?php esc_html_e( 'Available Balance', 'trackship-for-woocommerce' ); ?></span>
				</div>
				<div class="ts_subscription">
					<img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/ts-plan.png">
					<?php if ( isset( $plan_data->subscription_plan ) ) { ?>
						<div class="ts_plan_details"><strong><?php echo esc_html( $plan_data->subscription_plan ); ?></strong></div>
					<?php } ?>
					<a href="<?php echo esc_url($store_url); ?>" class="" target="_blank">
						<span><?php esc_html_e( $store_text ); ?></span>
						<span class="dashicons dashicons-arrow-right-alt2"></span>
					</a>
				</div>
			</div>
		</div>
		<h3><?php esc_html_e( 'Action Needed', 'trackship-for-woocommerce' ); ?></h3>
		<table class="fullfillment_table">
			<tbody>
				<?php foreach ( $action_needed as $key => $value ) { ?>
					<?php if ( $value['count'] > 0 ) { ?>
						<?php $shipment_link = $value['link'] ?? admin_url( 'admin.php?page=trackship-shipments&status=' . $key ); ?>
						<tr onclick="window.location='<?php echo esc_url( $shipment_link ); ?>';">
							<td>
								<label><?php echo esc_html( $value['count'] ); ?> <?php echo esc_html( $value['title'] ); ?></label>
							</td>
							<td>
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
				<?php if ( ( $late_shipment + $tracking_issues + $return_to_sender_shipment + $unsent_shipments ) == 0 ) { ?>
					<tr>
						<td>
							<label><?php esc_html_e( 'No action needed for Shipments', 'trackship-for-woocommerce' ); ?></label>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
	</div>
	<div class="fullfillment_dashboard_section last_section">
		<h3><?php esc_html_e( 'Shipping & Delivery Overview', 'trackship-for-woocommerce' ); ?></h3>
		<div class="dashboard_input_tab">
			<?php foreach ( $array as $key => $val ) { ?>
				<input id="dashboard_<?php esc_html_e( $key ); ?>" type="radio" name="tabs" class="tab_input <?php esc_html_e( $val[ 'class' ] ); ?>" data-tab="<?php esc_html_e( $val[ 'time' ] ); ?>" <?php echo 'month_to_date' == $key ? 'checked' : ''; ?> >
				<label for="dashboard_<?php esc_html_e( $key ); ?>" class="tab_label">
					<?php esc_html_e( $val[ 'label' ] ); ?>
				</label>
			<?php } ?>
		</div>
		<div class="fullfillment_dashboard_section_content">
			<?php foreach ( $first_line as $key => $value ) { ?>
				<div class="innner_content">
					<div class="fullfillment_details">
						<span class="fullfillment_status"><?php echo esc_html( $value['title'] ); ?></span>
						<div class="fullfillment_count <?php echo esc_html( $key ); ?>"></div>
					</div>
					<div class="fullfillment_image"><img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/<?php echo esc_html( $value['image'] ); ?>"></div>
				</div>
			<?php } ?>
		</div>
		<div class="detailed_stats"><a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=trackship-shipments' ) ); ?>"><?php esc_html_e( 'View detailed stats', 'trackship-for-woocommerce' ); ?></a></div>
		
	</div>
</div>

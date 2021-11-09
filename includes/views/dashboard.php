<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $wpdb;
$woo_trackship_shipment = $wpdb->prefix . 'trackship_shipment';

$late_shipments_days = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
$days = $late_shipments_days - 1 ;
$late_shipment = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipping_length > %d", $days ) );
$tracking_issues = $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row	
	WHERE 
		shipment_status NOT LIKE ( %s )
		AND shipment_status NOT LIKE ( '%pre_transit%')
		AND shipment_status NOT LIKE ( '%in_transit%')
		AND shipment_status NOT LIKE ( '%out_for_delivery%')
		AND shipment_status NOT LIKE ( '%return_to_sender%')
		AND shipment_status NOT LIKE ( '%available_for_pickup%')
		AND shipment_status NOT LIKE ( '%exception%')
", '%delivered%' ) );
$return_to_sender_shipment = $wpdb->get_var( "SELECT COUNT(*) FROM {$woo_trackship_shipment} AS row WHERE shipment_status LIKE ( '%return_to_sender%')" );

$this_month = gmdate('Y-m-01 00:00:00' );
$last_30 = gmdate('Y-m-d 00:00:00', strtotime( 'today - 29 days' ) );
$last_60 = gmdate('Y-m-d 00:00:00', strtotime( 'today - 59 days' ) );

$action_needed = array(
	'late_shipment' => array(
		'title' => __( 'Late Shipments', 'trackship-for-woocommerce' ),
		'count' => $late_shipment,
	),
	'tracking_issues' => array(
		'title' => __( 'Tracking Issues', 'trackship-for-woocommerce' ),
		'count' => $tracking_issues,
	),
	'return_to_sender' => array(
		'title' => __( 'Return To Sender', 'trackship-for-woocommerce' ),
		'count' => $return_to_sender_shipment,
	),
	'no_action_needed' => array(
		'title' => __( 'No action needed for Shipments', 'trackship-for-woocommerce' ),
		'count' => '',
	),
);
$first_line = array(
	'total_shipment' => array(
		'title' => __( 'Total Shipments', 'trackship-for-woocommerce' ),
		'image' => 'pre-transit.png',
	),
	'active_shipment' => array(
		'title' => __( 'Active', 'trackship-for-woocommerce' ),
		'image' => 'in-transit.png',
	),
	'delivered_shipment' => array(
		'title' => __( 'Delivered', 'trackship-for-woocommerce' ),
		'image' => 'delivered.png',
	),
	'tracking_issues' => array(
		'title' => __( 'Tracking Issues', 'trackship-for-woocommerce' ),
		'image' => 'label_cancelled.png',
	),
);
$array = array(
	'month_to_date' => array(
		'label'	=> __( 'Month to date', 'trackship-for-woocommerce' ),
		'time'	=> $this_month,
	),
	'last_30' => array(
		'label'	=> __( 'Last 30 days', 'trackship-for-woocommerce' ),
		'time'	=> $last_30,
	),
	'last_60' => array(
		'label'	=> __( 'Last 60 days', 'trackship-for-woocommerce' ),
		'time'	=> $last_60,
	),
);
$plan_array = array(
	'Free Trial'	=> 50,
	'Mini'			=> 100,
	'Small'			=> 300,
	'MEDIUM'		=> 500,
	'Large'			=> 1000,
	'X-LARGE'		=> 2000,
	'XX-LARGE'		=> 3000,
	'XXX-LARGE'		=> 5000,
	'HUGE'			=> 10000,
	'Giant 30K'		=> 30000,
	'Giant 50K'		=> 50000,
	'Giant 60k'		=> 60000,
	'Giant 100k'	=> 100000,
);
$url = 'https://my.trackship.info/wp-json/tracking/get_user_plan';								
$args[ 'body' ] = array(
	'user_key' => trackship_for_woocommerce()->actions->get_trackship_key(),				
);
$response = wp_remote_post( $url, $args );
if ( is_wp_error( $response ) ) {
	$plan_data = array();
} else {
	$plan_data = json_decode( $response[ 'body' ] );					
}
$current_plan = $plan_data->subscription_plan;
update_option( 'user_plan', $current_plan );
$nonce = wp_create_nonce( 'wc_ast_tools');
$store_text = in_array( $current_plan, array( 'Free Trial', 'Free 50', 'No active plan' ) ) ? __( 'Upgrade to Pro', 'trackship-for-woocommerce' ) : __( 'Account Dashboard', 'trackship-for-woocommerce' );
?>
<input type="hidden" id="wc_ast_dashboard_tab" name="wc_ast_dashboard_tab" value="<?php echo esc_attr( $nonce ); ?>" />
<input class="dashboard_hidden_field" type="hidden" value="<?php echo esc_html($current_plan); ?>">
<div class="fullfillment_dashboard">
	<div class="fullfillment_dashboard_section">
		<h3><?php esc_html_e( 'Action Needed', 'trackship-for-woocommerce' ); ?></h3>
		<table class="fullfillment_table">
			<tbody>
				<?php foreach ( $action_needed as $key => $value ) { ?>
					<?php if ( $value['count'] > 0 ) { ?>
						<tr onclick="window.location='<?php echo esc_url( admin_url( 'admin.php?page=trackship-shipments&status=' . $key ) ); ?>';">
							<td>
								<label><?php echo esc_html( $value['title'] ); ?> (<?php echo esc_html( $value['count'] ); ?>)</label>
								<span class="dashicons dashicons-arrow-right-alt2"></span>
							</td>
						</tr>
					<?php } ?>
				<?php } ?>
				<?php if ( ( $late_shipment + $tracking_issues + $return_to_sender_shipment ) == 0 ) { ?>
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
				<input id="dashboard_<?php esc_html_e( $key ); ?>" type="radio" name="tabs" class="tab_input" data-tab="<?php esc_html_e( $val[ 'time' ] ); ?>" <?php echo $key == 'month_to_date' ? 'checked' : ''; ?> >
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
		<div class="fullfillment_dashboard_status">
			<h4><?php esc_html_e( 'Status', 'trackship-for-woocommerce' ); ?></h4>
			<div class="ts_subscription">
				<?php esc_html_e( 'Billing Plan ', 'trackship-for-woocommerce' ); ?>:
				<?php if ( isset( $plan_data->subscription_plan ) ) { ?>
					<strong><?php echo esc_html( $plan_data->subscription_plan ); ?></strong>
				<?php } ?>
			</div>
			<div class="ts_tracker_balance">
				<span><?php esc_html_e( 'Usage Balance ', 'trackship-for-woocommerce' ); ?></span>: <strong> <?php echo esc_html( get_option('trackers_balance') ); ?><?php echo isset( $plan_array[ $current_plan ] ) ? ' / ' . esc_html( $plan_array[$current_plan] ) : ''; ?></strong>
			</div>
			<div class="ts_connected_status">
				<span><?php esc_html_e( 'Connection Status', 'trackship-for-woocommerce' ); ?></span>: <strong><span class="dashicons dashicons-yes"></span><?php esc_html_e( 'Connected', 'trackship-for-woocommerce' ); ?></strong>
			</div>
			<a href="https://trackship.info/my-account/?utm_source=wpadmin&utm_medium=sidebar&utm_campaign=upgrade" class="button-primary button-trackship btn_large" target="_blank">
				<span><?php esc_html_e( $store_text ); ?></span>
				<span class="dashicons dashicons-arrow-right-alt2"></span>
			</a>
		</div>
	</div>
</div>

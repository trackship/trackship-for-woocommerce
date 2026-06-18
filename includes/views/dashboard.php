<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( !get_trackship_settings( 'wc_admin_notice', '') ) {
	if ( in_array( get_option( 'user_plan' ), array( 'Complimentary 100', 'Complimentary 150', 'Free 20', 'No active plan', 'Trial Ended' ) ) ) {
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
$from_date = gmdate('Y-m-d', strtotime('today -30 days'));

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

/* ---------- Analytics data for the dashboard insight sections (all-time) ---------- */
$tsd_status_rows = $wpdb->get_results( "SELECT shipment_status status, COUNT(*) c FROM {$wpdb->prefix}trackship_shipment GROUP BY shipment_status", ARRAY_A );

$tsd_status_map = array(
	'delivered'            => array( __( 'Delivered', 'trackship-for-woocommerce' ),            '#16a34a' ),
	'in_transit'           => array( __( 'In Transit', 'trackship-for-woocommerce' ),           '#2563eb' ),
	'out_for_delivery'     => array( __( 'Out for Delivery', 'trackship-for-woocommerce' ),     '#0ea5b7' ),
	'pre_transit'          => array( __( 'Pre-Transit', 'trackship-for-woocommerce' ),          '#6366f1' ),
	'available_for_pickup' => array( __( 'Available for Pickup', 'trackship-for-woocommerce' ), '#09d3ac' ),
	'return_to_sender'     => array( __( 'Return to Sender', 'trackship-for-woocommerce' ),     '#7c3aed' ),
	'exception'            => array( __( 'Exception', 'trackship-for-woocommerce' ),            '#dc2626' ),
	'on_hold'              => array( __( 'On Hold', 'trackship-for-woocommerce' ),              '#f59e0b' ),
);

$tsd_status       = array();
$tsd_status_total = 0;
foreach ( (array) $tsd_status_rows as $r ) {
	$c                 = (int) $r['c'];
	$tsd_status_total += $c;
	$st                = $r['status'];
	if ( isset( $tsd_status_map[ $st ] ) ) {
		$k     = $st;
		$label = $tsd_status_map[ $st ][0];
		$color = $tsd_status_map[ $st ][1];
	} else {
		$k     = '_other';
		$label = __( 'Other', 'trackship-for-woocommerce' );
		$color = '#94a3b8';
	}
	if ( ! isset( $tsd_status[ $k ] ) ) {
		$tsd_status[ $k ] = array( 'label' => $label, 'color' => $color, 'count' => 0 );
	}
	$tsd_status[ $k ]['count'] += $c;
}
uasort( $tsd_status, function ( $a, $b ) { return $b['count'] - $a['count']; } );
// Keep the donut tidy: top 6 segments, merge the rest into "Other".
if ( count( $tsd_status ) > 6 ) {
	$tsd_keep = array_slice( $tsd_status, 0, 6, true );
	$tsd_rest = array_slice( $tsd_status, 6, null, true );
	$tsd_rest_count = 0;
	foreach ( $tsd_rest as $seg ) {
		$tsd_rest_count += $seg['count'];
	}
	if ( isset( $tsd_keep['_other'] ) ) {
		$tsd_keep['_other']['count'] += $tsd_rest_count;
	} elseif ( $tsd_rest_count > 0 ) {
		$tsd_keep['_other'] = array( 'label' => __( 'Other', 'trackship-for-woocommerce' ), 'color' => '#94a3b8', 'count' => $tsd_rest_count );
	}
	$tsd_status = $tsd_keep;
}

$tsd_carriers  = $wpdb->get_results( "SELECT shipping_provider p, COUNT(*) c FROM {$wpdb->prefix}trackship_shipment WHERE shipping_provider <> '' AND shipping_provider IS NOT NULL GROUP BY shipping_provider ORDER BY c DESC LIMIT 5", ARRAY_A );
$tsd_countries = $wpdb->get_results( "SELECT shipping_country co, COUNT(*) c FROM {$wpdb->prefix}trackship_shipment WHERE shipping_country <> '' AND shipping_country IS NOT NULL GROUP BY shipping_country ORDER BY c DESC LIMIT 5", ARRAY_A );

$tsd_kpi           = $wpdb->get_row( "SELECT COUNT(*) total, SUM(shipment_status='delivered') delivered, AVG(NULLIF(shipping_length,'')) avg_len, SUM(shipment_status='delivered' AND est_delivery_date IS NOT NULL AND last_event_time IS NOT NULL AND DATE(last_event_time) <= est_delivery_date) ontime, SUM(shipment_status='delivered' AND est_delivery_date IS NOT NULL) delivered_with_est FROM {$wpdb->prefix}trackship_shipment", ARRAY_A );
$tsd_avg_transit   = ! empty( $tsd_kpi['avg_len'] ) ? round( (float) $tsd_kpi['avg_len'], 1 ) : 0;
$tsd_ontime_pct    = ! empty( $tsd_kpi['delivered_with_est'] ) ? round( 100 * $tsd_kpi['ontime'] / $tsd_kpi['delivered_with_est'] ) : 0;
$tsd_delivered_pct = ! empty( $tsd_kpi['total'] ) ? round( 100 * $tsd_kpi['delivered'] / $tsd_kpi['total'] ) : 0;

/* Carrier performance (avg transit + on-time, top 5 by volume). */
$tsd_carrier_perf = $wpdb->get_results( "SELECT shipping_provider p, COUNT(*) total, AVG(NULLIF(shipping_length,'')) avg_len, SUM(shipment_status='delivered' AND est_delivery_date IS NOT NULL AND last_event_time IS NOT NULL AND DATE(last_event_time) <= est_delivery_date) ontime, SUM(shipment_status='delivered' AND est_delivery_date IS NOT NULL) del_est FROM {$wpdb->prefix}trackship_shipment WHERE shipping_provider <> '' AND shipping_provider IS NOT NULL GROUP BY shipping_provider ORDER BY total DESC LIMIT 5", ARRAY_A );

/* Shipments over time — 12 months ending at the most recent shipment month. */
$tsd_max_ship = $wpdb->get_var( "SELECT MAX(shipping_date) FROM {$wpdb->prefix}trackship_shipment WHERE shipping_date IS NOT NULL" );
$tsd_base     = strtotime( gmdate( 'Y-m-01', $tsd_max_ship ? strtotime( $tsd_max_ship ) : time() ) );
$tsd_trend    = array();
for ( $i = 11; $i >= 0; $i-- ) {
	$ts = strtotime( "-$i month", $tsd_base );
	$tsd_trend[ gmdate( 'Y-m', $ts ) ] = array( 'label' => date_i18n( 'M', $ts ), 'year' => gmdate( 'Y', $ts ), 'count' => 0 );
}
reset( $tsd_trend );
$tsd_trend_start = key( $tsd_trend ) . '-01';
$tsd_trend_rows  = $wpdb->get_results( $wpdb->prepare( "SELECT DATE_FORMAT(shipping_date, '%%Y-%%m') ym, COUNT(*) c FROM {$wpdb->prefix}trackship_shipment WHERE shipping_date >= %s GROUP BY ym", $tsd_trend_start ), ARRAY_A );
foreach ( (array) $tsd_trend_rows as $r ) {
	if ( isset( $tsd_trend[ $r['ym'] ] ) ) {
		$tsd_trend[ $r['ym'] ]['count'] = (int) $r['c'];
	}
}
$tsd_trend_max = 1;
foreach ( $tsd_trend as $m ) {
	if ( $m['count'] > $tsd_trend_max ) {
		$tsd_trend_max = $m['count'];
	}
}

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
$args = array(
	'body'    => json_encode( [ 'user_key' => get_trackship_key() ] ),
	'headers' => array( 'Content-Type' => 'application/json' ),
	'timeout' => 15,
);
$response  = wp_remote_post( $url, $args );
$plan_data = ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) )
	? json_decode( wp_remote_retrieve_body( $response ) )
	: null;
$current_plan    = ! empty( $plan_data->subscription_plan ) ? $plan_data->subscription_plan : get_option( 'user_plan', '' );
$current_balance = ! empty( $plan_data->tracker_balance )   ? $plan_data->tracker_balance   : get_option( 'trackers_balance', '' );
$plan_period     = ! empty( $plan_data->period )            ? $plan_data->period             : get_option( 'plan_period', '' );
if ( $plan_data ) {
	update_option( 'user_plan', $current_plan );
	update_option( 'trackers_balance', $current_balance );
	update_option( 'plan_period', $plan_period );
}

$nonce = wp_create_nonce( 'ts_tools');
$store_text = in_array( $current_plan, array( 'Complimentary 100', 'Complimentary 150', 'Free 20', 'No active plan', 'Trial Ended' ) ) ? __( 'Upgrade to Pro', 'trackship-for-woocommerce' ) : __( 'Account Dashboard', 'trackship-for-woocommerce' );
$store_url = in_array( $current_plan, array( 'Complimentary 100', 'Complimentary 150', 'Free 20', 'No active plan', 'Trial Ended' ) ) ? 'https://my.trackship.com/settings/?utm_source=wpadmin&utm_medium=trackship&utm_campaign=upgrade#billing' : 'https://my.trackship.com/?utm_source=wpadmin&utm_medium=trackship&utm_campaign=dashboard';

// Email of the TrackShip account this store is connected to.
// Uses $plan_data->email when the API returns it; falls back to empty string.
$connected_email = ( $plan_data && ! empty( $plan_data->email ) ) ? $plan_data->email : '';
?>
<input type="hidden" id="ts_tools" name="ts_tools" value="<?php echo esc_attr( $nonce ); ?>" />
<input class="dashboard_hidden_field" type="hidden" value="<?php echo esc_html($current_plan); ?>">
<?php
/* Inline SVG icon map keeps the dashboard self-contained and lets CSS recolor icons. */
$tsd_stat_icons = array(
	'total_shipment'     => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"/><path d="m3.3 7 8.7 5 8.7-5"/><path d="M12 22V12"/></svg>',
	'active_shipment'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg>',
	'delivered_shipment' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.8 10A10 10 0 1 1 17 3.34"/><path d="m9 11 3 3L22 4"/></svg>',
	'tracking_issues'    => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>',
);
$tsd_action_meta = array(
	'unsent_shipments' => array( 'tone' => 'blue',   'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 12a9 9 0 0 1 9-9 9.75 9.75 0 0 1 6.74 2.74L21 8"/><path d="M21 3v5h-5"/><path d="M21 12a9 9 0 0 1-9 9 9.75 9.75 0 0 1-6.74-2.74L3 16"/><path d="M3 21v-5h5"/></svg>' ),
	'late_shipment'    => array( 'tone' => 'amber',  'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>' ),
	'tracking_issues'  => array( 'tone' => 'red',    'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><path d="M12 9v4"/><path d="M12 17h.01"/></svg>' ),
	'return_to_sender' => array( 'tone' => 'purple', 'icon' => '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 14 4 9 9 4"/><path d="M20 20v-7a4 4 0 0 0-4-4H4"/></svg>' ),
);
?>
<div class="tsd-dashboard">
	<?php if ( ! trackship_for_woocommerce()->is_ast_active() ) { ?>
		<div class="tsd-alert">
			<span class="tsd-alert__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg></span>
			<div class="tsd-alert__body">
				<strong><?php esc_html_e( 'You must have a Shipment Tracking plugin installed to use TrackShip for WooCommerce.', 'trackship-for-woocommerce' ); ?></strong>
				<p><?php esc_html_e( "Include shipment tracking details in your WooCommerce orders, enabling customers to effortlessly monitor their orders. Shipment tracking information will be accessible within customers' accounts, located in the order section, and will also be included in the WooCommerce order completion email.", 'trackship-for-woocommerce' ); ?></p>
			</div>
			<a class="tsd-btn tsd-btn--solid" href="<?php echo esc_url( admin_url( 'plugin-install.php?tab=search&s=AST&plugin-search-input=Search+Plugins' ) ); ?>" target="_blank">
				<span><?php esc_html_e( 'Install Shipment Tracking plugin', 'trackship-for-woocommerce' ); ?></span>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
		</div>
	<?php } ?>

	<!-- Brand-forward overview hero with live status tiles -->
	<section class="tsd-hero">
		<div class="tsd-hero__head">
			<div class="tsd-hero__title">
				<h3><?php esc_html_e( 'Shipping & Delivery Overview', 'trackship-for-woocommerce' ); ?></h3>
				<p><?php esc_html_e( 'A live snapshot of your shipments across the selected period.', 'trackship-for-woocommerce' ); ?></p>
			</div>
			<div class="tsd-tabs dashboard_input_tab">
				<?php foreach ( $array as $key => $val ) { ?>
					<input id="dashboard_<?php echo esc_attr( $key ); ?>" type="radio" name="tabs" class="tab_input <?php echo esc_attr( $val['class'] ); ?>" data-tab="<?php echo esc_attr( $val['time'] ); ?>" <?php echo 'month_to_date' == $key ? 'checked' : ''; ?> >
					<label for="dashboard_<?php echo esc_attr( $key ); ?>" class="tab_label tsd-tab"><?php echo esc_html( $val['label'] ); ?></label>
				<?php } ?>
			</div>
		</div>
		<div class="tsd-stats fullfillment_dashboard_section_content">
			<?php foreach ( $first_line as $key => $value ) { ?>
				<div class="tsd-stat innner_content tsd-stat--<?php echo esc_attr( $key ); ?>">
					<span class="tsd-stat__icon"><?php echo $tsd_stat_icons[ $key ] ?? ''; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
					<span class="tsd-stat__value <?php echo esc_attr( $key ); ?>">0</span>
					<span class="tsd-stat__label"><?php echo esc_html( $value['title'] ); ?></span>
				</div>
			<?php } ?>
			<div class="tsd-stat tsd-stat--avg_transit">
				<span class="tsd-stat__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg></span>
				<span class="tsd-stat__value"><?php echo esc_html( $tsd_avg_transit ); ?><small><?php esc_html_e( 'days', 'trackship-for-woocommerce' ); ?></small></span>
				<span class="tsd-stat__label"><?php esc_html_e( 'Avg. Transit Time', 'trackship-for-woocommerce' ); ?></span>
			</div>
			<div class="tsd-stat tsd-stat--delivered_rate">
				<span class="tsd-stat__icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg></span>
				<span class="tsd-stat__value"><?php echo esc_html( $tsd_delivered_pct ); ?><small>%</small></span>
				<span class="tsd-stat__label"><?php esc_html_e( 'Delivered Rate', 'trackship-for-woocommerce' ); ?></span>
			</div>
		</div>
		<div class="tsd-hero__foot">
			<a target="_blank" href="<?php echo esc_url( admin_url( 'admin.php?page=trackship-shipments' ) ); ?>">
				<span><?php esc_html_e( 'View detailed stats', 'trackship-for-woocommerce' ); ?></span>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
		</div>
	</section>

	<div class="tsd-grid">
		<!-- Action Needed -->
		<section class="tsd-card tsd-card--action">
			<div class="tsd-card__head">
				<h3><?php esc_html_e( 'Action Needed', 'trackship-for-woocommerce' ); ?></h3>
			</div>
			<div class="tsd-actions">
				<?php $tsd_has_action = false; ?>
				<?php foreach ( $action_needed as $key => $value ) { ?>
					<?php if ( $value['count'] > 0 ) { ?>
						<?php
						$tsd_has_action = true;
						$shipment_link  = $value['link'] ?? admin_url( 'admin.php?page=trackship-shipments&status=' . $key );
						$tsd_meta       = $tsd_action_meta[ $key ] ?? array( 'tone' => 'blue', 'icon' => '' );
						?>
						<a class="tsd-action tsd-action--<?php echo esc_attr( $tsd_meta['tone'] ); ?>" href="<?php echo esc_url( $shipment_link ); ?>">
							<span class="tsd-action__icon"><?php echo $tsd_meta['icon']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
							<span class="tsd-action__count"><?php echo esc_html( $value['count'] ); ?></span>
							<span class="tsd-action__text"><?php echo esc_html( $value['title'] ); ?></span>
							<span class="tsd-action__arrow"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg></span>
						</a>
					<?php } ?>
				<?php } ?>
				<?php if ( ! $tsd_has_action ) { ?>
					<div class="tsd-actions__empty">
						<span class="tsd-actions__empty-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M21.8 10A10 10 0 1 1 17 3.34"/><path d="m9 11 3 3L22 4"/></svg></span>
						<p><?php esc_html_e( 'No action needed for Shipments', 'trackship-for-woocommerce' ); ?></p>
						<span class="tsd-actions__empty-sub"><?php esc_html_e( "You're all caught up.", 'trackship-for-woocommerce' ); ?></span>
					</div>
				<?php } ?>
			</div>
		</section>

		<!-- Plan & Balance -->
		<section class="tsd-card tsd-card--plan">
			<div class="tsd-plan">
				<div class="tsd-plan__metrics">
					<div class="tsd-plan__metric">
						<span class="tsd-plan__icon tsd-plan__icon--balance"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M19 7V5a2 2 0 0 0-2-2H5a2 2 0 0 0 0 4h16a1 1 0 0 1 1 1v3"/><path d="M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-3"/><path d="M18 12a2 2 0 0 0 0 4h4v-4Z"/></svg></span>
						<span class="tsd-plan__value"><?php echo esc_html( get_option( 'trackers_balance' ) ); ?></span>
						<span class="tsd-plan__label"><?php esc_html_e( 'Available Balance', 'trackship-for-woocommerce' ); ?></span>
					</div>
					<div class="tsd-plan__metric">
						<span class="tsd-plan__icon tsd-plan__icon--plan"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"><path d="M11.56 3.27a.5.5 0 0 1 .88 0l2.95 5.6a1 1 0 0 0 1.52.3l4.27-3.67a.5.5 0 0 1 .8.52l-2.84 10.25a1 1 0 0 1-.95.73H5.81a1 1 0 0 1-.96-.73L2.02 6.02a.5.5 0 0 1 .8-.52l4.27 3.67a1 1 0 0 0 1.52-.3z"/><path d="M5 21h14"/></svg></span>
						<span class="tsd-plan__value tsd-plan__value--plan"><?php echo isset( $plan_data->subscription_plan ) ? esc_html( $plan_data->subscription_plan ) : '&mdash;'; ?></span>
						<span class="tsd-plan__label"><?php esc_html_e( 'Current Plan', 'trackship-for-woocommerce' ); ?></span>
					</div>
				</div>
				<div class="tsd-plan__account">
					<span class="tsd-plan__account-icon"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="4" width="20" height="16" rx="2"/><path d="m22 7-8.97 5.7a1.94 1.94 0 0 1-2.06 0L2 7"/></svg></span>
					<span class="tsd-plan__account-text">
						<span class="tsd-plan__account-label"><?php esc_html_e( 'Connected with TrackShip account', 'trackship-for-woocommerce' ); ?></span>
						<span class="tsd-plan__account-email"><?php echo esc_html( $connected_email ); ?></span>
					</span>
				</div>
				<a class="tsd-btn tsd-btn--ghost" href="<?php echo esc_url( $store_url ); ?>" target="_blank">
					<span><?php echo esc_html( $store_text ); ?></span>
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
				</a>
			</div>
		</section>
	</div>

	<!-- Shipment status + shipments over time -->
	<div class="tsd-grid tsd-grid--analytics">
		<section class="tsd-card tsd-card--chart">
			<div class="tsd-card__head">
				<h3><?php esc_html_e( 'Shipment Status', 'trackship-for-woocommerce' ); ?></h3>
				<span class="tsd-card__sub"><?php esc_html_e( 'All time', 'trackship-for-woocommerce' ); ?></span>
			</div>
			<div class="tsd-donut-wrap">
				<div class="tsd-donut">
					<svg viewBox="0 0 180 180">
						<circle class="tsd-donut__track" cx="90" cy="90" r="70" fill="none" stroke-width="26"></circle>
						<g transform="rotate(-90 90 90)">
							<?php
							$tsd_circ = 2 * M_PI * 70;
							$tsd_off  = 0;
							foreach ( $tsd_status as $seg ) {
								$len = $tsd_status_total ? ( $seg['count'] / $tsd_status_total ) * $tsd_circ : 0;
								?>
								<circle cx="90" cy="90" r="70" fill="none" stroke="<?php echo esc_attr( $seg['color'] ); ?>" stroke-width="26" stroke-dasharray="<?php echo esc_attr( round( $len, 2 ) . ' ' . round( $tsd_circ - $len, 2 ) ); ?>" stroke-dashoffset="<?php echo esc_attr( round( -$tsd_off, 2 ) ); ?>"></circle>
								<?php
								$tsd_off += $len;
							}
							?>
						</g>
					</svg>
					<div class="tsd-donut__center">
						<span class="tsd-donut__total"><?php echo esc_html( number_format_i18n( $tsd_status_total ) ); ?></span>
						<span class="tsd-donut__cap"><?php esc_html_e( 'Shipments', 'trackship-for-woocommerce' ); ?></span>
					</div>
				</div>
				<ul class="tsd-legend">
					<?php foreach ( $tsd_status as $seg ) { ?>
						<?php $pct = $tsd_status_total ? round( 100 * $seg['count'] / $tsd_status_total ) : 0; ?>
						<li class="tsd-legend__item">
							<span class="tsd-legend__dot" style="background:<?php echo esc_attr( $seg['color'] ); ?>;"></span>
							<span class="tsd-legend__label"><?php echo esc_html( $seg['label'] ); ?></span>
							<span class="tsd-legend__val"><?php echo esc_html( number_format_i18n( $seg['count'] ) ); ?><span class="tsd-legend__pct"><?php echo esc_html( $pct ); ?>%</span></span>
						</li>
					<?php } ?>
				</ul>
			</div>
		</section>

		<section class="tsd-card tsd-card--trend">
			<div class="tsd-card__head">
				<h3><?php esc_html_e( 'Shipments Over Time', 'trackship-for-woocommerce' ); ?></h3>
				<span class="tsd-card__sub"><?php esc_html_e( 'Last 12 months', 'trackship-for-woocommerce' ); ?></span>
			</div>
			<div class="tsd-trend">
				<div class="tsd-trend__bars">
					<?php foreach ( $tsd_trend as $m ) { ?>
						<?php $h = $tsd_trend_max ? round( 85 * $m['count'] / $tsd_trend_max ) : 0; ?>
						<div class="tsd-trend__col" title="<?php echo esc_attr( $m['label'] . ' ' . $m['year'] . ': ' . $m['count'] ); ?>">
							<span class="tsd-trend__bar-wrap">
								<?php if ( $m['count'] > 0 ) { ?>
									<span class="tsd-trend__val"><?php echo esc_html( number_format_i18n( $m['count'] ) ); ?></span>
									<span class="tsd-trend__bar" style="height:<?php echo esc_attr( max( $h, 4 ) ); ?>%;"></span>
								<?php } ?>
							</span>
							<span class="tsd-trend__label"><?php echo esc_html( $m['label'] ); ?></span>
						</div>
					<?php } ?>
				</div>
			</div>
		</section>
	</div>

	<!-- Carriers & destinations + carrier performance -->
	<div class="tsd-grid tsd-grid--insights">
		<section class="tsd-card tsd-card--bars">
			<div class="tsd-card__head">
				<h3><?php esc_html_e( 'Carriers & Destinations', 'trackship-for-woocommerce' ); ?></h3>
				<span class="tsd-card__sub"><?php esc_html_e( 'All time', 'trackship-for-woocommerce' ); ?></span>
			</div>
			<div class="tsd-bars-grid">
				<div class="tsd-bars">
					<h4 class="tsd-bars__title"><span class="tsd-bars__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M14 18V6a2 2 0 0 0-2-2H4a2 2 0 0 0-2 2v11a1 1 0 0 0 1 1h2"/><path d="M15 18H9"/><path d="M19 18h2a1 1 0 0 0 1-1v-3.65a1 1 0 0 0-.22-.62l-3.48-4.35A1 1 0 0 0 17.52 8H14"/><circle cx="7" cy="18" r="2"/><circle cx="17" cy="18" r="2"/></svg></span><?php esc_html_e( 'Top Carriers', 'trackship-for-woocommerce' ); ?></h4>
					<?php if ( $tsd_carriers ) { ?>
						<?php $tsd_cmax = max( 1, (int) $tsd_carriers[0]['c'] ); ?>
						<?php foreach ( $tsd_carriers as $row ) { ?>
							<?php $w = round( 100 * $row['c'] / $tsd_cmax ); ?>
							<div class="tsd-bar">
								<span class="tsd-bar__label"><?php echo esc_html( trackship_for_woocommerce()->actions->get_provider_name( $row['p'] ) ); ?></span>
								<span class="tsd-bar__track"><span class="tsd-bar__fill tsd-bar__fill--carrier" style="width:<?php echo esc_attr( $w ); ?>%;"></span></span>
								<span class="tsd-bar__val"><?php echo esc_html( number_format_i18n( $row['c'] ) ); ?></span>
							</div>
						<?php } ?>
					<?php } else { ?>
						<p class="tsd-bars__empty"><?php esc_html_e( 'No carrier data yet.', 'trackship-for-woocommerce' ); ?></p>
					<?php } ?>
				</div>
				<div class="tsd-bars">
					<h4 class="tsd-bars__title"><span class="tsd-bars__ic"><svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M20 10c0 4.4-8 12-8 12s-8-7.6-8-12a8 8 0 0 1 16 0Z"/><circle cx="12" cy="10" r="3"/></svg></span><?php esc_html_e( 'Top Destinations', 'trackship-for-woocommerce' ); ?></h4>
					<?php if ( $tsd_countries ) { ?>
						<?php $tsd_dmax = max( 1, (int) $tsd_countries[0]['c'] ); ?>
						<?php foreach ( $tsd_countries as $row ) { ?>
							<?php $w = round( 100 * $row['c'] / $tsd_dmax ); ?>
							<div class="tsd-bar">
								<span class="tsd-bar__label"><?php echo esc_html( $row['co'] ); ?></span>
								<span class="tsd-bar__track"><span class="tsd-bar__fill tsd-bar__fill--dest" style="width:<?php echo esc_attr( $w ); ?>%;"></span></span>
								<span class="tsd-bar__val"><?php echo esc_html( number_format_i18n( $row['c'] ) ); ?></span>
							</div>
						<?php } ?>
					<?php } else { ?>
						<p class="tsd-bars__empty"><?php esc_html_e( 'No destination data yet.', 'trackship-for-woocommerce' ); ?></p>
					<?php } ?>
				</div>
			</div>
		</section>

		<section class="tsd-card tsd-card--perf">
			<div class="tsd-card__head">
				<h3><?php esc_html_e( 'Carrier Performance', 'trackship-for-woocommerce' ); ?></h3>
				<span class="tsd-card__sub"><?php esc_html_e( 'All time', 'trackship-for-woocommerce' ); ?></span>
			</div>
			<div class="tsd-perf">
				<div class="tsd-perf__head">
					<span><?php esc_html_e( 'Carrier', 'trackship-for-woocommerce' ); ?></span>
					<span><?php esc_html_e( 'Avg transit', 'trackship-for-woocommerce' ); ?></span>
					<span><?php esc_html_e( 'On-time', 'trackship-for-woocommerce' ); ?></span>
				</div>
				<?php if ( $tsd_carrier_perf ) { ?>
					<?php foreach ( $tsd_carrier_perf as $row ) { ?>
						<?php
						$ot_val  = $row['del_est'] > 0 ? round( 100 * $row['ontime'] / $row['del_est'] ) : null;
						$ot_txt  = null === $ot_val ? '&mdash;' : $ot_val . '%';
						$ot_cls  = null === $ot_val ? 'is-na' : ( $ot_val >= 90 ? 'is-good' : ( $ot_val >= 75 ? 'is-mid' : 'is-low' ) );
						$avg_txt = $row['avg_len'] ? round( $row['avg_len'], 1 ) . ' ' . __( 'days', 'trackship-for-woocommerce' ) : '&mdash;';
						?>
						<div class="tsd-perf__row">
							<span class="tsd-perf__carrier"><?php echo esc_html( trackship_for_woocommerce()->actions->get_provider_name( $row['p'] ) ); ?></span>
							<span class="tsd-perf__transit"><?php echo esc_html( $avg_txt ); ?></span>
							<span class="tsd-perf__ontime <?php echo esc_attr( $ot_cls ); ?>"><?php echo wp_kses_post( $ot_txt ); ?></span>
						</div>
					<?php } ?>
				<?php } else { ?>
					<p class="tsd-bars__empty"><?php esc_html_e( 'No carrier data yet.', 'trackship-for-woocommerce' ); ?></p>
				<?php } ?>
			</div>
		</section>
	</div>
</div>

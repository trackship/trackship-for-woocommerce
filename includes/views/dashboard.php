<?php 

$completed_order_with_tracking = $this->completed_order_with_tracking();		
$completed_order_with_zero_balance = $this->completed_order_with_zero_balance();							
$completed_order_with_do_connection = $this->completed_order_with_do_connection();
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

if ( $completed_order_with_tracking > 0 || $completed_order_with_zero_balance > 0 || $completed_order_with_do_connection > 0 ) {
	$total_orders = $completed_order_with_tracking + $completed_order_with_zero_balance + $completed_order_with_do_connection; ?>
	
	<div class="trackship-notice">
		<?php //%s used for replacement ?>
		<p><?php echo sprintf( esc_html( 'We detected %s Shipped orders from the last 30 days that were not sent to TrackShip, you can bulk send them to TrackShip', 'trackship-for-woocommerce'), esc_html( $total_orders ) ) ; ?></p>
		<button class="button-primary btn_green2 bulk_shipment_status_button"><?php esc_html_e( 'Get Shipment Status', 'trackship-for-woocommerce' ); ?></button>
	</div>
<?php } ?>

<div class="ts-dashboard-widgets-container row">
    <div class="ts-postbox-container col-lg-6">
		<div class="ts-dashboard-widget">
			<div class="ts-widget-header"><h2><?php esc_html_e( 'TrackShip Connection Status', 'trackship-for-woocommerce' ); ?></h2></div>
			<div class="ts-widget-content mh70">
				<div class="ts-widget-row">
					<div class="ts-widget__section">
						<span><?php esc_html_e( 'Connection Status', 'trackship-for-woocommerce' ); ?>: </span>
						<a href="https://trackship.info/my-account/?utm_source=wpadmin&utm_medium=sidebar&utm_campaign=upgrade" class="button-primary button-trackship btn_large" target="_blank" style="float: right;padding: 7px 10px 6px 3px;line-height: 1;" >
							<span class="dashicons dashicons-yes" style="margin:0;"></span>
							<span><?php esc_html_e( 'Connected', 'trackship-for-woocommerce' ); ?></span>
						</a>
					</div>
				</div>
			</div>
		</div>
    </div>
    
    <div class="ts-postbox-container col-lg-6">
    	<div class="ts-dashboard-widget">
			<div class="ts-widget-header"><h2><?php esc_html_e( 'TrackShip Account', 'trackship-for-woocommerce' ); ?></h2></div>
			<div class="ts-widget-content mh70">
				<div class="ts-widget-row">
					<div class="ts-widget__section">
                    	<div style="float:left;" class="subscription_detail">
							<p>
								<span>
									<?php esc_html_e( 'Subscription ', 'trackship-for-woocommerce' ); ?>:
									<?php
									if ( isset( $plan_data->subscription_plan ) ) {
										echo esc_html( $plan_data->subscription_plan );
									}
									?>
								</span>
							</p>
							<p><?php esc_html_e( 'Trackers Balance', 'trackship-for-woocommerce' ); ?>: <?php echo esc_html( get_option('trackers_balance') ); echo isset( $plan_array[ $plan_data->subscription_plan ] ) ? ' / ' . $plan_array[$plan_data->subscription_plan] : ''; ?></p>
						</div>
						<div style="float:right;" class="account_dashboard_btn">
							<a href="https://trackship.info/my-account/?utm_source=wpadmin&utm_medium=sidebar&utm_campaign=upgrade" class="account_dashboard_btn button-primary btn_large btn_outline" target="_blank" ><?php esc_html_e( 'Account Dashboard', 'trackship-for-woocommerce' ); ?></a>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>
    
</div>

<div class="ts-dashboard-widgets-container row">
	
    <div class="ts-postbox-container col-lg-6">
        <div class="ts-dashboard-widget">
			<div class="ts-widget-header">
				<h2>
					<?php esc_html_e( 'Tracking Analytics', 'trackship-for-woocommerce' ); ?>
					<small><?php esc_html_e( 'Last 30 days', 'trackship-for-woocommerce' ); ?></small>
				</h2>
			</div>

			<?php $tracking_analytics = $this->get_tracking_analytics_overview(); ?>
			<div class="ts-widget-content ">
				<div class="ts-widget-row mh100">
					<div class="ts-widget__section ts-widget-rborder ts-widget-bborder">
						<h3><?php esc_html_e( 'Total Shipments', 'trackship-for-woocommerce' ); ?></h3>	
						<span class="ts-widget-analytics-number"><?php echo esc_html( $tracking_analytics['total_shipments'] ); ?></span>
						<span>(<?php echo esc_html( $tracking_analytics['total_orders'] ); ?> <?php esc_html_e( 'Orders', 'woocommerce' ); ?>)</span>
					</div>
					<div class="ts-widget__section ts-widget-bborder">
						<h3><?php esc_html_e( 'Avg Shipment Length', 'trackship-for-woocommerce' ); ?></h3>
						<span class="ts-widget-analytics-number"><?php echo esc_html( round( (int) $tracking_analytics['avg_shipment_length'] ) ); ?></span>
						<span><?php esc_html_e( 'days' ); ?></span>
					</div>
				</div>
				<div class="ts-widget-row mh100">
					<div class="ts-widget__section ts-widget-rborder ts-widget-bborder">
						<h3><?php esc_html_e( 'Active Shipments', 'trackship-for-woocommerce' ); ?></h3>	
						<span class="ts-widget-analytics-number"><?php echo esc_html( $tracking_analytics['active_shipments'] ); ?></span>
					</div>
					<div class="ts-widget__section ts-widget-bborder">
						<h3><?php esc_html_e( 'Delivered', 'trackship-for-woocommerce' ); ?></h3>
						<span class="ts-widget-analytics-number"><?php echo esc_html( $tracking_analytics['delivered_shipments'] ); ?></span>
					</div>
				</div>
			</div>
		</div>
	</div>

	<div class="ts-postbox-container col-lg-6">
        <div class="ts-dashboard-widget">
			<div class="ts-widget-header">
				<h2 class="mh22"><?php esc_html_e( 'Guides', 'trackship-for-woocommerce' ); ?></h2>
			</div>

			<?php $tracking_analytics = $this->get_tracking_analytics_overview(); ?>
			<div class="ts-widget-content ">
				<div class="ts-widget-row ts-widget-guides">
					<div class="ts-widget__section ts-widget-rborder ts-widget-bborder">
						<p>
                        	<a target="_blank" href="https://trackship.info/docs/trackship-for-woocommerce/tracking-page/#customize-the-tracking-page-widget"><span class="dashicons dashicons-arrow-right-alt2"></span></a><?php esc_html_e( 'How to Set up and customize the Tracking Page', 'trackship-for-woocommerce' ); ?>
                        </p>
					</div>
                    <div class="ts-widget__section ts-widget-rborder ts-widget-bborder">
						<p>
							<a target="_blank" href="https://trackship.info/docs/trackship-for-woocommerce/compatibility/sms-for-woocommerce/"><span class="dashicons dashicons-arrow-right-alt2"></span></a><?php esc_html_e( 'Further Engage Customers with Delivery Updates via SMS', 'trackship-for-woocommerce' ); ?>
						</p>
					</div>
                    <div class="ts-widget__section ts-widget-rborder ts-widget-bborder">
						<p>
                        	<a target="_blank" href="https://trackship.info/docs/trackship-for-woocommerce/compatibility/automatewoo/"><span class="dashicons dashicons-arrow-right-alt2"></span></a><?php esc_html_e( 'How to Automate your post-shipping Workflow', 'trackship-for-woocommerce' ); ?>
						</p>
					</div>
                    <div class="ts-widget__section ts-widget-rborder ts-widget-bborder">
						<p>
                        	<a target="_blank" href="https://trackship.info/docs/trackship-for-woocommerce/shipment-status-notifications/"><span class="dashicons dashicons-arrow-right-alt2"></span></a><?php esc_html_e( 'Customize the Shipment Status Email Notifications', 'trackship-for-woocommerce' ); ?>
                        </p>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>	

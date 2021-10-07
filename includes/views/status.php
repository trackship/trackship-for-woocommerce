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
update_option( 'user_plan', $plan_data->subscription_plan );
?>
<?php if ( !function_exists( 'SMSWOO' ) ) { ?>
	<script>
		var smswoo_active = 'no';
	</script>
<?php } ?>
<table class="form-table heading-table">
	<tbody>				
		<tr valign="top">
			<td>
				<h1><?php esc_html_e( 'Status', 'trackship-for-woocommerce' ); ?></h1>
			</td>
		</tr>
	</tbody>
</table>		
<div class="ts-status-content">
	<div class="ts_connected_status">
		<span><?php esc_html_e( 'Connection Status', 'trackship-for-woocommerce' ); ?></span>: <strong style="color:#09d3ac;"><span class="dashicons dashicons-yes" style="margin:0;"></span><?php esc_html_e( 'Connected', 'trackship-for-woocommerce' ); ?></strong>
	</div>
	<span class="ts_subscription">
		<?php esc_html_e( 'Subscription ', 'trackship-for-woocommerce' ); ?>:
		<?php if ( isset( $plan_data->subscription_plan ) ) { ?>
			<strong><?php echo esc_html( $plan_data->subscription_plan ); ?></strong>
		<?php } ?>
	</span>
	<div class="ts_tracker_balance">
		<span><?php esc_html_e( 'Trackers Balance', 'trackship-for-woocommerce' ); ?></span>: <strong> <?php echo esc_html( get_option('trackers_balance') ); ?><?php echo isset( $plan_array[ $plan_data->subscription_plan ] ) ? ' / ' . esc_html( $plan_array[$plan_data->subscription_plan] ) : ''; ?></strong>
	</div>
</div>

<?php
$completed_order_with_tracking = $this->completed_order_with_tracking();		
$completed_order_with_zero_balance = $this->completed_order_with_zero_balance();							
$completed_order_with_do_connection = $this->completed_order_with_do_connection();
$total_orders = $completed_order_with_tracking + $completed_order_with_zero_balance + $completed_order_with_do_connection;
?>
<table class="tools_tab p15" style="border-radius: 5px 5px 0 0;">
	<tbody>				
		<tr valign="top">
			<td>
				<strong><?php esc_html_e( 'Tools', 'trackship-for-woocommerce' ); ?></strong>
			</td>
		</tr>
	</tbody>
</table>
<div class="tools_tab_ts4wc tools_tab" style="border-radius: 0 0 5px 5px; border-top:0;">
	<div class="trackship-notice p15">
		<?php //%s used for replacement ?>
		<p><?php echo sprintf( esc_html( 'We detected %s Shipped orders from the last 30 days that were not sent to TrackShip, you can bulk send them to TrackShip', 'trackship-for-woocommerce'), esc_html( $total_orders ) ) ; ?><button class="button-primary button-trackship bulk_shipment_status_button" <?php echo 0 == $total_orders ? 'disabled' : ''; ?>><?php esc_html_e( 'Get Shipment Status', 'trackship-for-woocommerce' ); ?></button></p>
	</div>
	<div class="tracking-event-delete-notice p15">
		<?php //%s used for replacement ?>
		<p style="line-height:35px;"><?php esc_html_e( 'Delete tracking events for orders delivered more then ', 'trackship-for-woocommerce' ); ?>
			<select name="delete_time" id="delete_time" style="height: 35px;"> 
				<option value="30" selected>30</option>
				<option value="60">60</option>
				<option value="90">90</option>
				<option value="180">180</option>
			</select>
			<?php esc_html_e( ' Days.', 'trackship-for-woocommerce' ); ?>
			<button class="button-primary button-trackship-red bulk_shipment_status_button"><?php esc_html_e( 'Delete Tracking Event details', 'trackship-for-woocommerce' ); ?></button>
			<?php $nonce = wp_create_nonce( 'wc_ast_tools'); ?>
			<input type="hidden" id="wc_ast_tools" name="wc_ast_tools" value="<?php echo esc_attr( $nonce ); ?>" />
		</p>
	</div>
</div>

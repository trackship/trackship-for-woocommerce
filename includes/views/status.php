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
<table class="form-table heading-table">
    <tbody>				
        <tr valign="top">
            <td>
                <h1 style=""><?php esc_html_e( 'Status', 'trackship-for-woocommerce' ); ?></h1>
            </td>
        </tr>
    </tbody>
</table>		
<div class="ts-status-content">
    <div class="subscription_detail">
        <span style="display:block; font-size:15px;">
            <?php esc_html_e( 'Subscription ', 'trackship-for-woocommerce' ); ?>:
            <?php if ( isset( $plan_data->subscription_plan ) ) { ?>
				<strong><?php echo esc_html( $plan_data->subscription_plan ); ?></strong>
			<?php } ?>
        </span>
        <div style="font-size: 14px;padding-top: 10px;">
        	<span><?php esc_html_e( 'Trackers Balance', 'trackship-for-woocommerce' ); ?></span>: <strong> <?php echo esc_html( get_option('trackers_balance') ); echo isset( $plan_array[ $plan_data->subscription_plan ] ) ? ' / ' . $plan_array[$plan_data->subscription_plan] : ''; ?></strong>
		</div>
    </div>
	<div>
        <a href="https://trackship.info/my-account/?utm_source=wpadmin&utm_medium=sidebar&utm_campaign=upgrade" class="button-primary button-trackship btn_large" target="_blank" style="line-height: 35px; margin-right:15px" >
            <span class="dashicons dashicons-yes" style="margin:0;"></span>
            <span><?php esc_html_e( 'Connected', 'trackship-for-woocommerce' ); ?></span>
        </a>
        <span class="account_dashboard_btn">
            <a style="line-height: 35px;" href="https://trackship.info/my-account/?utm_source=wpadmin&utm_medium=sidebar&utm_campaign=upgrade" class="account_dashboard_btn button-primary btn_large btn_outline" target="_blank" ><?php esc_html_e( 'Account Dashboard', 'trackship-for-woocommerce' ); ?></a>
        </span>
    </div>
</div>


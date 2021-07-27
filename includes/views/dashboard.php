<?php $nonce = wp_create_nonce( 'wc_ast_tools'); ?>
<input type="hidden" id="wc_ast_dashboard_tab" name="wc_ast_dashboard_tab" value="<?php echo esc_attr( $nonce ); ?>" />
<?php if ( trackship_for_woocommerce()->is_trackship_connected() ) { ?>
    <?php if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) { ?>
		<input type="hidden" class="disable_pro" name="disable_pro" value="disable_pro">
	<?php } ?>
    <div class="flexcontainer">
        <div class="flexcolumn right_border">
            <div class="shipping_time">
                <select class="select_option" name="shipping_time" id="shipping_time"> 
                    <option value="1">Today</option>
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                    <option value="60" selected>Last 60 Days</option>
                </select>
            </div>
        </div>
        <div class="flexcolumn right_border fullfillment_counter">
            <strong class="total_shipments_count"></strong>
            <span style="display:block;">Total Shipments</span>	
        </div>
        <div class="flexcolumn right_border fullfillment_counter"> 
            <strong class="active_shipments_count"></strong>
            <span class="percentage active_shipments_percent"></span>
            <span style="color: #005b9a; display:block;">Active Shipments</span>
        </div>
        <div class="flexcolumn right_border fullfillment_counter"> 
            <strong class="delivered_shipments_count"></strong>
            <span class="percentage delivered_shipments_percent"></span>
            <span style="color: #09d3ac; display:block;">Delivered</span>
        </div>
        <div class="flexcolumn fullfillment_counter"> 
            <strong class="avg_shipment_length_count"><?php //echo esc_html( $tracking_analytics['avg_shipment_length'] ); ?></strong>
            <span style="display:block;">Avg. Shipping Time</span>
        </div>
    </div>
    <div><h2><?php esc_html_e( 'Shipments Dashboard', 'trackship-for-woocommerce' ); ?></h2></div>

    <?php $ship_status = array(
        'all_tracking_statuses' => 'All Tracking Statuses',
        'pre_transit' => 'Pre Transit',
        'in_transit' => 'In Transit',
        'exception' => 'Exception',
        'out_for_delivery' => 'Out for Delivery',
        'return_to_sender' => 'Return to Sender',
        'available_for_pickup' => 'Available for Pickup',
        'late_shipment' => 'Late Shipments'
    ); ?>
    <div>
        <select class="select_option" name="active_shipment" id="active_shipment"> 
            <option value="active">Active</option>
            <option value="delivered">Delivered</option>
        </select>
        <select  class="dashboard_shipment_status select_option" name="shipment_status" id="shipment_status"> 
            <?php foreach ( $ship_status as $key => $val ) { ?>
                <option value="<?php echo esc_html( $key ); ?>"><?php echo esc_html( $val ); ?></option>
            <?php } ?>
        </select> 
        <span style="float:right">
            <input type="text" id="search_bar" name="search_bar" placeholder="Order Number">
            <button class="serch_button" type="button">Search</button>
        </span>  
    </div>
    <?php require_once( trackship_for_woocommerce()->get_plugin_path() . '/includes/shipments/views/trackship_shipments.php' );?>
<?php } else { ?>
	<div class="woocommerce trackship_admin_layout">
		<div class="trackship_admin_content" >
			<div class="trackship_nav_div">	
				<?php include 'trackship-integration.php'; ?>
			</div>
		</div>
	</div>
<?php } ?>

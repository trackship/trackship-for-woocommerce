<?php $nonce = wp_create_nonce( 'wc_ast_tools'); ?>
<input type="hidden" id="wc_ast_dashboard_tab" name="wc_ast_dashboard_tab" value="<?php echo esc_attr( $nonce ); ?>" />
<?php //$tracking_analytics = $this->get_tracking_analytics_overview( 30 ); ?>
<?php if ( trackship_for_woocommerce()->is_trackship_connected() ) { ?>
    <?php /* ?>
	<div class="flexcontainer">
        <div class="flexcolumn right_border">
            <div class="shipping_time">
            	<select class="select_option" name="shipping_time" id="shipping_time"> 
					<option value="1">Today</option>
                    <option value="7">Last 7 Days</option>
                    <option value="30" selected>Last 30 Days</option>
                </select>
			</div>
        </div>
        <div class="flexcolumn right_border fullfillment_counter">
            <strong class="total_shipments_count"><?php echo esc_html( $tracking_analytics['total_shipments'] ); ?></strong>
            <span>Total Shipments</span>	
        </div>
        <div class="flexcolumn right_border fullfillment_counter"> 
            <strong class="active_shipments_count"><?php echo esc_html( $tracking_analytics['active_shipments'] ); ?></strong>
            <span style="color: #005b9a;">Active Shipments</span>
        </div>
        <div class="flexcolumn right_border fullfillment_counter"> 
            <strong class="delivered_shipments_count"><?php echo esc_html( $tracking_analytics['delivered_shipments'] ); ?></strong>
            <span style="color: #59c889;">Delivered</span>
        </div>
        <div class="flexcolumn fullfillment_counter"> 
            <strong class="avg_shipment_length_count"><?php echo esc_html( $tracking_analytics['avg_shipment_length'] ); ?></strong>
            <span>Avg. Shipping Time</span>
        </div>
    </div>
	<?php */ ?>
	<div><h2><?php esc_html_e( 'Shipments Dashboard', 'trackship-for-woocommerce' ); ?></h2></div>

	<?php $ship_status = array(
        'all_tracking_statuses' => 'All Tracking Statuses',
        'pre_transit' => 'Pre Transit',
        'in_transit' => 'In Transit',
        'exception' => 'Exception',
        'out_for_delivery' => 'Out for Delivery',
        'return_to_sender' => 'Return to Sender',
        'available_for_pickup' => 'Available for Pickup',
        //'late_shipment' => 'Late Shipments'
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
        <!--<select class="select_option" name="ts4wc_shipment_times" id="ts4wc_shipment_times"> 
            <option value="all_shipping_times">All Shipping Times</option>
            <option value="1-3">1-3 days</option>
            <option value="3-7">3-7 days</option>
            <option value="7-10">7-10 days</option>
            <option value="10+">10+</option>
        </select>-->
        <span style="float:right">
            <input type="text" id="search_bar" name="search_bar" placeholder="Order ID">
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

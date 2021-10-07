<?php 
$statuses = array(	
	'pre_transit',
	'in_transit',
	'available_for_pickup',
	'out_for_delivery',
	'delivered',
	'return_to_sender',
	'failure',
	'unknown',
	'NULL',
	'pending',
	'INVALID_TRACKING_NUM',
	'carrier_unsupported',
	'wrong_shipping_provider'
);
global $typenow, $wpdb;
//$default_shippment_provider = $wpdb->get_results( "SELECT * FROM $this->table WHERE display_in_order = 1" );
?>
<div class="trackship_admin_content">	
	<section class="trackship_analytics_section">
		<div class="woocommerce trackship_admin_layout">		
			<div class="">			
				<input type="hidden" id="nonce_trackship_shipments" value="<?php echo wp_create_nonce( "_trackship_shipments" );?>">
                <table class="widefat dataTable fixed fullfilments_table" cellspacing="0" id="active_shipments_table" style="width: 100%;">
                    <thead>
                        <tr class="tabel_heading_th">
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipping Date', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Order', 'woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipment Status', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipping Provider', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('Tracking number', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Ship To', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipping Time', 'trackship-for-woocommerce'); ?></th>
							<th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('Delivery date', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Actions', 'trackship-for-woocommerce'); ?></th>
						</tr>
                    </thead>
                    <tbody></tbody>				
                </table>
			</div>		
		</div>
	</section>
</div>

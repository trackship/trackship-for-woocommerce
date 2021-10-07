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
                <table class="widefat dataTable fixed fullfilments_table" cellspacing="0" id="shipments_table" style="width: 100%;">
                    <thead>
                        <tr class="tabel_heading_th">
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php _e('Shipping Date', 'ast-pro'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php _e('Order', 'woocommerce'); ?></th>							
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php _e('Shipment Status', 'ast-pro'); ?></th>							
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php _e('Shipping Provider', 'ast-pro'); ?></th>
                            <th id="columnname" class="manage-column column-destination" scope="col"><?php _e('Tracking Number', 'ast-pro'); ?></th>
							<th id="columnname" class="manage-column column-destination" scope="col"><?php _e('Est. delivery', 'ast-pro'); ?></th>							
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php _e('Ship To', 'ast-pro'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php _e('Shipping Time', 'ast-pro'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php _e('Actions', 'ast-pro'); ?></th>
                        </tr>
                    </thead>
                    <tbody></tbody>				
                </table>
			</div>		
		</div>
	</section>
</div>

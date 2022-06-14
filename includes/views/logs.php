<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
global $wpdb;
$log_table = $wpdb->prefix . 'zorem_email_sms_log';
$all_shipment_status = $wpdb->get_results( "SELECT shipment_status FROM {$log_table} WHERE `type` = 'Email' OR `sms_type` = 'shipment_status' GROUP BY shipment_status" );
?>
<div class="trackship_logs_option">
    <span class="log_shipment_status">
		<select class="select_option" name="log_shipment_status" id="log_shipment_status">
            <option value=""><?php esc_html_e('All Notifications', 'trackship-for-woocommerce'); ?></option>
			<?php foreach ( $all_shipment_status as $status ) { ?>
                <option value="<?php echo esc_html( $status->shipment_status ); ?>"><?php echo esc_html( apply_filters("trackship_status_filter", $status->shipment_status ) ); ?></option>
			<?php } ?>
		</select>
	</span>
    <span class="log_type">
        <select class="select_option" name="log_type" id="log_type">
            <option value=""><?php esc_html_e('All Types', 'trackship-for-woocommerce'); ?></option>
            <option value="Email"><?php esc_html_e('Emails', 'trackship-for-woocommerce'); ?></option>
            <option value="SMS"><?php esc_html_e('SMS', 'trackship-for-woocommerce'); ?></option>
        </select>
	</span>
    <button class="serch_button" type="button" style="float:right;"><?php esc_html_e( 'Search', 'trackship-for-woocommerce' ); ?></button>
    <span class="log_search_bar">
		<input type="text" id="search_bar" name="search_bar" placeholder="Order id, Email, Phone number">
        <span class="dashicons dashicons-no"></span>
	</span>
</div>
<div class="trackship_admin_content">	
	<section class="trackship_logs_section">
		<div class="woocommerce trackship_admin_layout">		
			<div class="">			
				<input type="hidden" id="nonce_trackship_logs" value="<?php echo wp_create_nonce( "_trackship_logs" );?>">
                <table class="widefat dataTable fixed trackship_logs hover" cellspacing="0" id="trackship_notifications_logs" style="width: 100%;">
                    <thead>
                        <tr class="tabel_heading_th">
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Order', 'woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Shipment status', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Time', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-destination" scope="col"><?php esc_html_e('To', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Type', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Status', 'trackship-for-woocommerce'); ?></th>
                            <th id="columnname" class="manage-column column-columnname" scope="col"><?php esc_html_e('Actions', 'trackship-for-woocommerce'); ?></th>
						</tr>
                    </thead>
                    <tbody></tbody>				
                </table>
			</div>		
		</div>
	</section>
</div>

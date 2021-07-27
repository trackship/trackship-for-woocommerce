<?php
$completed_order_with_tracking = $this->completed_order_with_tracking();		
$completed_order_with_zero_balance = $this->completed_order_with_zero_balance();							
$completed_order_with_do_connection = $this->completed_order_with_do_connection();
$total_orders = $completed_order_with_tracking + $completed_order_with_zero_balance + $completed_order_with_do_connection;
$cookie = isset( $_COOKIE["Notice"] ) ? $_COOKIE["Notice"] : '';
if ( 'delete' != $cookie && $total_orders > 0 ) { ?>
    <div class="tools_tab_ts4wc">
        <div class="trackship-notice" style="border: 0;">
            <?php //%s used for replacement ?>
            <p><?php echo sprintf( esc_html( 'We detected %s Shipped orders from the last 30 days that were not sent to TrackShip, you can bulk send them to TrackShip', 'trackship-for-woocommerce'), esc_html( $total_orders ) ) ; ?><span style="float:right;padding-top: 7px;font-weight: 600;" class="dashicons remove-icon dashicons-no-alt"></span></p>
            <button style="float: none;" class="button-primary button-trackship bulk_shipment_status_button" <?php echo 0 == $total_orders ? 'disabled' : ''; ?>><?php esc_html_e( 'Get Shipment Status', 'trackship-for-woocommerce' ); ?></button>
        </div>
    </div>
<?php } ?>
<form method="post" id="wc_ast_trackship_form" action="" enctype="multipart/form-data">
	<div class="outer_form_table">
		<table class="form-table heading-table">
			<tbody>				
				<tr valign="top">
					<td>
						<h1 style=""><?php esc_html_e( 'Settings', 'trackship-for-woocommerce' ); ?></h1>
					</td>
				</tr>
			</tbody>
		</table>		
		<?php $this->get_html_ul( $this->get_trackship_general_data() ); ?>												
	</div>
    <div class="outer_form_table">
    	<div style="margin:15px 0;">
            <input type="hidden" name="wc_ast_status_delivered" value="0"/>
            <input class="ast-tgl ast-tgl-flat ts_order_status_toggle" id="wc_ast_status_delivered" name="wc_ast_status_delivered" type="checkbox" <?php echo get_option( 'wc_ast_status_delivered' ) ? 'checked' : ''; ?> value="1"/>
            <label class="ast-tgl-btn ast-tgl-btn-green" for="wc_ast_status_delivered"></label>
            <span style="margin-left: 5px;"><?php esc_html_e( 'Enable Order Delivery Automation', 'trackship-for-woocommerce' ); ?></span>
            <span class="woocommerce-help-tip tipTip" title="<?php esc_html_e( 'Enable a Custom Order Status Delivered that will be set automatically when all the order shipments are delivered', 'trackship-for-woocommerce' ); ?>"></span>
		</div>
        <div class="ts4wc_delivered_color">
        	<div class="order-label wc-delivered">
				<?php 
                if ( get_option('wc_ast_status_delivered') ) {
                    esc_html_e( wc_get_order_status_name( 'delivered' ), 'trackship-for-woocommerce' );	
                } else {
                    esc_html_e( 'Delivered', 'trackship-for-woocommerce' );
                }
                ?>
            </div>
            <input class="input-text regular-input color_input" type="text" name="wc_ast_status_label_color" id="wc_ast_status_label_color" style="" value="<?php echo esc_html( get_option( 'wc_ast_status_label_color', '#09d3ac' ) ); ?>" placeholder="">
            <select class="select ts_custom_order_color_select" id="wc_ast_status_label_font_color" name="wc_ast_status_label_font_color">	
                <option value="#fff" <?php echo '#fff' == get_option('wc_ast_status_label_font_color', '#fff') ? 'selected' : ''; ?>> <?php esc_html_e( 'Light Font', 'trackship-for-woocommerce' ); ?>
                </option>
                <option value="#000" <?php echo '#000' == get_option('wc_ast_status_label_font_color', '#fff') ? 'selected' : ''; ?>><?php esc_html_e( 'Dark Font', 'trackship-for-woocommerce' ); ?>
                </option>
            </select>							
		</div>
        <?php $late_shipments_email_settings = get_option('late_shipments_email_settings');
		$wcast_late_shipments_days = isset( $late_shipments_email_settings['wcast_late_shipments_days'] ) ? $late_shipments_email_settings['wcast_late_shipments_days'] : ''; ?>
        <div style="margin-top:10px">
            <label for=""><?php esc_html_e('Number of days for late shipments', 'trackship-for-woocommerce'); ?></label>	
            <input class="input-text" type="number" name="wcast_late_shipments_days" id="wcast_late_shipments_days" min="1" value="<?php echo esc_html( $wcast_late_shipments_days ); ?>" style="margin-left: 20px;width: 50px;">
		</div>
        <div class="settings_ul_submit" style="margin-top: 15px;">								
            <button name="save" class="button-primary button-trackship btn_large" type="submit" value="Save changes">
                <?php esc_html_e( 'Save Changes', 'trackship-for-woocommerce' ); ?>
            </button>
            <div class="spinner"></div>
			<?php wp_nonce_field( 'wc_ast_trackship_form', 'wc_ast_trackship_form_nonce' ); ?>
            <input type="hidden" name="action" value="wc_ast_trackship_form_update">
        </div>      	    
    </div>
</form>
<?php do_action( 'after_trackship_settings' );
	
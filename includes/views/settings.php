<form method="post" id="wc_ast_trackship_form" action="" enctype="multipart/form-data">
	<div class="outer_form_table">
		<table class="form-table heading-table">
			<tbody>				
				<tr valign="top">
					<td>
						<h3 style=""><?php _e( 'General Settings', 'trackship-for-woocommerce' ); ?></h3>
					</td>
					<td>
						<div class="settings_ul_submit">								
							<div class="spinner"></div>
							<button name="save" class="button-primary button-trackship btn_large" type="submit" value="Save changes">
								<?php _e( 'Save Changes', 'trackship-for-woocommerce' ); ?>
                            </button>
							<?php wp_nonce_field( 'wc_ast_trackship_form', 'wc_ast_trackship_form_nonce' );?>
							<input type="hidden" name="action" value="wc_ast_trackship_form_update">
						</div>
					</td>
				</tr>
			</tbody>
		</table>		
		<?php $this->get_html_ul( $this->get_trackship_general_data() ); ?>												
	</div>
</form>
        
<form method="post" id="wc_ast_trackship_automation_form" action="" enctype="multipart/form-data">
	<div class="outer_form_table">	
		<table class="form-table heading-table">
			<tbody>
				<tr valign="top">
					<td>
						<h3><?php esc_html_e( 'Automation', 'trackship-for-woocommerce' ); ?></h3>
                        <p><?php esc_html_e( 'Enable a Custom Order Status Delivered that will be set automatically when all the order shipments are delivered', 'trackship-for-woocommerce' ); ?></p>
						<?php wp_nonce_field( 'wc_ast_trackship_automation_form', 'wc_ast_trackship_automation_form_nonce' );?>
						<input type="hidden" name="action" value="wc_ast_trackship_automation_form_update">	
					</td>						
				</tr>
			</tbody>
		</table>
		<table class="form-table order-status-table">
			<tbody>					
						<tr valign="top" class="delivered_row <?php if(!get_option('wc_ast_status_delivered')){echo 'disable_row'; } ?>">
							<td class="forminp">
								<input type="hidden" name="wc_ast_status_delivered" value="0"/>
								<input class="ast-tgl ast-tgl-flat ts_order_status_toggle" id="wc_ast_status_delivered" name="wc_ast_status_delivered" type="checkbox" <?php if(get_option('wc_ast_status_delivered')){echo 'checked'; } ?> value="1"/>
								<label class="ast-tgl-btn ast-tgl-btn-green" for="wc_ast_status_delivered"></label>		
							</td>
							<td class="forminp status-label-column">
								<span class="order-label wc-delivered">
									<?php 
									if(get_option('wc_ast_status_delivered')){
										_e( wc_get_order_status_name( 'delivered' ), 'trackship-for-woocommerce' );	
									} else{
										_e( 'Delivered', 'trackship-for-woocommerce' );
									} ?>
								</span>
							</td>								
							<td class="forminp">							
								<fieldset>
									<input class="input-text regular-input color_input" type="text" name="wc_ast_status_label_color" id="wc_ast_status_label_color" style="" value="<?php echo get_option('wc_ast_status_label_color','#59c889')?>" placeholder="">
									<select class="select ts_custom_order_color_select" id="wc_ast_status_label_font_color" name="wc_ast_status_label_font_color">	
										<option value="#fff" <?php if(get_option('wc_ast_status_label_font_color','#fff') == '#fff'){ echo 'selected'; }?>><?php _e( 'Light Font', 'trackship-for-woocommerce' ); ?></option>
										<option value="#000" <?php if(get_option('wc_ast_status_label_font_color','#fff') == '#000'){ echo 'selected'; }?>><?php _e( 'Dark Font', 'trackship-for-woocommerce' ); ?></option>
									</select>							
								</fieldset>
							</td>
						</tr>
					</tbody>
				</table>	
			</div>	
		</form>
		
        <?php do_action( 'after_trackship_settings' );?>

<?php if ( ! trackship_for_woocommerce()->is_ast_active() ) : ?>
    <div class="d_table" style="">		
        <form method="post" id="trackship_mapping_form" action="" enctype="multipart/form-data">
            <div class="outer_form_table border_0">				
                <table class="form-table heading-table">
                    <tbody>
                        <tr valign="top">
                            <td>
								<div class="settings_ul_submit" style="float:right;" >								
                                    <div class="spinner"></div>
                                        <button name="save" class="button-primary btn_green2 btn_large woocommerce-save-button button-trackship" type="submit"><?php esc_html_e( 'Save Changes', 'trackship-for-woocommerce' ); ?></button>							
                                        <?php wp_nonce_field( 'trackship_mapping_form', 'trackship_mapping_form_nonce' ); ?>
                                        <input type="hidden" name="action" value="trackship_mapping_form_update">
                                </div>
                                <h3><?php esc_html_e( 'Map Shipping Providers', 'trackship-for-woocommerce' ); ?></h3>
                                <p><?php esc_html_e( "Map the Shipping Providers names that you add to orders with TrackShip's shipping provider names", 'trackship-for-woocommerce' ); ?></p>
                            </td>
                        </tr>
                    </tbody>
                </table>
                <table class="form-table fixed map-provider-table">
                    <thead>
                        <tr class="ptw_provider_border">
                            <th><?php esc_html_e( 'Shipping Provider', 'trackship-for-woocommerce' ); ?></th>
                            <th><?php esc_html_e( 'TrackShip Provider', 'trackship-for-woocommerce' ); ?></th>
                        </tr>
                    </thead>
                <tbody>
                <?php 
                $trackship_map_provider = get_option( 'trackship_map_provider' );
                $ts_shippment_providers = $this->get_trackship_provider();
                if ( !empty( $trackship_map_provider ) ) :
                    foreach ( $trackship_map_provider as $key => $val ) : 
                        ?>
                <tr>
                    <td>
                        <input type="text" class="map_shipping_provider_text" name="detected_provider[]" value="<?php esc_html_e( $key ); ?>">
                    </td>
                    <td>
                        <select name="ts_provider[]" class="select2">
                            <option value=""><?php esc_html_e( 'Select' ); ?></option>
                            <?php foreach( $ts_shippment_providers as $ts_provider ) { ?>
                                <option value="<?php echo $ts_provider->ts_slug; ?>" <?php esc_html_e( $ts_provider->ts_slug == $val ? 'selected' : '' ); ?> ><?php echo $ts_provider->provider_name; ?></option>	
                            <?php } ?>
                        </select>
                        <span class="dashicons dashicons-no-alt remove_custom_maping_row"></span>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>	
    <button class="button-primary add_custom_mapping_h3 button-trackship"><?php esc_html_e('Add custom mapping', 'trackship-for-woocommerce' ); ?><span class="dashicons dashicons-plus ptw-dashicons"></span></button>
        </div>
    </form>		
</div>				
<?php endif; ?>	
<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$url = 'https://my.trackship.com/api/user-plan/get/';
$args[ 'body' ] = array(
	'user_key' => trackship_for_woocommerce()->actions->get_trackship_key(), // Deprecated since 19-Aug-2022
);
$args['headers'] = array(
	'trackship-api-key' => trackship_for_woocommerce()->actions->get_trackship_key()
);
$response = wp_remote_post( $url, $args );
if ( is_wp_error( $response ) ) {
	$plan_data = array();
} else {
	$plan_data = json_decode( $response[ 'body' ] );					
}
update_option( 'user_plan', $plan_data->subscription_plan );
if ( ! function_exists( 'SMSWOO' ) && !is_plugin_active( 'zorem-sms-for-woocommerce/zorem-sms-for-woocommerce.php' ) ) {
	?>
	<script>
		var smswoo_active = 'no';
	</script>
	<?php 
} else {
	?>
	<script>
		var smswoo_active = 'yes';
	</script>
	<?php 
}
?>
<div class="accordion_container">
	<form method="post" id="wc_ast_trackship_form" action="" enctype="multipart/form-data">
		<div class="outer_form_table">
			<div class="heading_panel section_settings_heading">
				<strong><?php esc_html_e( 'General Settings', 'trackship-for-woocommerce' ); ?></strong>
				<div class="heading_panel_save">
					<span class="dashicons dashicons-arrow-right-alt2"></span>
					<div class="spinner"></div>
					<button name="save" class="button-primary button-trackship btn_large woocommerce-save-button" type="submit" value="Save & close">
						<?php esc_html_e( 'Save & close', 'trackship-for-woocommerce' ); ?>
					</button>
					<?php wp_nonce_field( 'wc_ast_trackship_form', 'wc_ast_trackship_form_nonce' ); ?>
					<input type="hidden" name="action" value="wc_ast_trackship_form_update">
				</div>
			</div>
			<div class="panel_content section_settings_content">
				<?php $this->get_html_ul( $this->get_trackship_general_data() ); ?>
				<div class="settings_toogle">
					<input type="hidden" name="wc_ast_status_delivered" value="0"/>
					<input class="ast-tgl ast-tgl-flat ts_order_status_toggle" id="wc_ast_status_delivered" name="wc_ast_status_delivered" type="checkbox" <?php echo get_option( 'wc_ast_status_delivered' ) ? 'checked' : ''; ?> value="1"/>
					<label class="ast-tgl-btn ast-tgl-btn-green" for="wc_ast_status_delivered"></label>
					<label class="setting_ul_tgl_checkbox_label">
						<span><?php esc_html_e( 'Enable Order Delivery Automation', 'trackship-for-woocommerce' ); ?></span>
						<span class="woocommerce-help-tip tipTip" title="<?php esc_html_e( 'Enable a Custom Order Status Delivered that will be set automatically when all the order shipments are delivered', 'trackship-for-woocommerce' ); ?>"></span>
					</label>
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
					<input class="input-text regular-input color_input" type="text" name="wc_ast_status_label_color" id="wc_ast_status_label_color" value="<?php echo esc_html( get_option( 'wc_ast_status_label_color', '#09d3ac' ) ); ?>" placeholder="">
					<select class="select ts_custom_order_color_select" id="wc_ast_status_label_font_color" name="wc_ast_status_label_font_color">	
					<option value="#fff" <?php echo '#fff' == get_option('wc_ast_status_label_font_color', '#fff') ? 'selected' : ''; ?>> <?php esc_html_e( 'Light Font', 'trackship-for-woocommerce' ); ?>
					</option>
						<option value="#000" <?php echo '#000' == get_option('wc_ast_status_label_font_color', '#fff') ? 'selected' : ''; ?>><?php esc_html_e( 'Dark Font', 'trackship-for-woocommerce' ); ?>
						</option>
					</select>
				</div>
				<?php
				$late_shipments_days = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
				?>
				<div class="late_shipment_days_settings">
					<label><?php esc_html_e('Number of days for late shipments', 'trackship-for-woocommerce'); ?></label>	
					<input class="input-text" type="number" name="wcast_late_shipments_days" id="wcast_late_shipments_days" min="1" value="<?php echo esc_html( $late_shipments_days ); ?>">
				</div>
				<div class="settings_toogle">
					<input type="hidden" name="enable_email_widget" value="0"/>
					<input class="ast-tgl ast-tgl-flat " id="enable_email_widget" name="enable_email_widget" data-settings="enable_email_widget" type="checkbox" 
					<?php echo get_option( 'enable_email_widget' ) ? 'checked' : ''; ?> value="1"/>
					<label class="ast-tgl-btn ast-tgl-btn-green" for="enable_email_widget"></label>
					<label class="setting_ul_tgl_checkbox_label" for="enable_email_widget">
						<span><?php esc_html_e( 'Enable unsubscribe (opt-out) from Shipment status notifications', 'trackship-for-woocommerce' ); ?></span>
					</label>
				</div>
			</div>
		</div>
	</form>
	<?php do_action( 'after_trackship_settings' ); ?>
	<form method="post" id="trackship_tracking_page_form" action="" enctype="multipart/form-data">
		<div class="heading_panel section_tracking_page_heading">
			<strong><?php esc_html_e( 'Tracking Page', 'trackship-for-woocommerce' ); ?></strong>
			<div class="heading_panel_save">
				<span class="dashicons dashicons-arrow-right-alt2"></span>
				<div class="spinner"></div>
				<button name="save" class="button-primary button-trackship btn_large woocommerce-save-button" type="submit" value="Save & close">
					<?php esc_html_e( 'Save & close', 'trackship-for-woocommerce' ); ?>
				</button>
				<?php wp_nonce_field( 'trackship_tracking_page_form', 'trackship_tracking_page_form_nonce' ); ?>
				<input type="hidden" name="action" value="trackship_tracking_page_form_update">
			</div>
		</div>
		<div class="panel_content section_tracking_page_content">
			<div class="outer_form_table">
				<?php $this->get_html_ul( $this->get_tracking_page_data() ); ?>
			</div>
		</div>
	</form>
	<?php include __DIR__ . '/map-providers.php'; ?>
</div>

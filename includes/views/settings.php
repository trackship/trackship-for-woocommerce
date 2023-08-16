<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$url = 'https://my.trackship.com/api/user-plan/get/';
$args[ 'body' ] = array(
	'user_key' => get_trackship_key(), // Deprecated since 19-Aug-2022
);
$args['headers'] = array(
	'trackship-api-key' => get_trackship_key()
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
$section = isset( $_GET['section'] ) ? sanitize_text_field( $_GET['section'] ) : '';
?>
<div class="accordion_container">
	<form method="post" id="wc_ast_trackship_form" action="" enctype="multipart/form-data">
		<div class="outer_form_table">
			<div class="heading_panel section_settings_heading <?php echo 'general' == $section ? 'checked' : ''; ?>">
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
				<?php
				$late_shipments_days = trackship_for_woocommerce()->ts_actions->get_option_value_from_array('late_shipments_email_settings', 'wcast_late_shipments_days', 7 );
				?>
				<div class="late_shipment_days_settings dis_block">
					<label><?php esc_html_e('Number of days for late shipments', 'trackship-for-woocommerce'); ?></label>	
					<input class="input-text" type="number" name="wcast_late_shipments_days" id="wcast_late_shipments_days" min="1" value="<?php echo esc_html( $late_shipments_days ); ?>">
				</div>
			</div>
		</div>
	</form>
	<?php include __DIR__ . '/delivery-automation.php'; ?>
	<?php include __DIR__ . '/tracking-page.php'; ?>
	<?php do_action( 'after_trackship_settings' ); ?>
	<?php include __DIR__ . '/map-providers.php'; ?>
</div>

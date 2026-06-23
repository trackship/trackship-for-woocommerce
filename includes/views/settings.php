<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( !get_trackship_settings( 'wc_admin_notice', '') ) {
	if ( in_array( get_option( 'user_plan' ), array( 'Complimentary 100', 'Complimentary 150', 'Free 20', 'No active plan', 'Trial Ended' ) ) ) {
		trackship_for_woocommerce()->wc_admin_notice->admin_notices_for_TrackShip_pro();
	}
	trackship_for_woocommerce()->wc_admin_notice->admin_notices_for_TrackShip_review();
	update_trackship_settings( 'wc_admin_notice', 'true');
}
$url = 'https://api.trackship.com/v1/user-plan/get';
$args = array(
	'body'    => json_encode( [ 'user_key' => get_trackship_key() ] ),
	'headers' => array( 'Content-Type' => 'application/json' ),
	'timeout' => 15,
);
$response  = wp_remote_post( $url, $args );
$plan_data = ( ! is_wp_error( $response ) && 200 === wp_remote_retrieve_response_code( $response ) )
	? json_decode( wp_remote_retrieve_body( $response ) )
	: null;
if ( $plan_data && ! empty( $plan_data->subscription_plan ) ) {
	update_option( 'user_plan', $plan_data->subscription_plan );
}
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
<div class="ts-settings-layout">
	<nav class="ts-settings-sidebar" id="ts-settings-nav">
		<div class="ts-nav-item active" data-target="#wc_trackship_form">
			<span class="dashicons dashicons-admin-settings"></span>
			<span class="ts-nav-label"><?php esc_html_e( 'General Settings', 'trackship-for-woocommerce' ); ?></span>
		</div>
		<div class="ts-nav-item" data-target="#trackship_tracking_page_form">
			<span class="dashicons dashicons-location-alt"></span>
			<span class="ts-nav-label"><?php esc_html_e( 'Tracking Page', 'trackship-for-woocommerce' ); ?></span>
		</div>
		<div class="ts-nav-item" data-target=".trackship_sms_settings">
			<span class="dashicons dashicons-smartphone"></span>
			<span class="ts-nav-label"><?php esc_html_e( 'SMS Settings', 'trackship-for-woocommerce' ); ?></span>
		</div>
		<div class="ts-nav-item" data-target=".d_table">
			<span class="dashicons dashicons-networking"></span>
			<span class="ts-nav-label"><?php esc_html_e( 'Map Shipping Carriers', 'trackship-for-woocommerce' ); ?></span>
		</div>
	</nav>
	<div class="ts-settings-main">
		<div class="accordion_container">
			<form method="post" id="wc_trackship_form" action="" enctype="multipart/form-data">
				<div class="">
					<div class="heading_panel section_settings_heading <?php echo 'general' == $section ? 'checked' : ''; ?>">
						<strong><?php esc_html_e( 'General Settings', 'trackship-for-woocommerce' ); ?></strong>
						<div class="heading_panel_save">
							<div class="spinner"></div>
							<button name="save" class="button-primary button-trackship btn_large woocommerce-save-button" type="submit" value="Save">
								<?php esc_html_e( 'Save', 'trackship-for-woocommerce' ); ?>
							</button>
							<?php wp_nonce_field( 'wc_trackship_form', 'wc_trackship_form_nonce' ); ?>
							<input type="hidden" name="action" value="wc_trackship_form_update">
						</div>
					</div>
					<div class="panel_content section_settings_content">
						<?php $this->get_settings_html( $this->get_trackship_general_data() ); ?>
					</div>
				</div>
			</form>
			<?php include __DIR__ . '/tracking-page.php'; ?>
			<?php do_action( 'after_trackship_settings' ); ?>
			<?php include __DIR__ . '/map-providers.php'; ?>
		</div>
	</div>
</div>

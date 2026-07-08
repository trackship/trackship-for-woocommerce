<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<form method="post" class="zorem_plugin_setting_tab_form trackship_sms_settings">
	<div class="heading_panel section_sms_heading">
		<span class="dashicons dashicons-smartphone ts-heading-icon"></span>
		<strong><?php esc_html_e( 'SMS Settings', 'trackship-for-woocommerce' ); ?></strong>
		<?php if ( ! function_exists( 'SMSWOO' ) && ! is_plugin_active( 'zorem-sms-for-woocommerce/zorem-sms-for-woocommerce.php' ) ) { ?>
			<div class="heading_panel_save">
				<div class="spinner workflow_spinner"></div>
				<button name="save" class="button-primary button-trackship btn_large woocommerce-save-button button-smswoo" type="submit"><?php esc_html_e( 'Save changes', 'trackship-for-woocommerce' ); ?></button>
				<?php $nonce = wp_create_nonce( 'smswoo_settings_tab' ); ?>
				<input type="hidden" name="smswoo_settings_tab_nonce" value="<?php echo esc_attr($nonce); ?>">
				<input type="hidden" name="action" value="smswoo_settings_tab_save">
			</div>
		<?php } ?>
	</div>
	<div class="panel_content section_sms_content">
		<?php if ( function_exists( 'SMSWOO' ) || is_plugin_active( 'zorem-sms-for-woocommerce/zorem-sms-for-woocommerce.php' ) ) { ?>
			<div class="ts-plugin-redirect-card">
				<div class="ts-plugin-redirect-card__icon">
					<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/></svg>
				</div>
				<div class="ts-plugin-redirect-card__body">
					<h3 class="ts-plugin-redirect-card__title"><?php esc_html_e( 'SMS Settings managed by SMS for WooCommerce', 'trackship-for-woocommerce' ); ?></h3>
					<p class="ts-plugin-redirect-card__desc"><?php esc_html_e( 'You have the SMS for WooCommerce plugin activated. Configure your SMS gateways and credentials directly from that plugin\'s settings.', 'trackship-for-woocommerce' ); ?></p>
					<a href="<?php echo esc_url( admin_url( 'admin.php?page=sms-for-woocommerce&tab=settings' ) ); ?>" class="button button-primary ts-plugin-redirect-card__btn">
						<?php esc_html_e( 'Go to SMS for WooCommerce Settings', 'trackship-for-woocommerce' ); ?>
						<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
					</a>
				</div>
			</div>
		<?php } else { ?>
			<div class="outer_form_table">
				<?php $this->get_html( $this->get_sms_provider_data() ); ?>
			</div>
		<?php } ?>
	</div>
</form>

<?php
/*
* $license object
*/
$license = trackship_for_woocommerce()->license;
?>
<form method="post" id="tswc-licence-form" class="zorem_plugin_licence_form">
	<table class="form-table widefat tags ui-sortable heading-table">
		<tbody>
			<tr valign="top">
				<td>
					<h3><?php esc_html_e( 'License', 'trackship-for-woocommerce' ); ?></h3>
				</td>
			</tr>
			<tr>
				<td class="license_number">
					<input name="license_key" size="50" type="text" class="textfield license_key" value="<?php echo esc_html( $license->get_license_key() ); ?>" required placeholder="<?php esc_html_e( 'License Key', 'trackship-for-woocommerce' ); ?>" />
					<?php wp_nonce_field( 'zorem-plugin', 'security' ); ?>
					<input type="hidden" name="action" class="licence_action" value="<?php echo $license->get_license_status() ? 'tswc_license_deactivate':'tswc_license_activate'; ?>" />
					<input type="submit" class="button-primary licence_submit button-trackship" name="licence_submit" value="<?php echo $license->get_license_status() ? esc_html( 'Deactivate' ) : esc_html( 'Activate' ); ?>">
					<a href="https://www.zorem.com/my-account/subscriptions/" class="license-activated <?php echo $license->get_license_status() ? '' : 'hidden'; ?>" target="blank" style="color: #59c889;vertical-align: -webkit-baseline-middle;">
						<span class="dashicons dashicons-yes-alt"></span>
					</a>
					<div class="license_message"></div>
				</td>
			</tr>	
		</tbody>
	</table>
</form>

<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<?php $ast_pro_active = is_plugin_active( 'ast-pro/ast-pro.php' ) || is_plugin_active( 'advanced-shipment-tracking-pro/advanced-shipment-tracking-pro.php' ); ?>
<div class="d_table">
	<form method="post" id="trackship_mapping_form" action="" enctype="multipart/form-data">
		<div class="heading_panel section_mapping_heading">
			<span class="dashicons dashicons-networking ts-heading-icon"></span>
			<strong><?php esc_html_e( 'Map Shipping Carriers', 'trackship-for-woocommerce' ); ?></strong>
			<?php if ( ! $ast_pro_active ) { ?>
				<div class="heading_panel_save">
					<div class="spinner"></div>
					<button name="save" class="button button-trackship trackship-save-button" type="submit"><?php esc_html_e( 'Save changes', 'trackship-for-woocommerce' ); ?></button>
					<?php wp_nonce_field( 'trackship_mapping_form', 'trackship_mapping_form_nonce' ); ?>
					<input type="hidden" name="action" value="trackship_mapping_form_update">
				</div>
			<?php } ?>
		</div>
		<div class="panel_content section_mapping_content">
			<div class="">
				<?php if ( ! $ast_pro_active ) { ?>
					<div class="ts-mapping-note">
						<span class="dashicons dashicons-info"></span>
						<p><?php esc_html_e( "This feature lets you align shipping providers from external shipping services with those on TrackShip. You can match the names of the Shipping Providers you receive from your shipping company with TrackShip's provider names.", 'trackship-for-woocommerce' ); ?></p>
					</div>
					<table class="form-table fixed map-provider-table">
						<thead>
							<tr class="ptw_provider_border">
								<th><?php esc_html_e( 'Your shipping provider', 'trackship-for-woocommerce' ); ?></th>
								<th class="ts-mapping-arrow-col"></th>
								<th><?php esc_html_e( 'TrackShip provider', 'trackship-for-woocommerce' ); ?></th>
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
											<input type="text" class="map_shipping_provider_text" name="detected_provider[]" value="<?php echo esc_attr( $key ); ?>">
										</td>
										<td class="ts-mapping-arrow-col">
											<span class="dashicons dashicons-arrow-right-alt2"></span>
										</td>
										<td class="ts-mapping-select-col">
											<select name="ts_provider[]" class="select2">
												<option value=""><?php esc_html_e( 'Select' ); ?></option>
												<?php foreach ( $ts_shippment_providers as $ts_provider ) { ?>
													<option value="<?php echo esc_html( $ts_provider->ts_slug ); ?>" <?php selected( $ts_provider->ts_slug, $val ); ?> ><?php echo esc_html( $ts_provider->provider_name ); ?></option>
												<?php } ?>
											</select>
											<span class="dashicons dashicons-trash remove_custom_maping_row"></span>
										</td>
									</tr>
								<?php endforeach; ?>
							<?php endif; ?>
						</tbody>
					</table>
					<div class="ts-mapping-footer">
						<button type="button" class="button button-trackship" id="add_custom_mapping_btn">
							<span class="dashicons dashicons-plus"></span>
							<?php esc_html_e( 'Add mapping', 'trackship-for-woocommerce' ); ?>
						</button>
						<button type="button" class="button update_shipping_provider btn_outline">
							<span class="dashicons dashicons-update"></span>
							<?php esc_html_e( 'Sync providers', 'trackship-for-woocommerce' ); ?>
						</button>
						<div class="add-custom-mapping spinner"></div>
					</div>
				<?php } else { ?>
					<div class="ts-plugin-redirect-card">
						<div class="ts-plugin-redirect-card__icon">
							<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" width="28" height="28"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
						</div>
						<div class="ts-plugin-redirect-card__body">
							<h3 class="ts-plugin-redirect-card__title"><?php esc_html_e( 'Carrier mapping managed by AST PRO', 'trackship-for-woocommerce' ); ?></h3>
							<p class="ts-plugin-redirect-card__desc"><?php esc_html_e( 'You have the AST PRO plugin activated. The shipping provider name mapping is done on the shipping provider settings in AST PRO.', 'trackship-for-woocommerce' ); ?></p>
							<a href="<?php echo esc_url( admin_url( 'admin.php?page=woocommerce-advanced-shipment-tracking&tab=shipping-providers' ) ); ?>" class="button button-trackship ts-plugin-redirect-card__btn">
								<?php esc_html_e( 'Go to AST PRO Shipping Providers', 'trackship-for-woocommerce' ); ?>
								<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" width="14" height="14"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
							</a>
						</div>
					</div>
				<?php } ?>
			</div>
		</div>
	</form>
</div>

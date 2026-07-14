<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div class="outer_form_table ts_notifications_outer_table">
	<?php
	$late_shipments_email_enable = get_trackship_settings( 'late_shipments_email_enable' );
	$tab_type = isset( $_GET['tab'] ) ? sanitize_text_field($_GET['tab']) : '';	
	
	$ts_notifications = $this->trackship_shipment_status_notifications_data();
	?>
	<div class="ts-settings-sidebar-wrap">
		<nav class="trackship_tab_name">
			<div class="ts-nav-item <?php echo ( 'sms-notification' !== $tab_type && 'admin-notification' !== $tab_type ) ? 'active' : ''; ?>" data-tab="email-notification" data-type="email">
				<span class="dashicons dashicons-email-alt"></span>
				<span class="ts-nav-label"><?php esc_html_e( 'Email Notifications', 'trackship-for-woocommerce' ); ?></span>
			</div>
			<div class="ts-nav-item <?php echo 'sms-notification' === $tab_type ? 'active' : ''; ?>" data-tab="sms-notification" data-type="sms">
				<span class="dashicons dashicons-smartphone"></span>
				<span class="ts-nav-label"><?php esc_html_e( 'SMS Notifications', 'trackship-for-woocommerce' ); ?></span>
			</div>
			<div class="ts-nav-item <?php echo 'admin-notification' === $tab_type ? 'active' : ''; ?>" data-tab="admin-notification" data-type="late-email">
				<span class="dashicons dashicons-admin-users"></span>
				<span class="ts-nav-label"><?php esc_html_e( 'Admin Notifications', 'trackship-for-woocommerce' ); ?></span>
			</div>
		</nav>
		<div class="ts-quick-help">
			<h4 class="ts-quick-help__title"><?php esc_html_e( 'Quick Help', 'trackship-for-woocommerce' ); ?></h4>
			<p class="ts-quick-help__desc"><?php esc_html_e( 'Learn how to configure TrackShip for best results.', 'trackship-for-woocommerce' ); ?></p>
			<a href="https://docs.trackship.com/docs/trackship-for-woocommerce/" target="_blank" rel="noopener noreferrer" class="ts-quick-help__link">
				<?php esc_html_e( 'View Documentation', 'trackship-for-woocommerce' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
			<a href="https://my.trackship.com/?support=1" target="_blank" rel="noopener noreferrer" class="ts-quick-help__link">
				<?php esc_html_e( 'Get Support', 'trackship-for-woocommerce' ); ?>
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/></svg>
			</a>
			<div class="ts-quick-help__illustration" aria-hidden="true">
				<svg viewBox="0 0 160 100" fill="none" xmlns="http://www.w3.org/2000/svg">
					<rect x="20" y="18" width="72" height="88" rx="6" fill="#e8edf8" transform="rotate(-6 20 18)"/>
					<rect x="34" y="14" width="72" height="88" rx="6" fill="#fff" stroke="#dde3f0" stroke-width="1.5"/>
					<rect x="46" y="32" width="48" height="5" rx="2.5" fill="#c5cfe8"/>
					<rect x="46" y="44" width="36" height="5" rx="2.5" fill="#dde3f0"/>
					<rect x="46" y="56" width="42" height="5" rx="2.5" fill="#dde3f0"/>
					<rect x="46" y="68" width="28" height="5" rx="2.5" fill="#dde3f0"/>
					<circle cx="126" cy="68" r="26" fill="#124ed6"/>
					<polyline points="115,68 123,76 138,58" stroke="#fff" stroke-width="3.5" stroke-linecap="round" stroke-linejoin="round"/>
				</svg>
			</div>
		</div>
	</div><!-- /.ts-settings-sidebar-wrap -->
	<section class="inner_tab_section shipment-status-email-section">
		<div class="heading_panel">
			<span class="dashicons dashicons-email-alt ts-heading-icon"></span>
			<strong><?php esc_html_e( 'Email Notifications', 'trackship-for-woocommerce' ); ?></strong>
		</div>
		<?php $nonce = wp_create_nonce( 'tswc_shipment_status_email'); ?>
		<input type="hidden" id="tswc_shipment_status_email" name="tswc_shipment_status_email" value="<?php echo esc_attr( $nonce ); ?>" />
		<table class="form-table shipment-status-email-table">
			<tbody>
				<?php foreach ( $ts_notifications as $key => $val ) { ?>
					<?php $enable_email = get_trackship_email_settings( $key, 'enable' ); ?>
					<tr class="<?php echo 1 == $enable_email ? 'enable' : 'disable'; ?> ">
						<td class="status-label-column">
							<img src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/<?php echo esc_html( $val['img_slug'] ); ?>.png">
							<strong class="shipment-status-label"><?php echo esc_html( $val['title'] ); ?></strong>
							<?php if ( 'delivered' == $key ) { ?>
								<label for="all-shipment-status-<?php echo esc_html( $key ); ?>">
									<input type="hidden" name="all-shipment-status-<?php echo esc_html( $key ); ?>" value="no">
									<input name="all-shipment-status-<?php echo esc_html( $key ); ?>" type="checkbox" id="all-shipment-status-<?php echo esc_html( $key ); ?>" value="yes" <?php echo get_option( 'all-shipment-status-' . $key ) == 1 ? 'checked' : ''; ?> >
									<?php echo esc_html( $val['title2'] ); ?>
									<?php $nonce = wp_create_nonce( 'all_status_delivered'); ?>
									<input type="hidden" id="all_status_delivered" name="all_status_delivered" value="<?php echo esc_attr( $nonce ); ?>" />
								</label>
							<?php } ?>
						</td>
						<td>
							<span class="shipment_status_toggle">
								<input type="hidden" name="<?php echo esc_html( $val['enable_status_name'] ); ?>" value="0"/>
								<input class="tgl tgl-flat" id="<?php echo esc_html( $val['enable_status_name'] ); ?>" name="<?php echo esc_html( $val['enable_status_name'] ); ?>" data-settings="trackship_email_settings" data-status="<?php echo esc_html( $key ); ?>" type="checkbox" <?php echo 1 == $enable_email ? 'checked' : ''; ?> value="yes"/>
								<label class="tgl-btn tgl-btn-green" for="<?php echo esc_html( $val['enable_status_name'] ); ?>"></label>
							</span>
							<a class="edit_customizer_a dashicons dashicons-admin-generic" href="<?php echo esc_html( $val['customizer_url'] ); ?>"></a>
						</td>
					</tr>
				<?php } ?>
			</tbody>
		</table>
		<?php do_action( 'after_shipment_status_email_notifications' ); ?>
	</section>
	<section class="inner_tab_section shipment-status-late-email-section">
		<div class="heading_panel">
			<span class="dashicons dashicons-admin-users ts-heading-icon"></span>
			<strong><?php esc_html_e( 'Admin Notifications', 'trackship-for-woocommerce' ); ?></strong>
		</div>
		<form method="post" id="trackship_late_shipments_form" action="" enctype="multipart/form-data">
			<?php wp_nonce_field( 'ts_late_shipments_email_form', 'ts_late_shipments_email_form_nonce' ); ?>
			<input type="hidden" name="action" value="ts_late_shipments_email_form_update">

			<div class="admin_notifications_div">
				<div class="heading_panel admin_notifications_tr late-shipment-tr <?php echo 1 == $late_shipments_email_enable ? 'enable' : 'disable'; ?>">
					<img class="ts-notif-status-icon" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/late-shipment.png">
					<strong><?php esc_html_e( 'Late Shipments', 'trackship-for-woocommerce' ); ?></strong>
					<div class="heading_panel_save">
						<button name="save" class="button button-trackship trackship-save-button" type="submit" value="Save & close"><?php esc_html_e( 'Save & close', 'trackship-for-woocommerce' ); ?></button>
						<?php trackship_for_woocommerce()->html->get_tgl_checkbox( 'late_shipments_email_enable', array( 'type' => 'tgl_checkbox', 'class' => 'shipment_status_toggle', 'settings' => 'late_shipments_email_settings' ) ); ?>
						<a class="edit_customizer_a dashicons dashicons-admin-generic"></a>
					</div>
				</div>
				<div class="panel_content late-shipments-email-content-table admin_notifiations_content">
					<?php $this->get_settings_html( $this->get_late_shipment_data() ); ?>
				</div>
			</div>

			<div class="admin_notifications_div">
				<div class="heading_panel admin_notifications_tr exception-shipment-tr <?php echo 1 == get_trackship_settings( 'exception_admin_email_enable' ) ? 'enable' : 'disable'; ?>">
					<img class="ts-notif-status-icon" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/failure.png">
					<strong><?php esc_html_e( 'Exception Shipments', 'trackship-for-woocommerce' ); ?></strong>
					<div class="heading_panel_save">
						<button name="save" class="button button-trackship trackship-save-button" type="submit" value="Save & close"><?php esc_html_e( 'Save & close', 'trackship-for-woocommerce' ); ?></button>
						<?php trackship_for_woocommerce()->html->get_tgl_checkbox( 'exception_admin_email_enable', array( 'type' => 'tgl_checkbox', 'class' => 'shipment_status_toggle', 'settings' => 'exception_admin_email' ) ); ?>
						<a class="edit_customizer_a dashicons dashicons-admin-generic"></a>
					</div>
				</div>
				<div class="panel_content exception-shipments-email-content-table admin_notifiations_content">
					<?php $this->get_settings_html( $this->get_exception_shipment_data() ); ?>
				</div>
			</div>

			<div class="admin_notifications_div">
				<div class="heading_panel admin_notifications_tr on-hold-shipment-tr <?php echo 1 == get_trackship_settings( 'on_hold_admin_email_enable' ) ? 'enable' : 'disable'; ?>">
					<img class="ts-notif-status-icon" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/css/icons/on-hold.png">
					<strong><?php esc_html_e( 'On Hold Shipments', 'trackship-for-woocommerce' ); ?></strong>
					<div class="heading_panel_save">
						<button name="save" class="button button-trackship trackship-save-button" type="submit" value="Save & close"><?php esc_html_e( 'Save & close', 'trackship-for-woocommerce' ); ?></button>
						<?php trackship_for_woocommerce()->html->get_tgl_checkbox( 'on_hold_admin_email_enable', array( 'type' => 'tgl_checkbox', 'class' => 'shipment_status_toggle', 'settings' => 'on_hold_admin_email' ) ); ?>
						<a class="edit_customizer_a dashicons dashicons-admin-generic"></a>
					</div>
				</div>
				<div class="panel_content exception-shipments-email-content-table admin_notifiations_content">
					<?php $this->get_settings_html( $this->get_on_hold_shipment_data() ); ?>
				</div>
			</div>
		</form>
	</section>
	<section class="inner_tab_section shipment-status-sms-section">
		<div class="heading_panel">
			<span class="dashicons dashicons-smartphone ts-heading-icon"></span>
			<strong><?php esc_html_e( 'SMS Notifications', 'trackship-for-woocommerce' ); ?></strong>
		</div>
		<?php if ( ! function_exists( 'SMSWOO' ) && in_array( get_option( 'user_plan' ), array( 'Complimentary 100', 'Complimentary 150', 'Free 20', 'No active plan', 'Trial Ended' ) ) ) { ?>
			<input type="hidden" class="disable_pro" name="disable_pro" value="disable_pro">
		<?php } ?>
		<?php do_action( 'shipment_status_sms_section' ); ?>
	</section>
</div>

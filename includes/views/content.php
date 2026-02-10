<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$menu_tab = isset( $_GET[ 'tab' ] ) ? sanitize_text_field( $_GET[ 'tab' ] ) : 'settings';
?>
<div class="woocommerce trackship_admin_layout">
	<span class="ts_activity-panel"><?php include 'header-sidebar.php'; ?></span>
	<div class="trackship_admin_content" >
		<div class="trackship_nav_div">
			<?php
			$array = array(
				'settings' => array(
					'label'	=> __( 'Settings', 'trackship-for-woocommerce' ),
					'slug'	=> 'settings',
					'show'	=> true,
				),
				'notifications' => array(
					'label'	=> __( 'Notifications', 'trackship-for-woocommerce' ),
					'slug'	=> 'notifications',
					'show'	=> true,
				),
				'integrations' => array(
					'label'	=> __( 'Integrations', 'trackship-for-woocommerce' ),
					'slug'	=> 'integrations',
					'show'	=> true,
				),
				'tools' => array(
					'label'	=> __( 'Tools', 'trackship-for-woocommerce' ),
					'slug'	=> 'tools',
					'show'	=> true,
				),
				'setup' => array(
					'label'	=> __( 'Setup', 'trackship-for-woocommerce' ),
					'slug'	=> 'setup',
					'show'	=> true,
				),
			);

			if ( WC_VERSION < '10.2' || trackship_for_woocommerce()->is_active_fulfillments() ) {
				unset( $array['setup'] ); // Remove Setup tab if WooCommerce version is less than 10.2 or WooCommerce Fulfillments is active
			}

			// Fallback when the requested tab is no longer available (e.g., setup removed after enabling fulfillments).
			if ( ! array_key_exists( $menu_tab, $array ) && 'setup' == $menu_tab ) {
				$menu_tab = 'settings';
			}

			?>
			<div>
				<?php foreach ( $array as $key => $val ) { ?>
					<?php 
					if ( 'notifications' == $val[ 'slug' ] ) {
						$checked = in_array( $menu_tab, array( $val[ 'slug' ], 'email-notification', 'sms-notification', 'admin-notification' ) ) ? 'checked' : '';
					} else {
						$checked = $val[ 'slug' ] == $menu_tab ? 'checked' : '';
					}
					?>
					<input id="tab_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" type="radio" name="tabs" class="tab_input" data-label="<?php esc_html_e( $val[ 'label' ] ); ?>" data-tab="<?php esc_html_e( $val[ 'slug' ] ); ?>" <?php esc_html_e( $checked ); ?> >
					<label for="tab_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" class="tab_label <?php echo 'settings' == $val[ 'slug' ] ? 'first_label' : ''; ?>">
						<?php esc_html_e( $val[ 'label' ] ); ?>
					</label>
				<?php } ?>
			</div>
			<div class="menu_devider"></div>
			<?php $this->get_trackship_notice_msg(); ?>
			<?php foreach ( $array as $key => $val ) { ?>
				<?php if ( $val[ 'show' ] ) { ?>
					<section id="content_trackship_<?php esc_html_e( $val[ 'slug' ] ); ?>" class="inner_tab_section">
						<div class="tab_inner_container">
							<?php include __DIR__ . '/' . $val[ 'slug' ] . '.php'; ?>
						</div>
					</section>
				<?php } ?>
			<?php } ?>
		</div>
	</div>
</div>

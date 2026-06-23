<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* Database upgrade notice ------------------------------------------- */
$db_status = trackship_for_woocommerce()->ts_install->check_tsdb_status();
if ( $db_status['missing_tables'] || $db_status['missing_columns'] ) {
	$url = admin_url( 'admin.php?page=trackship-for-woocommerce&tab=tools&verify-db=true' );
	?>
	<style>
		.wp-core-ui .notice.db_upgrade { padding: 20px; text-decoration: none; }
		.db_upgrade h3, .db_upgrade p { margin: 0; padding-bottom: 20px; }
	</style>
	<div class="notice notice-warning db_upgrade">
		<h3>Alert: TrackShip database upgrade required</h3>
		<p>Some database tables or columns are missing:</p>
		<?php echo $db_status['missing_tables'] ? '<p><strong>Missing tables:-</strong> ' . esc_html( implode( ', ', $db_status['missing_tables'] ) ) . '.</p>' : ''; ?>
		<?php echo $db_status['missing_columns'] ? '<p><strong>Missing columns:-</strong> ' . esc_html( implode( ', ', $db_status['missing_columns'] ) ) . '.</p>' : ''; ?>
		<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">Upgrade Database</a>
	</div>
	<?php
}

/* Active page detection --------------------------------------------- */
$page_slug         = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
$is_analytics_page = (
	'wc-admin' === $page_slug &&
	isset( $_GET['path'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	false !== strpos( sanitize_text_field( wp_unslash( $_GET['path'] ) ), 'trackship-analytics' ) // phpcs:ignore WordPress.Security.NonceVerification.Recommended
);

$tsh_nav_items = array(
	array(
		'label'  => __( 'Dashboard', 'trackship-for-woocommerce' ),
		'url'    => admin_url( 'admin.php?page=trackship-dashboard' ),
		'active' => ( 'trackship-dashboard' === $page_slug ),
	),
	array(
		'label'  => __( 'Shipments', 'trackship-for-woocommerce' ),
		'url'    => admin_url( 'admin.php?page=trackship-shipments' ),
		'active' => ( 'trackship-shipments' === $page_slug ),
	),
	array(
		'label'  => __( 'Logs', 'trackship-for-woocommerce' ),
		'url'    => admin_url( 'admin.php?page=trackship-logs' ),
		'active' => ( 'trackship-logs' === $page_slug ),
	),
	array(
		'label'  => __( 'Analytics', 'trackship-for-woocommerce' ),
		'url'    => admin_url( 'admin.php?page=wc-admin&path=/analytics/trackship-analytics' ),
		'active' => $is_analytics_page,
	),
	array(
		'label'  => __( 'Settings', 'trackship-for-woocommerce' ),
		'url'    => admin_url( 'admin.php?page=trackship-for-woocommerce' ),
		'active' => ( 'trackship-for-woocommerce' === $page_slug ),
	),
);
?>
<header class="tsh-header">
	<div class="tsh-header__inner">

		<!-- Brand logo -->
		<a href="<?php echo esc_url( admin_url( 'admin.php?page=trackship-dashboard' ) ); ?>" class="tsh-header__brand">
			<img class="tsh-header__logo" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png" alt="TrackShip">
		</a>

		<!-- Main navigation -->
		<nav class="tsh-header__nav" role="navigation" aria-label="<?php esc_attr_e( 'TrackShip navigation', 'trackship-for-woocommerce' ); ?>">
			<?php foreach ( $tsh_nav_items as $item ) { ?>
				<a href="<?php echo esc_url( $item['url'] ); ?>"
				   class="tsh-header__nav-link<?php echo $item['active'] ? ' tsh-header__nav-link--active' : ''; ?>">
					<?php echo esc_html( $item['label'] ); ?>
				</a>
			<?php } ?>
		</nav>

		<!-- External links -->
		<div class="tsh-header__actions">
			<a href="https://docs.trackship.com/docs/trackship-for-woocommerce/" target="_blank" rel="noopener noreferrer" class="tsh-header__action">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/><path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/></svg>
				<?php esc_html_e( 'Docs', 'trackship-for-woocommerce' ); ?>
			</a>
			<a href="https://my.trackship.com/?support=1" target="_blank" rel="noopener noreferrer" class="tsh-header__action tsh-header__action--support">
				<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
				<?php esc_html_e( 'Support', 'trackship-for-woocommerce' ); ?>
			</a>
		</div>

	</div>
</header>

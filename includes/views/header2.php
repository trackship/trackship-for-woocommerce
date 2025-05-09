<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Database upgrade notice
$db_status = trackship_for_woocommerce()->ts_install->check_tsdb_status();

if ( $db_status['missing_tables'] || $db_status['missing_columns'] ) {
	$url = admin_url( 'admin.php?page=trackship-for-woocommerce&tab=tools&verify-db=true' );
	?>
	<style>
		.wp-core-ui .notice.db_upgrade {
			padding: 20px;
			text-decoration: none;
		}
		.db_upgrade h3, .db_upgrade p {
			margin: 0;
			padding-bottom: 20px;
		}
		</style>
	<div class="notice notice-warning db_upgrade">
		<h3>Alert: TrackShip database upgrade required</h3>
		<p>Some database tables or columns are missing:</p>
		<?php echo $db_status['missing_tables'] ? '<p><strong>Missing tables:-</strong> ' . esc_html(implode(', ', $db_status['missing_tables'] )) . '.</p>' : ''; ?>
		<?php echo $db_status['missing_columns'] ? '<p><strong>Missing columns:-</strong> ' . esc_html(implode(', ', $db_status['missing_columns'] )) . '.</p>' : ''; ?>
		<a class="button button-primary" href="<?php echo esc_url($url); ?>">Upgrade Database</a>
	</div>
	<?php
}

$page_slug = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
$tittle = 'trackship-shipments' == $page_slug ? __( 'Shipments', 'trackship-for-woocommerce' ) : '';
$tittle = 'trackship-dashboard' == $page_slug ? __( 'Dashboard', 'trackship-for-woocommerce' ) : $tittle;
$tittle = 'trackship-for-woocommerce' == $page_slug ? __( 'Settings', 'trackship-for-woocommerce' ) : $tittle;
$tittle = 'trackship-logs' == $page_slug ? __( 'Logs', 'trackship-for-woocommerce' ) : $tittle;
$tittle = ! is_trackship_connected() ? __( 'Connect your store', 'trackship-for-woocommerce' ) : $tittle;

$page_link = 'trackship-dashboard' != $page_slug ? admin_url( 'admin.php?page=trackship-dashboard' ) : '#';

$version = trackship_for_woocommerce()->version;
$menu_items = array(
	array(
		'label' => __( 'Dashboard', 'trackship-for-woocommerce' ),
		'link' => admin_url( 'admin.php?page=trackship-dashboard' ),
		'image' => 'ts-dashboard.png',
	),
	array(
		'label' => __( 'Shipments', 'trackship-for-woocommerce' ),
		'link' => admin_url( 'admin.php?page=trackship-shipments' ),
		'image' => 'ts-shipments.png',
	),
	array(
		'label' => __( 'Logs', 'trackship-for-woocommerce' ),
		'link' => admin_url( 'admin.php?page=trackship-logs' ),
		'image' => 'ts-logs.png',
	),
	array(
		'label' => __( 'Analytics', 'trackship-for-woocommerce' ),
		'link' => admin_url('admin.php?page=wc-admin&path=/analytics/trackship-analytics'),
		'image' => 'ts-analytics.png',
	),
	array(
		'label' => __( 'Settings', 'trackship-for-woocommerce' ),
		'link' => admin_url( 'admin.php?page=trackship-for-woocommerce' ),
		'image' => 'ts-settings.png',
	),
	array(
		'label' =>__( 'Documentation', 'trackship-for-woocommerce' ),
		'link' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/',
		'image' => 'ts-documentation.png',
		'target' => 'target="_blank"'
	),
	array(
		'label' => __( 'Get Support', 'trackship-for-woocommerce' ),
		'link' => 'https://my.trackship.com/?support=1',
		'image' => 'ts-support.png',
		'target' => 'target="_blank"'
	),
);
?>
<div class="zorem-layout__header">
	<div>
		<span style="font-size:14px">
			<a href="<?php echo esc_url( $page_link ); ?>"><?php esc_html_e( 'TrackShip', 'trackship-for-woocommerce' ); ?></a>
			<span class="dashicons dashicons-arrow-right-alt2"></span>
			<span class="header-breadcrumbs-last"><?php echo esc_html($tittle); ?></span>
		</span>
	</div>
	<div style="float:right;">
		<h1 class="zorem-layout__header-breadcrumbs"><img class="ts4wc_logo_header" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png"></h1>
	</div>
</div>
<?php if ( in_array( $page_slug, array( 'trackship-shipments', 'trackship-dashboard', 'trackship-logs' ) ) && is_trackship_connected() ) { ?>
	<div class="fullfillment_header">
		<h2 class="fullfillment_header_h2"><?php echo esc_html($tittle); ?></h2>
		<span class="ts_activity-panel">
			<?php include 'header-sidebar.php'; ?>
		</span>
	</div>
<?php } ?>

<?php 
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';
$tittle = 'trackship-shipments' == $page ? __( 'Shipments', 'trackship-for-woocommerce' ) : '';
$tittle = 'trackship-dashboard' == $page ? __( 'Dashboard', 'trackship-for-woocommerce' ) : $tittle;
$tittle = 'trackship-for-woocommerce' == $page ? __( 'Settings', 'trackship-for-woocommerce' ) : $tittle;
$link = 'trackship-dashboard' != $page ? admin_url( 'admin.php?page=trackship-dashboard' ) : '#';

$version = trackship_for_woocommerce()->version;
$menu_items = array(
	array(
		'label' => __( 'Get Support', 'trackship-for-woocommerce' ),
		'link' => 'https://trackship.info/support/?support=1',
		'image' => 'get-support-icon.svg',
	),
	array(
		'label' =>__( 'Documentation', 'trackship-for-woocommerce' ),
		'link' => 'https://trackship.info/documentation/',
		'image' => 'documentation-icon.svg',
	),
);
?> 
<div class="zorem-layout__header">
	<div>
		<span style="font-size:14px">
			<a href="<?php echo esc_url( $link ); ?>"><?php esc_html_e( 'TrackShip', 'trackship-for-woocommerce' ); ?></a>
			<span class="dashicons dashicons-arrow-right-alt2"></span>
			<span class="header-breadcrumbs-last"><?php echo esc_html($tittle); ?></span>
		</span>
	</div>
	<div style="float:right;">
		<h1 class="zorem-layout__header-breadcrumbs"><img class="ts4wc_logo_header" src="<?php echo esc_url( trackship_for_woocommerce()->plugin_dir_url() ); ?>assets/images/trackship-logo.png"></h1>
	</div>
</div>
<?php if ( in_array( $page, array( 'trackship-shipments', 'trackship-dashboard' ) ) ) { ?>
	<div class="fullfillment_header">
		<h2 class="fullfillment_header_h2"><?php echo esc_html($tittle); ?></h2>
		<span class="woocommerce-layout__activity-panel">
			<?php include 'header-sidebar.php'; ?>
		</span>
	</div>
<?php } ?>

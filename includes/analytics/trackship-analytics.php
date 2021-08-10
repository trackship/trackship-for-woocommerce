<?php
/**
 * Plugin Name: TrackShip Analytics
 *
 * @package WooCommerce\Admin
 */

/**
 * Register the JS.
 */
function add_extension_register_script() {
	if ( ! class_exists( 'Automattic\WooCommerce\Admin\Loader' ) || ! \Automattic\WooCommerce\Admin\Loader::is_admin_or_embed_page() ) {
		return;
	}
	
	$script_path       = '/build/index.js';
	$script_asset_path = dirname( __FILE__ ) . '/build/index.asset.php';
	$script_asset      = file_exists( $script_asset_path )
		? require( $script_asset_path )
		: array( 'dependencies' => array(), 'version' => filemtime( $script_path ) );
	$script_url = plugins_url( $script_path, __FILE__ );

	wp_register_script(
		'trackship-analytics',
		$script_url,
		$script_asset['dependencies'],
		$script_asset['version'],
		true
	);

	wp_register_style(
		'trackship-analytics',
		plugins_url( '/build/index.css', __FILE__ ),
		// Add any dependencies styles may have, such as wp-components.
		array(),
		filemtime( dirname( __FILE__ ) . '/build/index.css' )
	);

	wp_enqueue_script( 'trackship-analytics' );
	wp_enqueue_style( 'trackship-analytics' );
}

add_action( 'admin_enqueue_scripts', 'add_extension_register_script' );

add_filter( 'woocommerce_analytics_report_menu_items', 'add_ts_analytics_menu' );
function add_ts_analytics_menu( $report_pages ) {
    $report_pages[10] = array(
        'id' => 'trackship-analytics',
        'title' => __('TrackShip', 'trackship-for-woocommerce'),
        'parent' => 'woocommerce-analytics',
        'path' => '/analytics/trackship-analytics',
    );
    return $report_pages;
}

add_action( 'rest_api_init', 'ts_analytics_rest_api_register_routes' );

/**
 * Register TrackShip Analytics Routes
 */
function ts_analytics_rest_api_register_routes() {
	
	if ( ! is_a( WC()->api, 'WC_API' ) ) {
		return;
	}
	
	require_once plugin_dir_path( __FILE__ ) . '/includes/api/class-trackship-analytics-rest-api-controller.php';
	
	// Register route with default namespace wc/v3.
	$ts_analytics_api_controller = new WC_Ts_Analytics_REST_API_Controller();
	$ts_analytics_api_controller->register_routes();					
}

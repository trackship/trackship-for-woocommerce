<?php
/**
 * Plugin Name: TrackShip for WooCommerce
 * Description: TrackShip for WooCommerce integrates TrackShip into your WooCommerce Store and auto-tracks your orders, automates your post-shipping workflow and allows you to provide a superior Post-Purchase experience to your customers.
 * Version: 1.4.7
 * Author: TrackShip
 * Author URI: https://trackship.com/
 * License: GPL-2.0+
 * License URI: 
 * Text Domain: trackship-for-woocommerce
 * Domain Path: /language/
 * WC tested up to: 7.1.0
*/

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackship_For_Woocommerce {
	
	/**
	 * Trackship_For_Woocommerce version.
	 *
	 * @var string
	*/
	public $version = '1.4.7';
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		
		if ( ! $this->is_wc_active() ) {
			add_action( 'admin_notices', array( $this, 'notice_activate_wc' ) );
			return;
		}
		
		if ( !$this->is_ast_active() && !$this->is_st_active() /*&& !$this->is_active_woo_order_tracking()*/ ) {
			add_action( 'admin_notices', array( $this, 'notice_activate_ast' ) );
			return;
		}
		
		// WC & AST/ST are active
			
		// Include required files.
		$this->includes();
		
		// Init REST API.
		$this->init_rest_api();
		
		//start adding hooks
		$this->init();

		//admin class init
		$this->ts_actions->init();			
		
		//admin class init
		$this->admin->init();
		
		//plugin install class init
		$this->ts_install->init();

		//plugin shipments class init
		$this->shipments->init();

		//plugin Logs class init
		$this->logs->init();
	}
	
	/**
	 * Check if WooCommerce is active
	 *
	 * @since  1.0.0
	 * @return bool
	*/
	private function is_wc_active() {
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		if ( is_plugin_active( 'woocommerce/woocommerce.php' ) ) {
			$is_active = true;
		} else {
			$is_active = false;
		}		
		return $is_active;
	}
	
	/**
	 * Display WC active notice
	 *
	 * @since  1.0.0
	*/
	public function notice_activate_wc() {
		?>
		<div class="error">
			<?php /* translators: %s: search for a tag */ ?>
			<p><?php printf( esc_html__( 'Please install and activate %1$sWooCommerce%2$s for TrackShip for WooCommerce!', 'trackship-for-woocommerce' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&s=WooCommerce&plugin-search-input=Search+Plugins' ) ) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}
	
	/**
	 * Display AST active notice
	 *
	 * @since  1.0.0
	*/
	public function notice_activate_ast() {
		?>
		<div class="error">
			<?php /* translators: %s: search for a tag */ ?>
			<p><?php printf( esc_html__( 'You must have a %1$sShipment Tracking plugin%2$s installed to use TrackShip for WooCommerce.', 'trackship-for-woocommerce' ), '<a href="' . esc_url( admin_url( 'plugin-install.php?tab=search&s=AST&plugin-search-input=Search+Plugins' ) ) . '">', '</a>' ); ?></p>
		</div>
		<?php
	}
	
	/*
	* init when class loaded
	*/
	public function init() {
		
		add_action( 'plugins_loaded', array( $this, 'on_plugins_loaded' ) );
		
		add_action( 'plugin_action_links_' . plugin_basename( __FILE__ ), array( $this, 'tsw_plugin_action_links' ) );

		add_filter( 'yith_wcbm_add_badge_tags_in_wp_kses_allowed_html', '__return_true' );
		add_filter( 'yith_wcbm_is_allowed_adding_badge_tags_in_wp_kses', '__return_true' );
	}				
	
	/**
	 * Init trackship REST API.
	 *
	*/
	private function init_rest_api() {
		add_action( 'rest_api_init', array( $this, 'rest_api_register_routes' ) );
	}
		
	/**
	 * Gets the absolute plugin path without a trailing slash, e.g.
	 * /path/to/wp-content/plugins/plugin-directory.
	 *
	 * @return string plugin path
	 */
	public function get_plugin_path() {
		if ( isset( $this->plugin_path ) ) {
			return $this->plugin_path;
		}

		$this->plugin_path = untrailingslashit( plugin_dir_path( __FILE__ ) );

		return $this->plugin_path;
	}
	
	/*
	* include files
	*/
	private function includes() {				
	
		require_once $this->get_plugin_path() . '/includes/class-wc-trackship-install.php';
		$this->ts_install = WC_Trackship_Install::get_instance();
	
		$wc_ast_api_key = get_option('wc_ast_api_key');
		if ( $wc_ast_api_key ) {
			require_once $this->get_plugin_path() . '/includes/class-wc-trackship-front.php';
			$this->front = WC_TrackShip_Front::get_instance();
			add_action( 'template_redirect', array( $this->front, 'preview_tracking_page' ) );
		}
		
		require_once $this->get_plugin_path() . '/includes/class-wc-trackship-actions.php';
		$this->ts_actions	= WC_Trackship_Actions::get_instance();
		$this->actions		= WC_Trackship_Actions::get_instance();
		
		require_once $this->get_plugin_path() . '/includes/class-wc-trackship-admin.php';
		$this->admin = WC_Trackship_Admin::get_instance();						
		
		require_once $this->get_plugin_path() . '/includes/class-wc-trackship-late-shipments.php';
		$this->late_shipments = WC_TrackShip_Late_Shipments::get_instance();

		require_once $this->get_plugin_path() . '/includes/class-wc-trackship-api-call.php';
		
		require_once $this->get_plugin_path() . '/includes/shipments/class-wc-trackship-shipments.php';
		$this->shipments = WC_Trackship_Shipments::get_instance();
		
		require_once $this->get_plugin_path() . '/includes/logs/class-wc-trackship-logs.php';
		$this->logs = WC_Trackship_Logs::get_instance();
		
		require_once $this->get_plugin_path() . '/includes/analytics/class-wc-trackship-analytics.php';
		$this->analytics = WC_Trackship_Analytics::get_instance();
		
		require_once $this->get_plugin_path() . '/includes/class-wc-trackship-notice.php';
		$this->trackship_admin_notice = WC_TrackShip_Admin_notice::get_instance();
		
		require_once $this->get_plugin_path() . '/includes/class-wc-admin-notices.php';
		$this->wc_admin_notice = WC_TS4WC_Admin_Notices_Under_WC_Admin::get_instance();

		//SMSWOO
		require_once $this->get_plugin_path() . '/includes/smswoo/class-smswoo-init.php';
		$this->smswoo_init = TSWC_SMSWOO_Init::get_instance();

		if ( is_plugin_active( 'automatewoo/automatewoo.php' ) ) {
			require_once plugin_dir_path( __FILE__ ) . '/includes/class-wc-automatewoo-integration.php';
		}
	}
	
	/**
	 * Register shipment tracking routes.
	 *
	 * @since 1.5.0
	 */
	public function rest_api_register_routes() {		
		if ( ! is_a( WC()->api, 'WC_API' ) ) {
			return;
		}
		require_once $this->get_plugin_path() . '/includes/api/class-trackship-rest-api-controller.php';
		
		$trackship_controller_v1 = new TrackShip_REST_API_Controller();
		$trackship_controller_v1->register_routes();
		
		$trackship_controller_v2 = new TrackShip_REST_API_Controller();
		$trackship_controller_v2->set_namespace( 'wc/v2' );
		$trackship_controller_v2->register_routes();
		
		$trackship_controller_v3 = new TrackShip_REST_API_Controller();
		$trackship_controller_v3->set_namespace( 'wc/v3' );
		$trackship_controller_v3->register_routes();
		
	}
	
	/*
	* include file on plugin load
	*/
	public function on_plugins_loaded() {
		$wc_ast_api_key = get_option('wc_ast_api_key');

		//load customizer
		if ( $wc_ast_api_key ) {
			require_once $this->get_plugin_path() . '/includes/customizer/trackship-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-intransit-email-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-outfordelivery-email-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-availableforpickup-email-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-failure-email-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-onhold-email-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-exception-email-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-returntosender-email-customizer.php';
			require_once $this->get_plugin_path() . '/includes/customizer/class-wc-delivered-email-customizer.php';
		}
		require_once $this->get_plugin_path() . '/includes/trackship-email-manager.php';
		
		//load plugin textdomain
		load_plugin_textdomain( 'trackship-for-woocommerce', false, dirname( plugin_basename(__FILE__) ) . '/language/' );
	}

	/*
	* return plugin directory URL
	*/
	public function plugin_dir_url() {
		return plugin_dir_url( __FILE__ );
	}				
	
	/**
	* Add plugin action links.
	*
	* Add a link to the settings page on the plugins.php page.
	*
	* @since 1.0.0
	*
	* @param  array  $links List of existing plugin action links.
	* @return array         List of modified plugin action links.
	*/
	public function tsw_plugin_action_links( $links ) {
		$admin_url = trackship_for_woocommerce()->is_trackship_connected() ? admin_url( '/admin.php?page=trackship-for-woocommerce' ) : admin_url( '/admin.php?page=trackship-dashboard' );
		$name = trackship_for_woocommerce()->is_trackship_connected() ? __( 'Settings', 'trackship-for-woocommerce' ) : __( 'Connect a Store', 'trackship-for-woocommerce' );
		$links = array_merge( array(
			'<a href="' . esc_url( $admin_url ) . '">' . esc_html__( $name ) . '</a>',
			'<a href="https://docs.trackship.com/docs/trackship-for-woocommerce/">' . __( 'Docs' ) . '</a>',
			'<a href="https://wordpress.org/support/plugin/trackship-for-woocommerce/#new-topic-0">' . __( 'Support' ) . '</a>',
			'<a href="https://wordpress.org/support/plugin/trackship-for-woocommerce/reviews/#new-post">' . __( 'Review' ) . '</a>'
		), $links );
		return $links;
	}
	
	/**
	 * Check if Advanced Shipment Tracking for WooCommerce is active
	 *
	 * @since  1.0.0
	 * @return bool
	*/
	public function is_ast_active() {
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		
		if ( is_plugin_active( 'woo-advanced-shipment-tracking/woocommerce-advanced-shipment-tracking.php' ) || is_plugin_active( 'ast-pro/ast-pro.php' )) {
			$is_active = true;
		} else {
			$is_active = false;
		}		
	
		return $is_active;
	}
	
	/**
	 * Check if Shipment Tracking is active
	 *
	 * @since  1.0.0
	 * @return bool
	*/
	public function is_st_active() {
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		
		if ( is_plugin_active( 'woocommerce-shipment-tracking/woocommerce-shipment-tracking.php' ) ) {
			$is_active = true;
		} else {
			$is_active = false;
		}		
	
		return $is_active;
	}
	
	/**
	 * Check if Woo order Tracking is active
	 *
	 * @since  1.2.2
	 * @return bool
	*/
	public function is_active_woo_order_tracking() {
		
		if ( ! function_exists( 'is_plugin_active' ) ) {
			require_once( ABSPATH . '/wp-admin/includes/plugin.php' );
		}
		
		if ( is_plugin_active( 'woo-orders-tracking/woo-orders-tracking.php' ) ) {
			$is_active = true;
		} else {
			$is_active = false;
		}		
	
		return $is_active;
	}
		
	/*
	 * check trackship is connected
	 *
	 * @since   1.0.0
	 *
	 * Return @void
	 *
	 */
	public function is_trackship_connected() {
		
		$wc_ast_api_key = get_option( 'wc_ast_api_key' );
		
		if ( ! $wc_ast_api_key ) {
			return false;
		}
		
		return true;
	}
	
	public function get_tracking_items( $order_id ) {
		if ( function_exists( 'ast_get_tracking_items' ) ) {
			return ast_get_tracking_items( $order_id );	
		} elseif ( class_exists( 'WC_Shipment_Tracking' ) ) {
			return WC_Shipment_Tracking()->actions->get_tracking_items( $order_id, true );
		} else {
			$order = wc_get_order( $order_id );
			$tracking_items = $order->get_meta( '_wc_shipment_tracking_items', true );
			return $tracking_items ? $tracking_items : array();
		}
	}
	
}

if ( ! function_exists( 'trackship_for_woocommerce' ) ) {

	/**
	 * Returns an instance of Trackship_For_Woocommerce.
	 *
	 * @since 1.0.0
	 * @version 1.0.0
	 *
	 * @return Trackship_For_Woocommerce
	*/	
	function trackship_for_woocommerce() {
		static $instance;
	
		if ( ! isset( $instance ) ) {		
			$instance = new Trackship_For_Woocommerce();
		}
	
		return $instance;
	}


	/**
	 * Register this class globally.
	 *
	 * Backward compatibility.
	*/
	trackship_for_woocommerce();
}

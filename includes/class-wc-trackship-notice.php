<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_TrackShip_Admin_notice {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Initialize the main plugin function
	*/
	public function __construct() {
		$this->init();	
	}
	
	/**
	 * Get the class instance
	 *
	 * @return WC_TrackShip_Admin_notice
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/*
	* init from parent mail class
	*/
	public function init() {
		// Ignore notice
		add_action( 'admin_init', array( $this, 'trackship_admin_notice_ignore' ) );

		// review notice
		add_action( 'admin_notices', array( $this, 'trackship_review_notice' ) );

		// review notice
		add_action( 'admin_notices', array( $this, 'trackship_upgrade_notice' ) );

		// Database update notice
		if ( version_compare( get_option( 'trackship_db' ), '1.19', '<' ) ) {
			add_action( 'admin_notices', array( $this, 'trackship_database_notice' ) );
		}
	}
	
	/*
	* Dismiss admin notice for trackship
	*/
	public function trackship_admin_notice_ignore() {
		if ( isset( $_GET['trackship-review-ignore'] ) ) {
			update_trackship_settings( 'review_notice_ignore', 'true' );
		}
		if ( isset( $_GET['trackship-upgrade-ignore'] ) ) {
			update_trackship_settings( 'trackship_upgrade_ignore', 'true');
		}
	}

	/*
	* Display TrackShip for WooCommerce review notice on plugin install or update
	*/
	public function trackship_review_notice() {
		
		if ( get_trackship_settings( 'review_notice_ignore', '') ) {
			return;
		}
		
		$dismissable_url = esc_url( add_query_arg( 'trackship-review-ignore', 'true' ) );
		$url = 'https://wordpress.org/support/plugin/trackship-for-woocommerce/reviews/#new-post';
		?>		
		<style>		
		.wp-core-ui .notice.trackship-dismissable-notice {
			padding: 12px;
			text-decoration: none;
		}
		.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		}
		</style>	
		<div class="notice notice-success is-dismissible trackship-dismissable-notice">
			<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
			<p><strong>Enjoying TrackShip for WooCommerce?</strong></p>
			<p> We'd love to hear your thoughts! Please take a moment to leave a review on <a href="<?php echo esc_url($url); ?>" target="_blank">WordPress.org</a>. Your feedback helps us improve and grow. Thank you for your support!</p>

			<a class="button button-primary" href="<?php echo esc_url($url); ?>" target="_blank">Review Now</a>
			<a class="button" style="margin: 0 10px;" href="<?php echo esc_url($dismissable_url); ?>" >No thanks</a>
		</div>
		<?php
	}

	/*
	* Display admin notice on Upgrade TrackShip plan
	*/
	public function trackship_upgrade_notice () {
		
		if ( get_trackship_settings( 'trackship_upgrade_ignore', '') || !in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			return;
		}

		$dismissable_url = esc_url( add_query_arg( 'trackship-upgrade-ignore', 'true' ) );
		$url = 'https://my.trackship.com/settings/#billing';
		?>		
		<style>		
		.wp-core-ui .notice.trackship-dismissable-notice {
			padding: 12px;
			text-decoration: none;
		}
		.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		}
		</style>
		<div class="notice notice-success is-dismissible trackship-dismissable-notice">
			<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
			<p><strong>Supercharge Customer Experience with TrackShip for WooCommerce</strong></p>
			<p>Upgrade your plan today to unlock premium features and maximize your tracking capabilities. Whether you choose a monthly or yearly subscription, you'll enjoy enhanced tracking benefits. Plus, get up to 2 months FREE with an annual plan! Don't miss out on this opportunity to boost your post-shipping workflow.</p>
			<a class="button button-primary" target="_blank" href="<?php echo esc_url($url); ?>" >UPGRADE NOW</a>
			<a class="button" style="margin: 0 10px;" href="<?php echo esc_url($dismissable_url); ?>" >No thanks</a>
		</div>
		<?php
	}

	public function trackship_database_notice() {
		$url = admin_url( '/admin.php?page=trackship-dashboard&trackship-database-upgrade=true' );
		?>		
		<style>		
		.wp-core-ui .notice.trackship-dismissable-notice {
			padding: 12px;
			text-decoration: none;
		}
		.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		}
		</style>
		<div class="notice notice-success trackship-dismissable-notice">
			<p><strong>TrackShip's database update required</strong></p>
			<p>TrackShip has been updated! To keep things running smoothly, we have to update your database to the newest version. The database update process runs in the background and may take a little while, so please be patient.</p>
			<a class="button button-primary" href="<?php echo esc_url($url); ?>" >Update TrackShip's database</a>
		</div>
		<?php
	}
}

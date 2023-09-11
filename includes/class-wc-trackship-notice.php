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

		// SMS notifications notice/ Yearly plan
		add_action( 'admin_notices', array( $this, 'trackship_admin_notice' ) );

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
		if ( isset( $_GET['trackship-ignore-notice'] ) ) {
			update_option( 'trackship_admin_notice_ignore', 'true' );
		}
		if ( isset( $_GET['trackship-review-ignore'] ) ) {
			update_option( 'trackship_review_notice_ignore', 'true' );
		}
		if ( isset( $_GET['trackship-upgrade-ignore'] ) ) {
			update_option( 'trackship_upgrade_notice_ignore', 'true' );
		}
	}

	/*
	* Display admin notice on plugin install or update
	*/
	public function trackship_admin_notice() { 		
		if ( get_option('trackship_admin_notice_ignore') ) {
			return;
		}
		
		$dismissable_url = esc_url(  add_query_arg( 'trackship-ignore-notice', 'true' ) );
		$sms_tab_url = admin_url( '/admin.php?page=trackship-for-woocommerce&tab=notifications' );
		?>		
		<style>		
		.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		}
		</style>	
		<div class="notice notice-success is-dismissible trackship-dismissable-notice">	
			<?php if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) { ?>
				<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
				<p>Great news! We changed TrackShip’s <a href="https://trackship.com/pricing/?utm_source=wpadmin&utm_medium=TS4WC&utm_campaign=trackship" target="_blank">pricing plans</a>, we added a discounted yearly plans and we added a lifetime free plan with 50 Trackers a month. To activate your free 50 plan, please login to your TrackShip account.</p>
			<?php } else { ?>
				<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
				<p>Great news! We added <a href="<?php echo esc_url( $sms_tab_url ); ?>">SMS notifications</a> option which is available to all TrackShip’s paid plans, now you can send shipping & delivery updates to your customers via SMS Notifications.</p>
			<?php } ?>
		</div>
		<?php
	}	
	
	/*
	* Display admin notice on plugin install or update
	*/
	public function trackship_review_notice() { 		
		if ( get_option('trackship_review_notice_ignore') ) {
			return;
		}
		
		$dismissable_url = esc_url(  add_query_arg( 'trackship-review-ignore', 'true' ) );
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
			<p>Hi, we're thrilled that you're using TrackShip for WooCommerce! We're constantly working to improve our plugin and the platform and to make it even more valuable to you. We would greatly appreciate it if you could take a moment to leave us a review on the plugin repository. Your feedback and suggestions will help us make TrackShip even better and more useful for you and other e-commerce merchants. Thank you for your support!</p>
			<p>	Eran Shor, founder & CEO</p>

			<a class="button button-primary" href="<?php echo esc_url($url); ?>" >Yes, let's add a review</a>
			<a class="button" style="margin: 0 10px;" href="<?php echo esc_url($dismissable_url); ?>" >No thanks</a>
		</div>
		<?php
	}

	/*
	* Display admin notice on Upgrade TrackShip plan
	*/
	public function trackship_upgrade_notice () {
		
		if ( get_option('trackship_upgrade_notice_ignore') || !in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			return;
		}

		$currentDate = date('Y-m-d');  // Get the current date in the format 'YYYY-MM-DD'
		$targetDate = '2023-06-06';

		if ( $currentDate > $targetDate ) {
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
			<p>Unlock TrackShip's PRO advantages: track more shipments, SMS Notifications, Priority Support, Shipments Dashboard, and more... Upgrade now and save with our special extra 10% off yearly plans! (Valid by June 5th)</p>
			<a class="button button-primary" target="_blank" href="<?php echo esc_url($url); ?>" >Upgrade to PRO</a>
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
			<p><strong>TrackShip database update required</strong></p>
			<p>TrackShip has been updated! To keep things running smoothly, we have to update your database to the newest version.</p>
			<p>The database update process runs in the background and may take a little while, so please be patient.</p>
			<a class="button button-primary" href="<?php echo esc_url($url); ?>" >Update TrackShip database</a>
		</div>
		<?php
	}
}

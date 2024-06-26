<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_TrackShip_Admin_Notice {

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
	 * @return WC_TrackShip_Admin_Notice
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

	}

	/*
	* Dismiss admin notice for trackship
	*/
	public function trackship_admin_notice_ignore() {
		if (isset($_GET['ts-review-ignore']) && $_GET['ts-review-ignore'] == 'true') {
			// Verify the nonce
			if (isset($_GET['nonce']) && wp_verify_nonce( $_GET['nonce'], 'ts_dismiss_notice' )) {
				update_trackship_settings( 'ts_review_ignore_132', 'true' );
			}
		}
		if (isset($_GET['ts-upgrade-ignore']) && $_GET['ts-upgrade-ignore'] == 'true') {
			// Verify the nonce
			if (isset($_GET['nonce']) && wp_verify_nonce( $_GET['nonce'], 'ts_dismiss_notice' )) {
				update_trackship_settings( 'ts_popup_ignore', 'true');
			}
		}
	}
	
	/*
	* Display TrackShip for WooCommerce review notice on plugin install or update
	*/
	public function trackship_review_notice() {
		
		if ( get_trackship_settings( 'ts_review_ignore_132', '') ) {
			return;
		}

		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan' ) ) && !get_trackship_settings( 'ts_popup_ignore', '') ) {
			return;
		}

		$nonce = wp_create_nonce('ts_dismiss_notice');
		$dismissable_url = esc_url( add_query_arg( [ 'ts-review-ignore' => 'true',  'nonce' => $nonce ] ) );
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
			<p>Hello there, I hope you're finding great value in using TrackShip for WooCommerce plugin. We strive to provide the best experience possible and your feedback is crucial in helping us improve. If you have a moment, I would really appreciate it if you could leave us a review on <a href="<?php echo esc_url($url); ?>" target="_blank">WordPress.org</a>. Sharing your experiences helps us grow and serve you better.</p>
			<p>
				Thank you for your support!<br>
				Best regards,<br>
				Eran Shor<br>
				Founder & CEO
			</p>
			<!-- <p> We'd love to hear your thoughts! Please take a moment to leave a review on <a href="<?php echo esc_url($url); ?>" target="_blank">WordPress.org</a>. Your feedback helps us improve and grow. Thank you for your support!</p> -->

			<a class="button button-primary" href="<?php echo esc_url($url); ?>" target="_blank">Review Now</a>
			<a class="button" style="margin: 0 10px;" href="<?php echo esc_url($dismissable_url); ?>" >No thanks</a>
		</div>
		<?php
	}

	/*
	* Display admin notice on Upgrade TrackShip plan
	*/
	public function trackship_upgrade_notice () {
		if ( get_trackship_settings( 'ts_popup_ignore', '') || !in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan' ) ) ) {
			return;
		}
		$target_date = strtotime('2024-05-31');
		$current_date = current_time('timestamp');
	
		// If the current date is after May 31, 2024, return early
		if ( $current_date > $target_date ) {
			return;
		}
		$nonce = wp_create_nonce('ts_dismiss_notice');
		$dismissable_url = esc_url( add_query_arg( [ 'ts-upgrade-ignore' => 'true',  'nonce' => $nonce ] ) );
		$url = 'https://my.trackship.com/settings/#billing';
		?>
		<style>
		.wp-core-ui .notice.trackship-dismissable-notice {
			padding: 20px;
			text-decoration: none;
		}
		.trackship-dismissable-notice h3, .trackship-dismissable-notice p {
			margin: 0;
			padding-bottom: 20px;
		}
		.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		}
		</style>
		<div class="notice notice-success is-dismissible trackship-dismissable-notice">
			<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
			<h3>Upgrade to TrackShip Pro!</h3>
			<p>Upgrade your plan today to unlock premium features and maximize your tracking capabilities. Whether you choose a monthly or yearly subscription, you'll enjoy enhanced tracking benefits. Plus, get up to 2 months FREE with an annual plan! Don't miss out on this opportunity to boost your post-shipping workflow.</p>
			<p>Plus, as a special limited-time offer, use coupon code <b>TRACKSHIP10</b> at checkout to receive a 10% discount on your subscription. Hurry, this offer is valid until May 31st!</p>
			<a class="button button-primary" target="_blank" href="<?php echo esc_url($url); ?>" >UPGRADE NOW</a>
			<a class="button" style="margin: 0 10px;" href="<?php echo esc_url($dismissable_url); ?>" >No thanks</a>
		</div>
		<?php
	}

}

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
		$nonce = sanitize_text_field($_GET['nonce'] ?? '');
		// Verify the nonce
		if (!$nonce || !wp_verify_nonce(sanitize_text_field($nonce), 'ts_dismiss_notice')) {
			return;
		}

		$notice_types = [
			'ts-review-ignore'  => 'ts_review_ignore_139',
			'ts-upgrade-ignore' => 'ts_popup_ignore139',
		];

		foreach ($notice_types as $param => $setting_key) {
			$value = sanitize_text_field($_GET[$param] ?? '');
			if ($value === 'true') {
				update_trackship_settings($setting_key, 'true');
			}
		}
	}
	
	/*
	* Display TrackShip for WooCommerce review notice on plugin install or update
	*/
	public function trackship_review_notice() {
		
		if ( get_trackship_settings( 'ts_review_ignore_139', '') ) {
			return;
		}

		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) && !get_trackship_settings( 'ts_popup_ignore139', '') ) {
			return;
		}

		$nonce = wp_create_nonce('ts_dismiss_notice');
		$dismissable_url = esc_url( add_query_arg( [ 'ts-review-ignore' => 'true', 'nonce' => $nonce ] ) );
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
			<p>Hi there! I hope you're enjoying the TrackShip for WooCommerce plugin and finding it valuable for your business. Your feedback is incredibly important to us, and it helps us continue to enhance and refine the plugin. If you could spare a moment, I'd be grateful if you could share your experience by leaving a review on <a href="<?php echo esc_url($url); ?>" target="_blank">WordPress.org</a>. Your insights help us grow and improve, making TrackShip even better for you and others.</p>
			<p>
				Thank you for your continued support!<br>
				Best regards,<br>
				Eran Shor<br>
				Founder & CEO
			</p>
			<a class="button button-primary" href="<?php echo esc_url($url); ?>" target="_blank">Review Now</a>
			<a class="button" style="margin: 0 10px;" href="<?php echo esc_url($dismissable_url); ?>" >No thanks</a>
		</div>
		<?php
	}

	/*
	* Display admin notice on Upgrade TrackShip plan
	*/
	public function trackship_upgrade_notice () {
		if ( get_trackship_settings( 'ts_popup_ignore139', '') || !in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended', 'Free Trial' ) ) ) {
			return;
		}
		$target_date = strtotime('2025-05-10');
		$current_date = current_time('timestamp');
	
		// If the current date is after May 10, 2025, return early
		if ( $current_date > $target_date ) {
			return;
		}
		$nonce = wp_create_nonce('ts_dismiss_notice');
		$dismissable_url = esc_url( add_query_arg( [ 'ts-upgrade-ignore' => 'true', 'nonce' => $nonce ] ) );
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
			<p>Upgrade to the Pro Plan today and unlock a suite of premium features that will take your tracking capabilities to the next level. Choose between a monthly or yearly subscription and enjoy advanced tracking benefits. With an annual plan, you can also get up to 2 months FREE!</p>
			<p>As a special limited-time offer, use coupon code <b>TRACKSHIP10</b> at checkout to receive a 10% discount on your subscription. Don't wait—this offer is valid until May 10th!</p>
			<a class="button button-primary" target="_blank" href="<?php echo esc_url($url); ?>" >UPGRADE NOW</a>
			<a class="button" style="margin: 0 10px;" href="<?php echo esc_url($dismissable_url); ?>" >No thanks</a>
		</div>
		<?php
	}

}

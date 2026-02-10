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

		// review notice
		add_action( 'admin_notices', array( $this, 'trackship_fulfillments_notice' ) );

		// review notice
		add_action( 'admin_notices', array( $this, 'trackship_store_connect_notice' ) );
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
			'ts-review-ignore'  => 'ts_review_ignore_141',
			'ts-upgrade-ignore' => 'ts_popup_ignore141',
			'ts-fulfillments-ignore' => 'ts_fulfillments_ignore',
		];

		foreach ($notice_types as $param => $setting_key) {
			$value = sanitize_text_field($_GET[$param] ?? '');
			if ( 'true' ===  $value ) {
				update_trackship_settings($setting_key, 'true');
			}
		}
	}
	
	/*
	* Display TrackShip for WooCommerce review notice on plugin install or update
	*/
	public function trackship_review_notice() {
		
		if ( get_trackship_settings( 'ts_review_ignore_141', '') ) {
			return;
		}

		if ( in_array( get_option( 'user_plan' ), array( 'Free 50', 'No active plan', 'Trial Ended' ) ) && !get_trackship_settings( 'ts_popup_ignore141', '') ) {
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
			<p>Hi there!<br> I hope you're enjoying the TrackShip for WooCommerce plugin and finding it valuable for your business. Your feedback is incredibly important to us, and it helps us continue to enhance and refine the plugin. If you could spare a moment, I'd be grateful if you could share your experience by leaving a review on <a href="<?php echo esc_url($url); ?>" target="_blank">WordPress.org</a>. Your insights help us grow and improve, making TrackShip even better for you and others.</p>
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
		if ( get_trackship_settings( 'ts_popup_ignore141', '') ) {
			return;
		}
		$target_date = strtotime('2025-11-30');
		$current_date = current_time('timestamp');
		// If the current date is after Nov 30, 2025, return early
		if ( $current_date > $target_date ) {
			return;
		}
		
		$user_plan = get_option( 'user_plan' );
		
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
			padding-bottom: 10px;
		}
		.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss{
			padding: 9px;
			text-decoration: none;
		}
		</style>

		<?php // Upgrade to Pro Notice for Free Plan ?>
		<?php if ( in_array( $user_plan, array( 'Free 50' ) ) ) { ?>
			<?php
			$args = array(
				'return' => 'ids',
				'date_created' => '>' . ( time() - 2592000 ),
				'type' => 'shop_order',
				'limit' => '-1',
			);
			$orders = wc_get_orders( $args );
			$order_count = count( $orders );
			?>

			<?php if ( $order_count <= 50 ) { ?>
				<?php // Upgrade to Pro Notice for Free Plan (Under 50 shipments) ?>
				<div class="notice notice-success is-dismissible trackship-dismissable-notice" role="region">
					<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
					<h3>Turn every order into a repeat customer.</h3>
					<p>Upgrade to TrackShip Pro and give buyers the branded, worry-free tracking they love - before the holiday rush!</p>
					<p>🎁 50% OFF for 3 months with <strong>TRACKSHIP50F3M</strong>.</p>
					<p style="padding:0;">
						<a class="button button-primary" target="_blank" href="<?php echo esc_url( $url ); ?>">Upgrade to Pro</a>
						<a class="button" href="<?php echo esc_url( $dismissable_url ); ?>">Dismiss</a>
					</p>
				</div>
			<?php } else { ?>
				<?php // Upgrade to Pro Notice for Free Plan (Over 50 shipments) ?>
				<div class="notice notice-success is-dismissible trackship-dismissable-notice" role="region">
					<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
					<h3>Your customers are waiting for updates - are you ready?</h3>
					<p>Give them a premium tracking experience that builds trust, loyalty, and repeat sales.</p>
					<p>🎁 50% OFF for 3 months with <strong>TRACKSHIP50F3M</strong>.</p>
					<p style="padding:0;">
						<a class="button button-primary" target="_blank" href="<?php echo esc_url( $url ); ?>">Upgrade Now</a>
						<a class="button" href="<?php echo esc_url( $dismissable_url ); ?>">Dismiss</a>
					</p>
				</div>
			<?php } ?>

		<?php } ?>

		<?php // Upgrade to Pro Notice for Trial ended ?>
		<?php if ( in_array( $user_plan, array( 'No active plan', 'Trial Ended' ) ) ) { ?>
			<div class="notice notice-success is-dismissible trackship-dismissable-notice" role="region">
				<a href="<?php esc_html_e( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
				<h3>Don't lose momentum after your trial!</h3>
				<p>Keep customers engaged post-purchase with branded tracking that builds trust and loyalty - and cuts down on support tickets.</p>
				<p>🎁 50% OFF for 3 months with <strong>TRACKSHIP50F3M</strong>.</p>
				<p style="padding:0;">
					<a class="button button-primary" target="_blank" href="<?php echo esc_url( $url ); ?>">Reactivate Now</a>
					<a class="button" href="<?php echo esc_url( $dismissable_url ); ?>">Dismiss</a>
				</p>
			</div>
			<?php
		}
	}

	/*
	* Display admin notice to promote WC Fulfillments
	*/
	public function trackship_fulfillments_notice () {
		if ( get_trackship_settings( 'ts_fulfillments_ignore', '') || WC_VERSION < '10.2' ) {
			return;
		}
		$nonce = wp_create_nonce('ts_dismiss_notice');
		$dismissable_url = esc_url( add_query_arg( [ 'ts-fulfillments-ignore' => 'true', 'nonce' => $nonce ] ) );
		$url = add_query_arg( array( 'page' => 'trackship-for-woocommerce', 'tab' => 'setup' ), admin_url( 'admin.php' ) );
		$is_fulfillments = trackship_for_woocommerce()->is_active_fulfillments();
		?>
		<style>
			.wp-core-ui .notice.trackship-dismissable-notice {
				padding: 15px;
				text-decoration: none;
			}
			.trackship-dismissable-notice h3, .trackship-dismissable-notice p {
				margin: 0;
				padding-bottom: 10px;
			}
			.wp-core-ui .notice.trackship-dismissable-notice a.notice-dismiss {
				padding: 9px;
				text-decoration: none;
			}
		</style>
		<?php if ( !$is_fulfillments ) { ?>
			<div class="notice notice-info is-dismissible trackship-dismissable-notice" role="region">
				<a href="<?php echo esc_url( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
				<h3>🆕 WooCommerce Fulfillments is now supported</h3>
				<p>TrackShip can sync shipments created via WooCommerce Fulfillments. Enable it once to start tracking fulfilment-based shipments automatically.</p>
				<p style="padding:0;">
					<a class="button button-primary" href="<?php echo esc_url( $url ); ?>">Start setup</a>
					<a class="button" href="<?php echo esc_url( $dismissable_url ); ?>">Skip for now</a>
				</p>
			</div>
		<?php } else { ?>
			<div class="notice notice-success is-dismissible trackship-dismissable-notice" role="region">
				<a href="<?php echo esc_url( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text">Dismiss this notice.</span></a>
				<h3>✅ WooCommerce Fulfillments is enabled</h3>
				<p>TrackShip is now syncing shipments created via WooCommerce Fulfillments. Your fulfilment-based shipments are being tracked automatically.</p>
			</div>
		<?php }
	}

	public function trackship_store_connect_notice () {
		if ( is_trackship_connected() ) {
			return;
		}
		$store_url = get_site_url();
		$url = add_query_arg( array(
			'utm_source'	=> 'wpadmin',
			'utm_campaign'	=> 'tspage',
			'store_url'		=> $store_url,
			'type'			=> 'wc',
			'token'			=> md5( $store_url ),
		), 'https://my.trackship.com' );
		?>
		<style>
		.wp-core-ui .notice.notice-trackship {
			padding: 12px;
			text-decoration: none;
		}
		.notice-trackship h3, .notice-trackship p {
			margin: 0;
			padding-bottom: 10px;
		}
		</style>	
		<div class="notice notice-success notice-trackship">
			<h3>Turn shipping into a loyalty booster.</h3>
			<p>Connect your store to TrackShip and give customers real-time updates, fewer “Where is my order?” messages, and more repeat sales this shopping season.</p>
			<p>🚀 Setup takes 2 minutes.</p>
			<p style="padding:0;">
				<a class="button button-primary" target="_blank" href="<?php echo esc_url( $url ); ?>">Connect Store</a>
				<a class="button button-primary" target="_blank" href="https://trackship.com/woocommerce-integration/">Learn More</a>
			</p>
		</div>
		<?php
	}
}

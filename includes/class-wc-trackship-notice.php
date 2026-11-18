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
			'ts-review-ignore' => 'ts_review_ignore_141',
			'ts-upgrade-ignore' => 'ts_bfcm_ignore_2026',
			'ts-fulfillments-ignore' => 'ts_fulfillments_ignore',
		];

		foreach ($notice_types as $param => $setting_key) {
			$value = sanitize_text_field($_GET[$param] ?? '');
			if ( 'true' === $value ) {
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

		if ( in_array( get_option( 'user_plan' ), array( 'Complimentary 100', 'Complimentary 150', 'Free 20', 'No active plan', 'Trial Ended' ) ) && !get_trackship_settings( 'ts_popup_ignore141', '') ) {
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
		if ( get_trackship_settings( 'ts_bfcm_ignore_2026', '' ) ) {
			return;
		}

		// the sale ends on December 1, and so does the notice
		if ( current_time( 'timestamp' ) > strtotime( '2026-12-01 23:59:59' ) ) {
			return;
		}

		// the offer is for stores that are not on a paid yearly plan yet
		$user_plan = get_option( 'user_plan' );
		if ( ! in_array( $user_plan, array( 'Complimentary 100', 'Complimentary 150', 'Free 20', 'No active plan', 'Trial Ended', 'Free Trial' ) ) ) {
			return;
		}

		$nonce = wp_create_nonce( 'ts_dismiss_notice' );
		$dismissable_url = esc_url( add_query_arg( [ 'ts-upgrade-ignore' => 'true', 'nonce' => $nonce ] ) );
		$url = 'https://my.trackship.com/settings/#billing';
		$coupon = 'BFCM30';
		?>
		<style>
		.wp-core-ui .notice.ts_bfcm_notice {
			display: flex;
			align-items: center;
			gap: 20px;
			flex-wrap: wrap;
			padding: 16px 20px;
			border: 1px solid #cfe7da;
			border-left: 4px solid #22c55e;
			border-radius: 10px;
			background: linear-gradient( 90deg, #eefaf1 0%, #eef5fd 100% );
		}
		.wp-core-ui .notice.ts_bfcm_notice a.notice-dismiss {
			padding: 9px;
			text-decoration: none;
		}
		.ts_bfcm_notice .ts_bfcm_body {
			flex: 1 1 420px;
			min-width: 0;
		}
		.ts_bfcm_notice .ts_bfcm_badge {
			display: inline-block;
			margin-bottom: 10px;
			padding: 4px 12px;
			border-radius: 999px;
			background: #f97316;
			color: #fff;
			font-size: 12px;
			font-weight: 600;
		}
		.ts_bfcm_notice p {
			margin: 0 0 8px;
			padding: 0;
			font-size: 14px;
			line-height: 1.5;
		}
		.ts_bfcm_notice p.ts_bfcm_terms {
			color: #5b6472;
			font-size: 13px;
		}
		.ts_bfcm_notice .ts_bfcm_coupon_row {
			display: flex;
			align-items: center;
			gap: 8px;
			flex-wrap: wrap;
			margin: 0;
		}
		.ts_bfcm_notice .ts_bfcm_code {
			padding: 4px 12px;
			border: 1px dashed #3858e9;
			border-radius: 6px;
			background: #fff;
			color: #1f2540;
			font-size: 13px;
			font-weight: 600;
			letter-spacing: .5px;
		}
		.wp-core-ui .ts_bfcm_notice .ts_bfcm_copy {
			height: auto;
			min-height: 0;
			padding: 2px 10px;
			border-color: #3858e9;
			background: #3858e9;
			color: #fff;
			font-size: 11px;
			font-weight: 600;
			line-height: 1.7;
			text-transform: uppercase;
			letter-spacing: .3px;
		}
		.ts_bfcm_notice .ts_bfcm_valid {
			padding: 3px 12px;
			border: 1px solid #fdba74;
			border-radius: 999px;
			background: #fff7ed;
			color: #c2410c;
			font-size: 12px;
			font-weight: 600;
		}
		.wp-core-ui .ts_bfcm_notice .ts_bfcm_cta {
			flex: 0 0 auto;
			margin-right: 30px;
			padding: 4px 16px;
			height: auto;
			min-height: 0;
			font-size: 12px;
			font-weight: 600;
			line-height: 1.9;
			text-transform: uppercase;
			letter-spacing: .3px;
		}
		</style>
		<div class="notice is-dismissible ts_bfcm_notice" role="region">
			<a href="<?php echo esc_url( $dismissable_url ); ?>" class="notice-dismiss"><span class="screen-reader-text"><?php esc_html_e( 'Dismiss this notice.', 'trackship-for-woocommerce' ); ?></span></a>
			<div class="ts_bfcm_body">
				<span class="ts_bfcm_badge">&#9889; <?php esc_html_e( 'Black Friday Offer', 'trackship-for-woocommerce' ); ?></span>
				<p>
					&#128640;
					<?php
					printf(
						/* translators: 1: Black Friday Sale label, 2: the discount */
						esc_html__( '%1$s %2$s on any yearly plan!', 'trackship-for-woocommerce' ),
						'<strong>' . esc_html__( 'Black Friday Sale', 'trackship-for-woocommerce' ) . '</strong>',
						'<strong>' . esc_html__( '30% off your first year', 'trackship-for-woocommerce' ) . '</strong>'
					);
					?>
				</p>
				<p class="ts_bfcm_terms"><?php esc_html_e( 'The discount applies to your first yearly subscription. After the first year, the plan renews at the regular price.', 'trackship-for-woocommerce' ); ?></p>
				<p class="ts_bfcm_coupon_row">
					<span>&#127991; <?php esc_html_e( 'Use coupon', 'trackship-for-woocommerce' ); ?></span>
					<span class="ts_bfcm_code"><?php echo esc_html( $coupon ); ?></span>
					<button type="button" class="button ts_bfcm_copy" data-coupon="<?php echo esc_attr( $coupon ); ?>" data-copied="<?php esc_attr_e( 'Copied!', 'trackship-for-woocommerce' ); ?>"><?php esc_html_e( 'Copy', 'trackship-for-woocommerce' ); ?></button>
					<span><?php esc_html_e( 'at checkout and save 30%', 'trackship-for-woocommerce' ); ?></span>
					<span class="ts_bfcm_valid">&#9200; <?php esc_html_e( 'Valid until December 1, 2026', 'trackship-for-woocommerce' ); ?></span>
				</p>
			</div>
			<a class="button button-primary ts_bfcm_cta" target="_blank" href="<?php echo esc_url( $url ); ?>"><?php esc_html_e( 'Subscribe Now', 'trackship-for-woocommerce' ); ?></a>
		</div>
		<script>
		jQuery( document ).on( 'click', '.ts_bfcm_copy', function () {
			var button = jQuery( this );
			var coupon = button.data( 'coupon' );
			var label = button.text();

			var done = function () {
				button.text( button.data( 'copied' ) );
				setTimeout( function () {
					button.text( label );
				}, 2000 );
			};

			// clipboard access is refused on http and in some browsers, so fall back to a
			// hidden field the old execCommand can copy from
			var fallback = function () {
				var field = jQuery( '<textarea>' ).val( coupon ).css( { position: 'fixed', opacity: 0 } ).appendTo( 'body' );
				field.trigger( 'select' );
				try {
					document.execCommand( 'copy' );
					done();
				} catch ( e ) {
					window.prompt( 'Copy the coupon code:', coupon );
				}
				field.remove();
			};

			if ( navigator.clipboard && navigator.clipboard.writeText ) {
				navigator.clipboard.writeText( coupon ).then( done ).catch( fallback );
			} else {
				fallback();
			}
		} );
		</script>
		<?php
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
		$page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( 'trackship-dashboard' === $page ) {
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

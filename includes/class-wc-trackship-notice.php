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
		add_action( 'admin_notices', array( $this, 'trackship_admin_notice' ) );	
		add_action( 'admin_init', array( $this, 'trackship_admin_notice_ignore' ) );	
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
	* Dismiss admin notice for trackship
	*/
	public function trackship_admin_notice_ignore() {
		if ( isset( $_GET['trackship-ignore-notice'] ) ) {
			update_option( 'trackship_admin_notice_ignore', 'true' );
		}
	}	
}

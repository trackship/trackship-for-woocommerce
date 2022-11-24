<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WC_TS4WC_Admin_Notices_Under_WC_Admin {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;
	
	/**
	 * Get the class instance
	 *
	 * @return WC_Advanced_Shipment_Tracking_Admin_notice
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
	
	/*
	* Admin notice in WC admin
	*/
	public function admin_notices_for_TrackShip_pro() {
		
		if ( ! class_exists( 'Automattic\WooCommerce\Admin\Notes\WC_Admin_Notes' ) ) {
			return;
		}			
		
		$note_name = 'trackship_wc_admin_notice';
		
		// Otherwise, add the note
		$activated_time = current_time( 'timestamp', 0 );
		$activated_time_formatted = gmdate( 'F jS', $activated_time );
		$note = new Automattic\WooCommerce\Admin\Notes\WC_Admin_Note();
		$note->set_title( 'TrackShip' );
		if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			$note->set_content( 'Great news! We changed TrackShip’s pricing plans, we added a discounted yearly plans and we added a lifetime free plan with 50 Trackers a month. To activate your free 50 plan, please login to your TrackShip account.' );
		} else {
			$note->set_content( 'Great news! We added SMS notifications option which is available to all TrackShip’s paid plans, now you can send shipping & delivery updates to your customers via SMS Notifications.' );
		}
		$note->set_content_data( (object) array(
			'getting_started'     => true,
			'activated'           => $activated_time,
			'activated_formatted' => $activated_time_formatted,
		) );
		$note->set_type( 'info' );		
		$note->set_image('');
		$note->set_name( $note_name );
		$note->set_source( 'TrackShip Pro' );		
		$note->set_image('');
		// This example has two actions. A note can have 0 or 1 as well.
		if ( in_array( get_option( 'user_plan' ), array( 'Free Trial', 'Free 50', 'No active plan' ) ) ) {
			$note->add_action( 
				'settings', 'TrackShip Pricing', 'https://trackship.com/pricing/?utm_source=wpadmin&utm_medium=TS4WC&utm_campaign=wcadmin'
			);
		} else {
			$note->add_action(
				'settings', 'SMS notifications', admin_url( '/admin.php?page=trackship-for-woocommerce&tab=notifications' )
			);
		}		
		$note->save();
	}				
}

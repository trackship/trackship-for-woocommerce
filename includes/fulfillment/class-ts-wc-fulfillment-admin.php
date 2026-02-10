<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TrackShip WooCommerce Fulfillment Admin
 */
class TrackShip_Fulfillment_Admin {

	/**
	 * Constructor.
	 */
	public function __construct() {
		$this->init();
	}

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	 */
	private static $instance;

	public $ast_directory;
	
	/**
	 * Get the class instance
	 *
	 * @return WC_Trackship_Admin
	*/
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	public function init() {

		// Update TrackShip System after fulfillment is fulfilled
		add_action( 'woocommerce_fulfillment_after_fulfill', array( $this, 'update_trackship_system' ) );

		// Update fulfillment tracking link before fulfillment is fulfilled
		add_filter( 'woocommerce_fulfillment_before_fulfill', array( $this, 'update_fulfillment_tracking_link' ) );

		// Sync fulfillment changes
		add_action( 'woocommerce_fulfillment_after_update', array( $this, 'sync_fulfillment_changes' ) );

		// Cleanup fulfillment data after deletion
		add_action( 'woocommerce_fulfillment_after_delete', array( $this, 'cleanup_fulfillment_data' ) );

	}

	public function update_trackship_system( $fulfillment ) {
		// $logger = wc_get_logger();
		// $context = array( 'source' => 'ts_fulfillment_after_fulfill' );
		// $logger->info( 'Fulfillment data: ' . print_r( $fulfillment, true ), $context );

		$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
		if ( ! $tracking_number ) {
			return;
		}
		
		$order_id = $fulfillment->get_entity_id();
		$order = wc_get_order( $order_id );

		$order_shipped = apply_filters( 'is_order_shipped', false, $order );
		if ( $order_shipped && $tracking_number ) {
			as_schedule_single_action( time() + 1, 'trackship_tracking_apicall', array( $order_id ), 'TrackShip' );
			trackship_for_woocommerce()->actions->set_temp_pending( $order_id );
		}
	}

	public function update_fulfillment_tracking_link( $fulfillment ) {
		$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
		if ( ! $tracking_number ) {
			return $fulfillment;
		}

		$order_id = $fulfillment->get_entity_id();

		$tracking_link = trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id, $tracking_number );

		$_tracking_url = $fulfillment->get_meta( '_tracking_url', true );
		$fulfillment->update_meta_data( 'carrier_tracking_url', $_tracking_url );

		if ( $tracking_link ) {
			$fulfillment->update_meta_data( '_tracking_url', $tracking_link );
		}
		return $fulfillment;
	}

	public function sync_fulfillment_changes( $fulfillment ) {
		// $logger = wc_get_logger();
		// $context = array( 'source' => 'ts_fulfillment_after_update' );
		// $logger->info( 'Fulfillment data: ' . print_r( $fulfillment, true ), $context );

		$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
		if ( ! $tracking_number ) {
			return;
		}

		global $wpdb;
		$shipment_table = $wpdb->prefix . 'trackship_shipment';
		$shipment_meta_table = $wpdb->prefix . 'trackship_shipment_meta';

		$fulfillment_id = $fulfillment->get_id();
		$order_id = $fulfillment->get_entity_id();
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$wpdb->prefix}trackship_shipment WHERE fulfillment_id = %d", $fulfillment_id ) );
		
		// Delete tracking number from TrackShip system
		if ( ! $row ) {
			return;
		}
		$api = new WC_TrackShip_Api_Call();
		$api->delete_tracking_number_from_trackship( $order_id, $row->tracking_number, $row->shipping_provider );

		// Delete shipment and its meta data
		$wpdb->delete( $shipment_table, array( 'id' => $row->id ) );
		$wpdb->delete( $shipment_meta_table, array( 'meta_id' => $row->id ) );
	}

	public function cleanup_fulfillment_data( $fulfillment ) {
		// $logger = wc_get_logger();
		// $context = array( 'source' => 'ts_fulfillment_after_delete' );
		// $logger->info( 'Fulfillment data: ' . print_r( $fulfillment, true ), $context );

		$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
		if ( ! $tracking_number ) {
			return;
		}

		$order_id = $fulfillment->get_entity_id();
		$tracking_provider = $fulfillment->get_meta( '_shipment_provider', true );

		// Delete tracking number from TrackShip system
		$api = new WC_TrackShip_Api_Call();
		$api->delete_tracking_number_from_trackship( $order_id, $tracking_number, $tracking_provider );

		global $wpdb;
		$shipment_table = $wpdb->prefix . 'trackship_shipment';
		$shipment_meta_table = $wpdb->prefix . 'trackship_shipment_meta';
		
		// Get shipment ID
		$id = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$wpdb->prefix}trackship_shipment WHERE order_id = %d AND tracking_number = %s", $order_id, $tracking_number ) );

		// Delete shipment and its meta data
		$wpdb->delete( $shipment_table, array( 'id' => $id ) );
		$wpdb->delete( $shipment_meta_table, array( 'meta_id' => $id ) );
	}

}
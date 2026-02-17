<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore;
use Automattic\WooCommerce\Internal\Fulfillments\DTO\Fulfillment;
use Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils;

class WOO_Fulfillment_Tracking_TS4WC {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;
	private $fulfillments_table_exists;

	/**
	 * Private constructor to prevent direct instantiation.
	 */
	private function __construct() {
		// You can hook things here later if needed.
	}

	/**
	 * Get singleton instance.
	 *
	 * @return WOO_Fulfillment_Tracking_TS4WC
	 */
	public static function get_instance() {

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Get fulfillment objects for a given WooCommerce order ID.
	 *
	 * @param int $order_id WooCommerce order ID.
	 *
	 * @return Fulfillment[] Array of fulfillment DTOs (can be empty).
	 */
	public function get_fulfillments_by_order_id( $order_id ) {
		$order_id = (int) $order_id;

		if ( !$order_id ) {
			return [];
		}

		if ( ! function_exists( 'wc_get_container' ) ) {
			return [];
		}

		if ( ! class_exists( FulfillmentsDataStore::class ) ) {
			return [];
		}

		/** @var FulfillmentsDataStore $datastore */
		$datastore = wc_get_container()->get( FulfillmentsDataStore::class );

		if ( ! $datastore ) {
			return [];
		}

		if ( ! $this->is_fulfillments_table_exists() ) {
			return [];
		}

		// read_fulfillments( string $entity_type, string $entity_id )
		$fulfillments = $datastore->read_fulfillments( \WC_Order::class, (string) $order_id );

		if ( ! is_array( $fulfillments ) ) {
			return [];
		}

		return $fulfillments;
	}

	public function woo_orders_tracking_items( $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order ) {
			return [];
		}
		$fulfillments = $this->get_fulfillments_by_order_id( $order_id );
		$tracking_items = [];
		$providers = $this->get_providers();

		foreach ( $fulfillments as $fulfillment ) {
			$tracking_number = $fulfillment->get_meta( '_tracking_number', true );
			if ( ! $tracking_number ) {
				continue;
			}
			$product_array = [];
			$items = $fulfillment->get_meta( '_items', true );
			foreach ( $items as $item_value ) {
				$item_id = $item_value['item_id'];
				$item = $order->get_item( $item_id );
				$product_array[] = (object) array(
					'product'	=> $item ? $item->get_product_id() : null,
					'item_id'	=> $item_value['item_id'],
					'qty'		=> $item_value['qty'],
				);
			}

			$shipment_provider = $fulfillment->get_meta( '_shipment_provider', true );

			$tracking_item = array(
				'fulfillment_id'				=> $fulfillment->get_id(),
				'formatted_tracking_provider'	=> trackship_for_woocommerce()->actions->get_provider_name( $shipment_provider ),
				'tracking_provider'				=> $shipment_provider,
				'tracking_number'				=> $tracking_number,
				'formatted_tracking_link'		=> $fulfillment->get_meta( '_carrier_tracking_url', true ),
				'tracking_id'					=> '',
				'date_shipped'					=> $fulfillment->get_meta( '_date_fulfilled', true ),
				'products_list'					=> $product_array,
				'tracking_provider_image'		=> $providers[ $shipment_provider ]['icon'] ?? null,
				'tracking_page_link'			=> trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id, $tracking_number ),
			);
			$tracking_items[] = $tracking_item;
		}
		return $tracking_items;
	}

	public function has_pending_items( $order_id ) {
		$order = wc_get_order( $order_id );
		$fulfillments = $this->get_fulfillments_by_order_id( $order_id );

		return FulfillmentUtils::has_pending_items( $order, $fulfillments );
	}

	public function get_providers() {
		return FulfillmentUtils::get_shipping_providers_object();
	}

	public function is_fulfillments_table_exists() {
		if ( $this->fulfillments_table_exists ) {
			return 'yes' === $this->fulfillments_table_exists;
		}
		global $wpdb;
		$table_name = $wpdb->prefix . 'wc_order_fulfillments';

		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) === $table_name ) {
			$this->fulfillments_table_exists = 'yes';
			return true;
		} else {
			$this->fulfillments_table_exists = 'no';
		}

		return false;
	}
}

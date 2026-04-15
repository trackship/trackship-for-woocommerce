<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_Fulfillment_Tracking_TS4WC {

	/**
	 * Instance of this class.
	 *
	 * @var object Class Instance
	*/
	private static $instance;
	private $fulfillments_table_exists;
	private $datastore_class_cache;
	private $utils_class_cache;

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
	 * @return array Array of fulfillment objects (can be empty).
	 */
	public function get_fulfillments_by_order_id( $order_id ) {
		$order_id = (int) $order_id;

		if ( !$order_id ) {
			return [];
		}

		if ( ! function_exists( 'wc_get_container' ) ) {
			return [];
		}

		$datastore_class = $this->get_fulfillments_datastore_class();
		if ( ! $datastore_class ) {
			return [];
		}

		$datastore = wc_get_container()->get( $datastore_class );

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
			foreach ( (array) $items as $item_value ) {
				$item_id = $item_value['item_id'] ?? null;
				if ( ! $item_id ) {
					continue;
				}
				$item = $order->get_item( $item_id );
				$product_array[] = (object) array(
					'product'	=> $item ? $item->get_product_id() : null,
					'item_id'	=> $item_id,
					'qty'		=> $item_value['qty'] ?? 1,
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
				'tracking_provider_image'		=> $this->get_provider_image( $providers, $shipment_provider ),
				'tracking_page_link'			=> trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id, $tracking_number ),
			);
			$tracking_items[] = $tracking_item;
		}
		return $tracking_items;
	}

	/**
	 * Returns the provider image, handling WC 10.6 (class name string) and WC 10.7+ (instantiated object).
	 *
	 * @param array|false $providers        Result of get_providers().
	 * @param string      $shipment_provider Provider slug.
	 * @return string|null
	 */
	private function get_provider_image( $providers, $shipment_provider ) {
		if ( ! $providers || ! isset( $providers[ $shipment_provider ] ) ) {
			return null;
		}
		$provider = $providers[ $shipment_provider ];
		// WC 10.7+: provider is already an instantiated object
		if ( is_object( $provider ) ) {
			return method_exists( $provider, 'get_icon' ) ? $provider->get_icon() : null;
		}
		// WC 10.6: provider is a class name string — resolve via container to handle DI dependencies
		if ( is_string( $provider ) && class_exists( $provider ) ) {
			$instance = function_exists( 'wc_get_container' ) ? wc_get_container()->get( $provider ) : new $provider();
			return ( $instance && method_exists( $instance, 'get_icon' ) ) ? $instance->get_icon() : null;
		}
		return null;
	}

	/**
	 * Returns the available FulfillmentsDataStore class name, supporting WooCommerce 10.6 and 10.7+.
	 *
	 * @return string|null Fully-qualified class name, or null if neither exists.
	 */
	private function get_fulfillments_datastore_class() {
		if ( $this->datastore_class_cache !== null ) {
			return $this->datastore_class_cache;
		}
		if ( class_exists( 'Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore' ) ) {
			return $this->datastore_class_cache = 'Automattic\WooCommerce\Admin\Features\Fulfillments\DataStore\FulfillmentsDataStore';
		}
		if ( class_exists( 'Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore' ) ) {
			return $this->datastore_class_cache = 'Automattic\WooCommerce\Internal\DataStores\Fulfillments\FulfillmentsDataStore';
		}
		return $this->datastore_class_cache = false;
	}

	/**
	 * Returns the available FulfillmentUtils class name, supporting WooCommerce 10.6 and 10.7+.
	 *
	 * @return string|null Fully-qualified class name, or null if neither exists.
	 */
	private function get_fulfillment_utils_class() {
		if ( $this->utils_class_cache !== null ) {
			return $this->utils_class_cache;
		}
		if ( class_exists( 'Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils' ) ) {
			return $this->utils_class_cache = 'Automattic\WooCommerce\Admin\Features\Fulfillments\FulfillmentUtils';
		}
		if ( class_exists( 'Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils' ) ) {
			return $this->utils_class_cache = 'Automattic\WooCommerce\Internal\Fulfillments\FulfillmentUtils';
		}
		return $this->utils_class_cache = false;
	}

	public function has_pending_items( $order_id ) {
		$utils_class = $this->get_fulfillment_utils_class();
		if ( ! $utils_class ) {
			return false;
		}
		$order = wc_get_order( $order_id );
		$fulfillments = $this->get_fulfillments_by_order_id( $order_id );

		return $utils_class::has_pending_items( $order, $fulfillments );
	}

	public function get_providers() {
		$utils_class = $this->get_fulfillment_utils_class();
		if ( ! $utils_class ) {
			return false;
		}
		return $utils_class::get_shipping_providers();
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

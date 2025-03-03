<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class WOO_Omnisend_TS4WC {

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
	 * @return WOO_Omnisend_TS4WC
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
		add_action( 'ts_status_change_trigger', array( $this, 'ts_status_change_omnisend_callback'), 10, 4 );
	}

	public function ts_status_change_omnisend_callback ( $order_id, $old_status, $new_status, $tracking_number ) {
		
		if ( !get_trackship_settings( 'omnisend', '') ) {
			return;
		}

		$omnisend_api_key = get_option('omnisend_api_key');

		if ( !$omnisend_api_key ) {
			return;
		}

		// API execution url
		$url = 'https://api.omnisend.com/v5/events';

		$row = trackship_for_woocommerce()->actions->get_shipment_row( $order_id , $tracking_number );

		$order = wc_get_order( $order_id );
		$phone = $order ? $order->get_billing_phone() : '';
		$items = $order ? $order->get_items() : [];

		$products = array();
		foreach ( $items as $item_id => $item ) {
			
			$variation_id = $item->get_variation_id();
			$product_id = $item->get_product_id();
			
			if ( 0 != $variation_id ) {
				$product_id = $variation_id;
			}
			
			$products[$item_id] = array(
				'item_id' => $item_id,
				'product_id' => $product_id,
				'product_name' => $item->get_name(),
				'product_qty' => $item->get_quantity(),
			);
		}
		$products_array = trackship_for_woocommerce()->front->tracking_widget_product_array_callback ( $products, $order_id, [], '', $tracking_number );

		$phone = $order ? $order->get_billing_phone() : '';
		$body = [
			'contact' => [
				'email' => $order ? $order->get_billing_email() : '',
				'firstName' => $order ? $order->get_billing_first_name() : '',
				'lastName' => $order ? $order->get_billing_last_name() : '',
				'phone' => trackship_for_woocommerce()->actions->get_formated_number($phone, $order),
				'address' => $order ? $order->get_billing_address_1() : '',
				'city' => $order ? $order->get_billing_city() : '',
				'state' => $order ? $order->get_billing_state() : '',
				'postalCode' => $order ? $order->get_billing_postcode() : '',
				'country' => $order ? WC()->countries->countries[ $order->get_billing_country() ] : '',
				'countryCode' => $order ? $order->get_billing_country() : '',
			],
			'properties' => [
				'order_id'						=> $order_id,
				'order_number'					=> $order ? $order->get_order_number() : $order_id,
				'tracking_number'				=> $tracking_number,
				'tracking_provider'				=> $row->shipping_provider,
				'tracking_provider_label'		=> trackship_for_woocommerce()->actions->get_provider_name( $row->shipping_provider ),
				'tracking_event_status'			=> $row->shipment_status,
				'tracking_event_status_label'	=> apply_filters('trackship_status_filter', $row->shipment_status ),
				'tracking_est_delivery_date'	=> $row->est_delivery_date,
				'tracking_link'					=> trackship_for_woocommerce()->actions->get_tracking_page_link( $order_id, $tracking_number ),
				'latest_event' 					=> $row->last_event,
				'origin_country'				=> $row->origin_country,
				'destination_country'			=> $row->destination_country,
				'delivery_number'				=> $row->delivery_number,
				'delivery_provider'				=> $row->delivery_provider,
				'shipping_service'				=> $row->shipping_service,
				'last_event_time'				=> $row->last_event_time,
				'products'						=> array_values($products_array),
				'order_status'					=> $order->get_status(),
				'order_status_label'			=> wc_get_order_status_name( $order->get_status() ),
			],
			'eventName' => 'TrackShip tracking events',
			'origin' => 'TrackShip'
		];

		// if ( apply_filters( 'exclude_omnisend_phone', false ) ) {
		// 	unset( $body['data']['attributes']['profile']['data']['attributes']['phone_number'] );
		// }

		// Add requirements header parameters in below array
		$args = array(
			'body'		=> wp_json_encode($body),
			'headers'	=> array(
				'accept'		=> 'application/json',
				'Content-Type'	=> 'application/json',
				'X-API-KEY'		=> $omnisend_api_key,
			),
		);

		// Example API call on integrately
		$response = wp_remote_post( $url, $args );

		$content = print_r($response, true);
		$logger = wc_get_logger();
		$context = array( 'source' => 'trackship-omnisend-response' );
		$logger->info( "Response \n" . $content . "\n", $context );

	}

	
}

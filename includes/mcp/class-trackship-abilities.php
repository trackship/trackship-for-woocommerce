<?php
/**
 * TrackShip Abilities (WordPress Abilities API → MCP).
 *
 * Registers TrackShip read/write operations as WordPress "abilities". When the
 * WordPress MCP Adapter (or WooCommerce's native MCP) is active, abilities flagged
 * meta.mcp.public are automatically exposed as MCP tools — no bespoke JSON-RPC server.
 *
 * If the Abilities API is not present, this class simply registers nothing (graceful
 * degradation); the plugin keeps working exactly as before.
 *
 * @package TrackShip for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackship_Abilities {

	protected static $instance = null;

	/** @var Trackship_Settings_Service */
	private $settings_service;

	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	private function __construct() {
		require_once __DIR__ . '/class-trackship-settings-service.php';
		$this->settings_service = new Trackship_Settings_Service();

		add_action( 'wp_abilities_api_categories_init', array( $this, 'register_category' ) );
		add_action( 'wp_abilities_api_init', array( $this, 'register_abilities' ) );
	}

	/**
	 * Capability gate reused by every ability's permission_callback.
	 *
	 * @return bool
	 */
	public function can_manage() {
		return current_user_can( 'manage_woocommerce' );
	}

	/**
	 * Register the "trackship" ability category.
	 */
	public function register_category() {
		if ( ! function_exists( 'wp_register_ability_category' ) ) {
			return;
		}
		wp_register_ability_category( 'trackship', array(
			'label'			=> __( 'TrackShip', 'trackship-for-woocommerce' ),
			'description'	=> __( 'Shipment tracking, notifications and settings from TrackShip for WooCommerce.', 'trackship-for-woocommerce' ),
		) );
	}

	/**
	 * Register every TrackShip ability.
	 */
	public function register_abilities() {
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return;
		}

		$perm = array( $this, 'can_manage' );
		$mcp_read = array( 'mcp' => array( 'public' => true, 'type' => 'tool' ), 'expose_in_deprecated_woocommerce_mcp' => true );
		$mcp_write = array( 'mcp' => array( 'public' => true, 'type' => 'tool', 'annotations' => array( 'destructiveHint' => false, 'readOnlyHint' => false ) ), 'expose_in_deprecated_woocommerce_mcp' => true );

		// ---- READ: list shipments ----
		wp_register_ability( 'trackship/list-shipments', array(
			'label'					=> __( 'List shipments', 'trackship-for-woocommerce' ),
			'description'			=> __( 'List TrackShip shipments with optional filters (status, provider, date range, free-text search) and pagination. Returns shipment status, tracking number, carrier, dates and last event.', 'trackship-for-woocommerce' ),
			'input_schema'			=> array(
				'type'				=> 'object',
				'properties' => array(
					'search'	=> array( 'type' => 'string', 'description' => 'Match order id/number, provider, tracking number or country.' ),
					'status'	=> array( 'type' => 'string', 'description' => 'Status filter: active, delivered, late_shipment, active_late, tracking_issues, pending_trackship, carrier_unsupported, all_ship, or an exact status slug.' ),
					'provider'	=> array( 'type' => 'string', 'description' => 'Carrier slug (ts_slug); omit or "all" for any.' ),
					'date_from' => array( 'type' => 'string', 'description' => 'Shipping date range start (Y-m-d).' ),
					'date_to'	=> array( 'type' => 'string', 'description' => 'Shipping date range end (Y-m-d).' ),
					'orderby'	=> array( 'type' => 'string', 'enum' => array( 'shipping_date', 'order_id', 'updated_at' ), 'description' => 'Sort column.' ),
					'order'		=> array( 'type' => 'string', 'enum' => array( 'asc', 'desc' ), 'description' => 'Sort direction.' ),
					'page'		=> array( 'type' => 'integer', 'minimum' => 1, 'description' => 'Page number (default 1).' ),
					'per_page'	=> array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'description' => 'Results per page (default 25).' ),
				),
			),
			'output_schema'			=> array( 'type' => 'object' ),
			'category'				=> 'trackship',
			'permission_callback'	=> $perm,
			'execute_callback'		=> array( $this, 'exec_list_shipments' ),
			'meta'					=> $mcp_read,
		) );

		// ---- READ: single shipment ----
		wp_register_ability( 'trackship/get-shipment', array(
			'label'			=> __( 'Get shipment', 'trackship-for-woocommerce' ),
			'description'	=> __( 'Get a single shipment by WooCommerce order id, including full tracking event history.', 'trackship-for-woocommerce' ),
			'input_schema'	=> array(
				'type'		=> 'object',
				'properties' => array(
					'order_id' => array( 'type' => 'integer', 'description' => 'WooCommerce order id.' ),
				),
				'required'	=> array( 'order_id' ),
			),
			'output_schema'			=> array( 'type' => 'object' ),
			'category'				=> 'trackship',
			'permission_callback'	=> $perm,
			'execute_callback'		=> array( $this, 'exec_get_shipment' ),
			'meta'					=> $mcp_read,
		) );

		// ---- READ: list notifications (email/SMS logs) ----
		wp_register_ability( 'trackship/list-notifications', array(
			'label'			=> __( 'List notifications', 'trackship-for-woocommerce' ),
			'description'	=> __( 'List sent shipment notification logs (Email/SMS) with optional filters and pagination. Returns recipient, type, shipment status and delivery result (Sent/Failed).', 'trackship-for-woocommerce' ),
			'input_schema'	=> array(
				'type'		=> 'object',
				'properties' => array(
					'search'	=> array( 'type' => 'string', 'description' => 'Match order id/number, recipient or tracking number.' ),
					'type'		=> array( 'type' => 'string', 'enum' => array( 'Email', 'SMS' ), 'description' => 'Notification channel.' ),
					'shipment_status' => array( 'type' => 'string', 'description' => 'Exact shipment status slug.' ),
					'page'		=> array( 'type' => 'integer', 'minimum' => 1, 'description' => 'Page number (default 1).' ),
					'per_page'	=> array( 'type' => 'integer', 'minimum' => 1, 'maximum' => 100, 'description' => 'Results per page (default 25).' ),
				),
			),
			'output_schema'		=> array( 'type' => 'object' ),
			'category'			=> 'trackship',
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_list_notifications' ),
			'meta'				=> $mcp_read,
		) );

		// ---- READ: analytics summary ----
		wp_register_ability( 'trackship/get-analytics-summary', array(
			'label'				=> __( 'Get analytics summary', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Aggregate shipment metrics for a date range: total, active, delivered, tracking issues, average transit days and delivered rate.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array(
				'type'			=> 'object',
				'properties' => array(
					'date_from' => array( 'type' => 'string', 'description' => 'Range start (Y-m-d).' ),
					'date_to'	=> array( 'type' => 'string', 'description' => 'Range end (Y-m-d). Defaults to today.' ),
				),
				'required'	=> array( 'date_from' ),
			),
			'output_schema'			=> array( 'type' => 'object' ),
			'category'				=> 'trackship',
			'permission_callback'	=> $perm,
			'execute_callback'		=> array( $this, 'exec_get_analytics_summary' ),
			'meta'					=> $mcp_read,
		) );

		// ---- READ: list providers ----
		wp_register_ability( 'trackship/list-providers', array(
			'label'				=> __( 'List shipping providers', 'trackship-for-woocommerce' ),
			'description'		=> __( 'List TrackShip supported shipping providers (carrier display name and slug).', 'trackship-for-woocommerce' ),
			'input_schema'		=> array( 'type' => 'object' ),
			'output_schema'		=> array( 'type' => 'object' ),
			'category'			=> 'trackship',
			'permission_callback'	=> $perm,
			'execute_callback'		=> array( $this, 'exec_list_providers' ),
			'meta'					=> $mcp_read,
		) );

		// ---- READ: get settings ----
		wp_register_ability( 'trackship/get-settings', array(
			'label'				=> __( 'Get TrackShip settings', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Read all TrackShip settings (the full settings option). Reading is unrestricted; writes are limited to a safe whitelist.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array( 'type' => 'object' ),
			'output_schema'		=> array( 'type' => 'object' ),
			'category'			=> 'trackship',
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_get_settings' ),
			'meta'				=> $mcp_read,
		) );

		// ---- READ: email customizer settings ----
		wp_register_ability( 'trackship/get-email-settings', array(
			'label'				=> __( 'Get shipment status email settings', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Read the shipment-status email customizer settings (per-status email design: colors, headings, content, enable flags). Nested as status => { property: value }. Read-only.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array( 'type' => 'object' ),
			'category'			=> 'trackship',
			'output_schema'		=> array( 'type' => 'object' ),
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_get_email_settings' ),
			'meta'				=> $mcp_read,
		) );

		// ---- READ: carrier mappings ----
		wp_register_ability( 'trackship/get-carrier-mappings', array(
			'label'				=> __( 'Get carrier mappings', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Read the shipping carrier mappings (your external/detected provider name → the TrackShip provider it maps to), including the resolved TrackShip provider display name.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array( 'type' => 'object' ),
			'category'			=> 'trackship',
			'output_schema'		=> array( 'type' => 'object' ),
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_get_carrier_mappings' ),
			'meta'				=> $mcp_read,
		) );

		// ---- WRITE: update carrier mappings ----
		wp_register_ability( 'trackship/update-carrier-mappings', array(
			'label'				=> __( 'Update carrier mappings', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Add/update or remove shipping carrier mappings. "set" is a map of detected provider name => TrackShip provider slug (ts_slug); "remove" is a list of detected provider names to delete. Each ts_slug is validated against the known TrackShip provider list; unknown slugs are skipped. This changes store configuration — confirm with the user first.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array(
				'type'		=> 'object',
				'properties' => array(
					'set'	=> array( 'type' => 'object', 'description' => 'Map of detected_provider => TrackShip ts_slug to add or update.' ),
					'remove' => array( 'type' => 'array', 'items' => array( 'type' => 'string' ), 'description' => 'Detected provider names to remove.' ),
				),
			),
			'category'			=> 'trackship',
			'output_schema'		=> array( 'type' => 'object' ),
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_update_carrier_mappings' ),
			'meta'				=> $mcp_write,
		) );

		// ---- ACTION: get shipment status / resync (send tracking to TrackShip) ----
		wp_register_ability( 'trackship/resync-shipments', array(
			'label'				=> __( 'Get shipment status (send tracking to TrackShip)', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Trigger "Get Shipment Status" for one or more orders: sends their tracking info to TrackShip and schedules a status refresh. Accepts "order_id" (single) or "order_ids" (array, max 50). This is a state-changing action that consumes a TrackShip tracking credit per shipped order — confirm with the user before calling. Orders that are not shipped are skipped.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array(
				'type'	=> 'object',
				'properties' => array(
					'order_id'	=> array( 'type' => 'integer', 'description' => 'A single WooCommerce order id.' ),
					'order_ids' => array( 'type' => 'array', 'items' => array( 'type' => 'integer' ), 'maxItems' => 50, 'description' => 'Multiple WooCommerce order ids (max 50).' ),
				),
			),
			'category'			=> 'trackship',
			'output_schema'		=> array( 'type' => 'object' ),
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_resync_shipments' ),
			'meta'				=> $mcp_write,
		) );

		// ---- WRITE: update email customizer settings ----
		wp_register_ability( 'trackship/update-email-settings', array(
			'label'				=> __( 'Update shipment status email settings', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Update per-status email customizer settings for one status group. Provide "status" (e.g. delivered, in_transit, common_settings) and "changes" (property => value). Only whitelisted email properties are accepted (colors, labels, button, toggles, subject/heading/content). This changes email design — confirm with the user before calling.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array(
				'type'	=> 'object',
				'properties' => array(
					'status'	=> array( 'type' => 'string', 'description' => 'Status group: common_settings, in_transit, available_for_pickup, out_for_delivery, failure, on_hold, exception, return_to_sender, delivered, pickup_reminder.' ),
					'changes' => array( 'type' => 'object', 'description' => 'Map of email property => new value. Allowed: ' . implode( ', ', array_keys( $this->settings_service->get_email_property_whitelist() ) ) . '.' ),
				),
				'required'	=> array( 'status', 'changes' ),
			),
			'category'			=> 'trackship',
			'output_schema'		=> array( 'type' => 'object' ),
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_update_email_settings' ),
			'meta'				=> $mcp_write,
		) );

		// ---- WRITE: update settings ----
		wp_register_ability( 'trackship/update-settings', array(
			'label'				=> __( 'Update TrackShip settings', 'trackship-for-woocommerce' ),
			'description'		=> __( 'Update one or more writable TrackShip settings. Only whitelisted keys are accepted. This changes store configuration — confirm with the user before calling.', 'trackship-for-woocommerce' ),
			'input_schema'		=> array(
				'type'		=> 'object',
				'properties' => array(
					'changes' => array(
						'type'	=> 'object',
						'description' => 'Map of setting key => new value. Allowed keys: ' . implode( ', ', array_keys( $this->settings_service->get_whitelist() ) ) . '.',
					),
				),
				'required'	=> array( 'changes' ),
			),
			'output_schema'		=> array( 'type' => 'object' ),
			'category'			=> 'trackship',
			'permission_callback' => $perm,
			'execute_callback'	=> array( $this, 'exec_update_settings' ),
			'meta'				=> $mcp_write,
		) );
	}

	/* ----------------------------------------------------------------------
	 * Execute callbacks
	 * -------------------------------------------------------------------- */

	public function exec_list_shipments( $input ) {
		$input = (array) $input;
		$per_page = isset( $input['per_page'] ) ? max( 1, (int) $input['per_page'] ) : 25;
		$page = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;

		$orderby_map = array( 'order_id' => '1', 'updated_at' => '3', 'shipping_date' => '' );
		$orderby = isset( $input['orderby'] ) ? (string) $input['orderby'] : 'shipping_date';

		$args = array(
			'start'			=> ( $page - 1 ) * $per_page,
			'length'		=> $per_page,
			'search'		=> isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '',
			'status'		=> isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : '',
			'provider'		=> isset( $input['provider'] ) ? sanitize_text_field( $input['provider'] ) : '',
			'start_date'	=> isset( $input['date_from'] ) ? sanitize_text_field( $input['date_from'] ) : '',
			'end_date'		=> isset( $input['date_to'] ) ? sanitize_text_field( $input['date_to'] ) : '',
			'orderby_column' => $orderby_map[ $orderby ] ?? '',
			'order'			=> ( isset( $input['order'] ) && 'asc' === strtolower( $input['order'] ) ) ? 'asc' : 'desc',
		);

		$result = trackship_for_woocommerce()->shipments->query_shipments( $args );

		return array(
			'items'	=> $result['items'],
			'total'	=> $result['total'],
			'page'	=> $page,
			'per_page' => $per_page,
		);
	}

	public function exec_get_shipment( $input ) {
		$input = (array) $input;
		$order_id = isset( $input['order_id'] ) ? (int) $input['order_id'] : 0;
		$shipment = trackship_for_woocommerce()->shipments->get_shipment( $order_id );

		if ( null === $shipment ) {
			return new WP_Error( 'not_found', __( 'No shipment found for that order id.', 'trackship-for-woocommerce' ), array( 'status' => 404 ) );
		}
		return $shipment;
	}

	public function exec_list_notifications( $input ) {
		$input = (array) $input;
		$per_page = isset( $input['per_page'] ) ? max( 1, (int) $input['per_page'] ) : 25;
		$page = isset( $input['page'] ) ? max( 1, (int) $input['page'] ) : 1;

		$args = array(
			'start'		=> ( $page - 1 ) * $per_page,
			'length'	=> $per_page,
			'search'	=> isset( $input['search'] ) ? sanitize_text_field( $input['search'] ) : '',
			'shipment_status' => isset( $input['shipment_status'] ) ? sanitize_text_field( $input['shipment_status'] ) : '',
			'type'		=> isset( $input['type'] ) ? sanitize_text_field( $input['type'] ) : '',
		);

		$result = trackship_for_woocommerce()->logs->query_notifications( $args );

		return array(
			'items'	=> $result['items'],
			'total'	=> $result['total'],
			'page'	=> $page,
			'per_page' => $per_page,
		);
	}

	public function exec_get_analytics_summary( $input ) {
		$input = (array) $input;
		$date_from = isset( $input['date_from'] ) ? sanitize_text_field( $input['date_from'] ) : '';
		$date_to = isset( $input['date_to'] ) ? sanitize_text_field( $input['date_to'] ) : '';

		return trackship_for_woocommerce()->admin->get_analytics_summary( $date_from, $date_to );
	}

	public function exec_list_providers( $input ) {
		global $wpdb;
		$rows = $wpdb->get_results( "SELECT provider_name, ts_slug FROM {$wpdb->prefix}trackship_shipping_provider ORDER BY provider_name ASC", ARRAY_A );
		return array( 'items' => $rows ? $rows : array() );
	}

	public function exec_get_settings( $input ) {
		return $this->settings_service->get_settings();
	}

	public function exec_get_email_settings( $input ) {
		return $this->settings_service->get_email_settings();
	}

	public function exec_get_carrier_mappings( $input ) {
		return $this->settings_service->get_carrier_mappings();
	}

	public function exec_update_carrier_mappings( $input ) {
		$input = (array) $input;
		$set = isset( $input['set'] ) && is_array( $input['set'] ) ? $input['set'] : array();
		$remove = isset( $input['remove'] ) && is_array( $input['remove'] ) ? $input['remove'] : array();
		if ( empty( $set ) && empty( $remove ) ) {
			return new WP_Error( 'no_changes', __( 'Provide "set" and/or "remove".', 'trackship-for-woocommerce' ), array( 'status' => 400 ) );
		}
		return $this->settings_service->update_carrier_mappings( $set, $remove );
	}

	public function exec_resync_shipments( $input ) {
		$input = (array) $input;

		$ids = array();
		if ( isset( $input['order_ids'] ) && is_array( $input['order_ids'] ) ) {
			$ids = $input['order_ids'];
		}
		if ( isset( $input['order_id'] ) ) {
			$ids[] = $input['order_id'];
		}

		$ids = array_values( array_unique( array_filter( array_map( 'intval', $ids ) ) ) );
		if ( empty( $ids ) ) {
			return new WP_Error( 'no_orders', __( 'Provide order_id or order_ids.', 'trackship-for-woocommerce' ), array( 'status' => 400 ) );
		}
		if ( count( $ids ) > 50 ) {
			return new WP_Error( 'too_many', __( 'A maximum of 50 orders can be resynced per call.', 'trackship-for-woocommerce' ), array( 'status' => 400 ) );
		}

		return trackship_for_woocommerce()->shipments->resync_orders( $ids );
	}

	public function exec_update_email_settings( $input ) {
		$input = (array) $input;
		$status = isset( $input['status'] ) ? sanitize_text_field( $input['status'] ) : '';
		$changes = isset( $input['changes'] ) && is_array( $input['changes'] ) ? $input['changes'] : array();
		if ( ! $status ) {
			return new WP_Error( 'no_status', __( 'Provide a status group.', 'trackship-for-woocommerce' ), array( 'status' => 400 ) );
		}
		if ( empty( $changes ) ) {
			return new WP_Error( 'no_changes', __( 'No email settings changes provided.', 'trackship-for-woocommerce' ), array( 'status' => 400 ) );
		}
		return $this->settings_service->update_email_settings( $status, $changes );
	}

	public function exec_update_settings( $input ) {
		$input = (array) $input;
		$changes = isset( $input['changes'] ) && is_array( $input['changes'] ) ? $input['changes'] : array();
		if ( empty( $changes ) ) {
			return new WP_Error( 'no_changes', __( 'No settings changes provided.', 'trackship-for-woocommerce' ), array( 'status' => 400 ) );
		}
		return $this->settings_service->update_settings( $changes );
	}
}

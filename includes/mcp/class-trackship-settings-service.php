<?php
/**
 * TrackShip settings service.
 *
 * Read/write access to a whitelisted subset of TrackShip settings, shared by the
 * MCP abilities layer. Only keys defined in get_whitelist() can be read or written,
 * each with an explicit type + sanitizer, so the MCP surface can never touch arbitrary
 * options.
 *
 * @package TrackShip for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackship_Settings_Service {

	/**
	 * Whitelist of writable/readable TrackShip settings.
	 *
	 * type: boolean | integer | string | email — controls sanitization and the ability schema.
	 *
	 * @return array<string, array{type:string, label:string}>
	 */
	public function get_whitelist() {
		return array(
			// Late shipment admin notifications
			'late_shipments_email_enable'	=> array( 'type' => 'boolean', 'label' => 'Enable Late Shipment admin email' ),
			'late_shipments_days'			=> array( 'type' => 'integer', 'label' => 'Late shipment threshold (days)' ),
			'late_shipments_email_to'		=> array( 'type' => 'string', 'label' => 'Late shipment recipient email(s)' ),
			'late_shipments_digest_time'	=> array( 'type' => 'time', 'label' => 'Late shipment digest time (HH:MM)' ),

			// Exception shipment admin notifications
			'exception_admin_email_enable'	=> array( 'type' => 'boolean', 'label' => 'Enable Exception Shipment admin email' ),
			'exception_shipments_days'		=> array( 'type' => 'integer', 'label' => 'Exception shipment threshold (days)' ),
			'exception_shipments_email_to'	=> array( 'type' => 'string', 'label' => 'Exception shipment recipient email(s)' ),
			'exception_shipments_digest_time'=> array( 'type' => 'time', 'label' => 'Exception shipment digest time (HH:MM)' ),

			// On-hold shipment admin notifications
			'on_hold_admin_email_enable'	=> array( 'type' => 'boolean', 'label' => 'Enable On Hold Shipment admin email' ),
			'on_hold_shipments_days'		=> array( 'type' => 'integer', 'label' => 'On hold shipment threshold (days)' ),
			'on_hold_shipments_email_to'	=> array( 'type' => 'string', 'label' => 'On hold shipment recipient email(s)' ),
			'on_hold_shipments_digest_time'	=> array( 'type' => 'time', 'label' => 'On hold shipment digest time (HH:MM)' ),

			// General notifications
			'enable_email_widget'					=> array( 'type' => 'boolean', 'label' => 'Enable tracking widget in emails' ),
			'enable_notification_for_amazon_order'	=> array( 'type' => 'boolean', 'label' => 'Send notifications for Amazon orders' ),

			// Tracking page behaviour
			'ts_delivered_status'		=> array( 'type' => 'boolean', 'label' => 'Enable delivered order status' ),
			'ts_link_to_carrier'		=> array( 'type' => 'boolean', 'label' => 'Link tracking number to carrier site' ),
			'hide_provider_image'		=> array( 'type' => 'boolean', 'label' => 'Hide shipping provider image' ),
			'ts_hide_list_mile_tracking'=> array( 'type' => 'boolean', 'label' => 'Hide first-mile tracking events' ),
			'ts_hide_from_to'			=> array( 'type' => 'boolean', 'label' => 'Hide shipping from-to' ),
			'ts_tracking_page'			=> array( 'type' => 'boolean', 'label' => 'Enable TrackShip tracking page' ),
			'tracking_page_type'		=> array( 'type' => 'enum', 'enum' => array( 'classic', 'modern' ), 'label' => 'Tracking page type' ),
			'ts_tracking_page_layout'	=> array( 'type' => 'enum', 'enum' => array( 't_layout_1', 't_layout_2', 't_layout_3' ), 'label' => 'Tracker layout' ),
			'ts_tracking_events'		=> array( 'type' => 'enum', 'enum' => array( '0', '1', '2' ), 'label' => 'Tracking event display' ),

			// Tracking page colours & branding
			'wc_ts_bg_color'			=> array( 'type' => 'color', 'label' => 'Tracking page background color' ),
			'wc_ts_font_color'			=> array( 'type' => 'color', 'label' => 'Tracking page font color' ),
			'wc_ts_border_color'		=> array( 'type' => 'color', 'label' => 'Tracking page border color' ),
			'wc_ts_link_color'			=> array( 'type' => 'color', 'label' => 'Tracking page link color' ),
			'wc_ts_border_radius'		=> array( 'type' => 'integer', 'label' => 'Tracking page border radius' ),
			'ts_use_villa_email_template' => array( 'type' => 'boolean', 'label' => 'Use Villa email template' ),

			// Track form button
			'form_tab_view'			=> array( 'type' => 'enum', 'enum' => array( 'both', 'order_details', 'tracking_details' ), 'label' => 'Track form tabs display' ),
			'form_button_Text'		=> array( 'type' => 'string', 'label' => 'Track form button text' ),
			'form_button_color'		=> array( 'type' => 'color', 'label' => 'Track form button color' ),
			'form_button_text_color' => array( 'type' => 'color', 'label' => 'Track form button text color' ),
			'form_button_border_radius' => array( 'type' => 'integer', 'label' => 'Track form button border radius' ),

			// Integrations
			'klaviyo'	=> array( 'type' => 'boolean', 'label' => 'Enable Klaviyo integration' ),
			'omnisend'	=> array( 'type' => 'boolean', 'label' => 'Enable Omnisend integration' ),

			// Automation
			'trackship_trigger_order_statuses' => array( 'type' => 'order_statuses', 'label' => 'Order statuses that send tracking to TrackShip' ),
		);
	}

	/**
	 * Read TrackShip settings (the full `trackship_settings` option), minus internal
	 * state/dismissal flags that are not meaningful configuration.
	 *
	 * Reading is otherwise unrestricted; only writes are constrained to the whitelist.
	 *
	 * @return array<string, mixed> Stored settings, excluding internal keys.
	 */
	public function get_settings() {
		$all = get_option( 'trackship_settings', array() );
		$out = array();
		foreach ( (array) $all as $key => $value ) {
			if ( $this->is_internal_key( $key ) ) {
				continue;
			}
			$out[ $key ] = $value;
		}
		return $out;
	}

	/**
	 * Read the shipment-status email customizer settings (the full `trackship_email_settings`
	 * option). Nested as [ status_group => [ property => value ] ]. Read-only.
	 *
	 * @return array<string, mixed> Per-status email customizer settings.
	 */
	public function get_email_settings() {
		return get_option( 'trackship_email_settings', array() );
	}

	/**
	 * Valid email customizer status groups (the top-level keys of trackship_email_settings
	 * that hold per-status design settings).
	 *
	 * @return string[]
	 */
	public function get_email_status_groups() {
		return array(
			'common_settings', 'in_transit', 'available_for_pickup', 'out_for_delivery',
			'failure', 'on_hold', 'exception', 'return_to_sender', 'delivered', 'pickup_reminder',
		);
	}

	/**
	 * Whitelist of editable email properties within a status group, with types for sanitization.
	 *
	 * @return array<string, string> property => type (string|html|color|integer|boolean).
	 */
	public function get_email_property_whitelist() {
		return array(
			'enable'					=> 'boolean',
			'subject'					=> 'string',
			'heading'					=> 'string',
			'content'					=> 'html',
			'shipped_product_label'		=> 'string',
			'shipping_address_label'	=> 'string',
			'track_button_Text'			=> 'string',
			'border_color'				=> 'color',
			'link_color'				=> 'color',
			'bg_color'					=> 'color',
			'font_color'				=> 'color',
			'track_button_color'		=> 'color',
			'track_button_text_color'	=> 'color',
			'track_button_border_radius'=> 'integer',
			'days'						=> 'integer',
			'tracking_page_layout'		=> 'string',
			'show_trackship_branding'	=> 'boolean',
			'shipping_provider_logo'	=> 'boolean',
			'ts_last_event'				=> 'boolean',
			'show_order_details'		=> 'boolean',
			'show_product_image'		=> 'boolean',
			'show_shipping_address'		=> 'boolean',
		);
	}

	/**
	 * Update whitelisted email customizer properties for one status group.
	 *
	 * @param string $status Status group (must be in get_email_status_groups()).
	 * @param array<string, mixed> $changes property => new value.
	 * @return array { status, updated:map, skipped:list } or { error, valid_groups }.
	 */
	public function update_email_settings( $status, array $changes ) {
		$groups = $this->get_email_status_groups();
		if ( ! in_array( $status, $groups, true ) ) {
			return array( 'error' => 'invalid status group', 'valid_groups' => $groups );
		}

		$whitelist = $this->get_email_property_whitelist();
		$updated = array();
		$skipped = array();

		foreach ( $changes as $key => $value ) {
			if ( ! isset( $whitelist[ $key ] ) ) {
				$skipped[] = $key;
				continue;
			}
			$clean = $this->sanitize_email_prop( $whitelist[ $key ], $value );
			update_trackship_email_settings( $status, $key, $clean );
			$updated[ $key ] = $clean;
		}

		return array(
			'status' => $status,
			'updated' => $updated,
			'skipped' => $skipped,
		);
	}

	/**
	 * Sanitize an email property value based on its declared type.
	 *
	 * @param string $type string|html|color|integer|boolean.
	 * @param mixed $value Raw value.
	 * @return string Stored value (email settings use '1'/'0' for booleans).
	 */
	private function sanitize_email_prop( $type, $value ) {
		switch ( $type ) {
			case 'boolean':
				return rest_sanitize_boolean( $value ) ? '1' : '0';
			case 'integer':
				return (string) (int) $value;
			case 'color':
				$c = sanitize_hex_color( $value );
				return $c ? $c : '';
			case 'html':
				return wp_kses_post( $value );
			case 'string':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Read the carrier mappings (detected provider name => TrackShip provider), with the
	 * resolved TrackShip provider display name for each.
	 *
	 * @return array { mappings:[{detected_provider, ts_slug, ts_provider_name}], total }
	 */
	public function get_carrier_mappings() {
		$map = get_option( 'trackship_map_provider', array() );
		if ( ! is_array( $map ) ) {
			$map = array();
		}
		$out = array();
		foreach ( $map as $detected => $slug ) {
			$out[] = array(
				'detected_provider' => (string) $detected,
				'ts_slug'			=> (string) $slug,
				'ts_provider_name'	=> trackship_for_woocommerce()->actions->get_provider_name( $slug ),
			);
		}
		return array( 'mappings' => $out, 'total' => count( $out ) );
	}

	/**
	 * Add/update and/or remove carrier mappings. Each ts_slug is validated against the
	 * known TrackShip provider list; unknown slugs are skipped (not written).
	 *
	 * @param array<string,string> $set detected_provider => ts_slug to add or update.
	 * @param string[] $remove detected_provider names to remove.
	 * @return array { applied:map, removed:list, skipped:list, total:int }
	 */
	public function update_carrier_mappings( array $set = array(), array $remove = array() ) {
		$map = get_option( 'trackship_map_provider', array() );
		if ( ! is_array( $map ) ) {
			$map = array();
		}
		$valid = $this->get_valid_provider_slugs();

		$applied = array();
		$skipped = array();
		$removed = array();

		foreach ( $set as $detected => $slug ) {
			$detected = sanitize_text_field( $detected );
			$slug = sanitize_text_field( $slug );
			if ( '' === $detected ) {
				continue;
			}
			if ( ! in_array( $slug, $valid, true ) ) {
				$skipped[] = array( 'detected_provider' => $detected, 'ts_slug' => $slug, 'reason' => 'unknown TrackShip provider slug' );
				continue;
			}
			$map[ $detected ] = $slug;
			$applied[ $detected ] = $slug;
		}

		foreach ( $remove as $detected ) {
			$detected = sanitize_text_field( $detected );
			if ( isset( $map[ $detected ] ) ) {
				unset( $map[ $detected ] );
				$removed[] = $detected;
			}
		}

		update_option( 'trackship_map_provider', $map );

		return array(
			'applied' => $applied,
			'removed' => $removed,
			'skipped' => $skipped,
			'total' => count( $map ),
		);
	}

	/**
	 * Valid TrackShip provider slugs from the provider lookup table.
	 *
	 * @return string[]
	 */
	private function get_valid_provider_slugs() {
		global $wpdb;
		$slugs = $wpdb->get_col( "SELECT ts_slug FROM {$wpdb->prefix}trackship_shipping_provider" );
		return is_array( $slugs ) ? $slugs : array();
	}

	/**
	 * Internal/state flags that should never be shown or edited via the MCP surface
	 * (review-notice dismissals, popup dismissals, fulfillment ignore, DB version).
	 *
	 * @param string $key Setting key.
	 * @return bool
	 */
	private function is_internal_key( $key ) {
		if ( 0 === strpos( $key, 'ts_review_ignore_' ) ) {
			return true;
		}
		if ( 0 === strpos( $key, 'ts_popup_ignore' ) ) {
			return true;
		}
		if ( in_array( $key, array( 'ts_fulfillments_ignore', 'trackship_db', 'wc_admin_notice' ), true ) ) {
			return true;
		}
		return false;
	}

	/**
	 * Apply a set of setting changes (whitelisted keys only, sanitized per type).
	 *
	 * @param array<string, mixed> $changes key => new value.
	 * @return array {
	 * @type array $updated Map of key => stored value for applied changes.
	 * @type array $skipped List of keys ignored because they are not whitelisted.
	 * }
	 */
	public function update_settings( array $changes ) {
		$whitelist = $this->get_whitelist();
		$updated = array();
		$skipped = array(); // keys not in the whitelist
		$invalid = array(); // keys with a value that failed validation

		foreach ( $changes as $key => $value ) {
			if ( ! isset( $whitelist[ $key ] ) ) {
				$skipped[] = $key;
				continue;
			}
			$clean = $this->sanitize( $whitelist[ $key ], $value );
			if ( is_wp_error( $clean ) ) {
				$invalid[] = array( 'key' => $key, 'reason' => $clean->get_error_message() );
				continue;
			}
			update_trackship_settings( $key, $clean );
			$updated[ $key ] = $this->normalize( $whitelist[ $key ]['type'], $clean );
		}

		return array(
			'updated' => $updated,
			'skipped' => $skipped,
			'invalid' => $invalid,
		);
	}

	/**
	 * Sanitize/validate an incoming value for storage based on its whitelist meta.
	 *
	 * @param array $meta Whitelist entry { type, enum? }.
	 * @param mixed $value Raw value.
	 * @return mixed|WP_Error Clean value, or WP_Error when the value is invalid.
	 */
	private function sanitize( $meta, $value ) {
		$type = $meta['type'];
		switch ( $type ) {
			case 'boolean':
				// Stored as '1' / '' to match the existing checkbox settings convention.
				return rest_sanitize_boolean( $value ) ? '1' : '';
			case 'integer':
				return (string) (int) $value;
			case 'email':
				return sanitize_email( $value );
			case 'color':
				$raw = trim( (string) $value );
				if ( '' !== $raw && '#' !== $raw[0] ) {
					$raw = '#' . $raw;
				}
				$color = sanitize_hex_color( $raw );
				return null === $color ? new WP_Error( 'invalid_color', 'Not a valid hex color (e.g. #1a2b3c).' ) : $color;
			case 'time':
				$time = trim( (string) $value );
				if ( ! preg_match( '/^([01]?\d|2[0-3]):[0-5]\d$/', $time ) ) {
					return new WP_Error( 'invalid_time', 'Time must be in 24h HH:MM format.' );
				}
				return $time;
			case 'enum':
				$val = sanitize_text_field( (string) $value );
				$allowed = isset( $meta['enum'] ) ? $meta['enum'] : array();
				return in_array( $val, $allowed, true )
					? $val
					: new WP_Error( 'invalid_enum', 'Allowed values: ' . implode( ', ', $allowed ) . '.' );
			case 'order_statuses':
				$valid = $this->get_valid_order_statuses();
				$out = array();
				foreach ( (array) $value as $status ) {
					$status = sanitize_text_field( $status );
					if ( in_array( $status, $valid, true ) ) {
						$out[] = $status;
					}
				}
				if ( empty( $out ) ) {
					return new WP_Error( 'invalid_statuses', 'No valid order statuses. Allowed: ' . implode( ', ', $valid ) . '.' );
				}
				return array_values( array_unique( $out ) );
			case 'string':
			default:
				return sanitize_text_field( $value );
		}
	}

	/**
	 * Normalize a stored value for output based on its declared type.
	 *
	 * @param string $type Type slug.
	 * @param mixed $value Stored value.
	 * @return mixed
	 */
	private function normalize( $type, $value ) {
		switch ( $type ) {
			case 'boolean':
				return (bool) ( '1' === $value || 1 === $value || true === $value );
			case 'integer':
				return (int) $value;
			case 'order_statuses':
				return array_values( (array) $value );
			default:
				return is_array( $value ) ? $value : (string) $value;
		}
	}

	/**
	 * Valid WooCommerce order status slugs (without the `wc-` prefix), matching how
	 * trackship_trigger_order_statuses is stored and compared against $order->get_status().
	 *
	 * @return string[]
	 */
	private function get_valid_order_statuses() {
		$statuses = array();
		foreach ( array_keys( wc_get_order_statuses() ) as $slug ) {
			$statuses[] = preg_replace( '/^wc-/', '', $slug );
		}
		return $statuses;
	}
}

<?php
/**
 * TrackShip MCP - the tool list.
 *
 * TrackShip's tools as the MCP server offers them. The definitions come from
 * Trackship_Abilities::tool_map(), the same source the WordPress Abilities (WooCommerce MCP)
 * route reads, so the two routes cannot drift apart.
 *
 * NAMING
 *
 * A tool's `name` is its identifier: an AI calls it by that string and a client stores it, so
 * it must never change once shipped. Every name is prefixed `trackship-`. A tool's `title` is
 * for people and starts with "TrackShip › ", because MCP has no way to group tools and a client
 * may list tools from several connectors side by side.
 *
 * WRITES
 *
 * Write tools are dropped from the list while the merchant has switched "Allow the AI to make
 * changes" off, and refused when called by name, so a client that remembers a name from an
 * earlier session cannot still use it.
 *
 * @package TrackShip for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackship_MCP_Registry {

	/**
	 * Prefix on every tool name (trackship-list-shipments, ...). Never change it once shipped.
	 */
	const PREFIX = 'trackship';

	/**
	 * Prefix on every tool title, as AI clients show it.
	 */
	const TITLE_PREFIX = 'TrackShip › ';

	/**
	 * The single instance.
	 *
	 * @var Trackship_MCP_Registry|null
	 */
	protected static $instance = null;

	/**
	 * Get the registry.
	 *
	 * @return Trackship_MCP_Registry
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * TrackShip's tool definitions, keyed by short name.
	 *
	 * Read when needed rather than at load time: the labels and descriptions are translated,
	 * and the text domain is not ready until init.
	 *
	 * @return array
	 */
	protected function definitions() {
		if ( ! class_exists( 'Trackship_Abilities' ) ) {
			return array();
		}

		return Trackship_Abilities::get_instance()->tool_map();
	}

	/**
	 * Every available tool, in MCP's shape, keyed by tool name.
	 *
	 * @return array
	 */
	public function tools() {
		$writes = Trackship_MCP_Settings::writes_enabled();
		$out = array();

		foreach ( $this->definitions() as $short => $tool ) {
			if ( ! empty( $tool['writes'] ) && ! $writes ) {
				continue;
			}

			$name = self::PREFIX . '-' . sanitize_key( $short );
			$label = isset( $tool['label'] ) && '' !== $tool['label']
				? $tool['label']
				: ucfirst( str_replace( array( '-', '_' ), ' ', $short ) );

			$out[ $name ] = array(
				'name' => $name,
				'title' => self::TITLE_PREFIX . $label,
				'description' => isset( $tool['description'] ) ? $tool['description'] : '',
				'inputSchema' => isset( $tool['input_schema'] ) ? $tool['input_schema'] : array( 'type' => 'object' ),
				'annotations' => array(
					'readOnlyHint' => empty( $tool['writes'] ),
					'destructiveHint' => ! empty( $tool['destructive'] ),
				),
			);
		}

		return $out;
	}

	/**
	 * Run one tool.
	 *
	 * The server has already proved who is calling; this decides whether that person may manage
	 * TrackShip at all.
	 *
	 * @param string $name Tool name as advertised.
	 * @param array $args Tool arguments.
	 * @return array|WP_Error
	 */
	public function call( $name, $args ) {
		$tool = $this->find( $name );

		if ( ! $tool ) {
			return new WP_Error(
				'trackship_mcp_unknown_tool',
				__( 'That tool is not available on this connection.', 'trackship-for-woocommerce' )
			);
		}

		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			return new WP_Error(
				'trackship_forbidden',
				__( 'This connection is not allowed to manage TrackShip.', 'trackship-for-woocommerce' )
			);
		}

		if ( empty( $tool['callback'] ) || ! is_callable( $tool['callback'] ) ) {
			return new WP_Error(
				'trackship_mcp_not_executable',
				__( 'That tool cannot be run right now.', 'trackship-for-woocommerce' )
			);
		}

		return call_user_func( $tool['callback'], is_array( $args ) ? $args : array() );
	}

	/**
	 * Find a tool definition by its advertised name, honouring the same write gate the list does.
	 *
	 * @param string $name Tool name.
	 * @return array|null
	 */
	protected function find( $name ) {
		$writes = Trackship_MCP_Settings::writes_enabled();

		foreach ( $this->definitions() as $short => $tool ) {
			if ( self::PREFIX . '-' . sanitize_key( $short ) !== $name ) {
				continue;
			}

			if ( ! empty( $tool['writes'] ) && ! $writes ) {
				return null;
			}

			return $tool;
		}

		return null;
	}
}

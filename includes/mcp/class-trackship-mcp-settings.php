<?php
/**
 * TrackShip MCP - settings.
 *
 * The AI Assistant's own options, kept apart from trackship_settings.
 *
 * @package TrackShip for WooCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackship_MCP_Settings {

	/**
	 * Option holding the merchant's choices, as a flat array.
	 */
	const OPTION = 'trackship_mcp_settings';

	/**
	 * Option holding the last few inbound attempts on the link.
	 *
	 * The one thing the settings screen cannot otherwise know is whether anything is even
	 * arriving. A firewall in front of WordPress refuses callers before this plugin runs, so
	 * from in here a blocked AI service and an AI service nobody ever pointed at the store
	 * look exactly the same -- silence. Writing down what DOES arrive turns that silence
	 * into evidence: requests arriving and refused is a wrong link, requests arriving and
	 * answered is a working one, and nothing arriving at all while an AI app reports an
	 * error is something in front of WordPress.
	 */
	const ATTEMPTS = 'trackship_mcp_attempts';

	/**
	 * How many attempts to keep.
	 *
	 * Raised from ten once the connections screen started reading this. Ten was enough to
	 * answer "is anything arriving at all", which was all it was for; it is not enough to
	 * show WHO is arriving, because one assistant asking a couple of questions fills the
	 * whole record and pushes the other person off the end of it.
	 */
	const ATTEMPTS_KEEP = 60;


	/**
	 * Defaults, and the full list of recognised keys.
	 *
	 * @return array
	 */
	public static function defaults() {
		return array(
			// The connection itself. Off until the merchant asks for it: TrackShip must never
			// open a route to a store that did not request one.
			'enabled' => 0,

			// Whether tools that change data are offered at all. On, so the link carries the
			// same tools a desktop client would get through WooCommerce MCP -- a merchant on
			// claude.ai or a phone should not quietly get a smaller set.
			'allow_write' => 1,

			// Days of audit history to keep. 0 keeps everything.
			'audit_retention' => 30,
		);
	}

	/**
	 * Read one setting.
	 *
	 * @param string $key Setting key.
	 * @param mixed $default Optional override for the declared default.
	 * @return mixed
	 */
	public static function get( $key, $default = null ) {
		$all = get_option( self::OPTION, array() );
		$all = is_array( $all ) ? $all : array();
		$defaults = self::defaults();

		if ( array_key_exists( $key, $all ) ) {
			return $all[ $key ];
		}

		if ( null !== $default ) {
			return $default;
		}

		return isset( $defaults[ $key ] ) ? $defaults[ $key ] : null;
	}

	/**
	 * Write one setting. Unknown keys are ignored so a stray field on a settings screen
	 * cannot quietly grow this option.
	 *
	 * @param string $key Setting key.
	 * @param mixed $value Value.
	 * @return void
	 */
	public static function set( $key, $value ) {
		if ( ! array_key_exists( $key, self::defaults() ) ) {
			return;
		}

		$all = get_option( self::OPTION, array() );
		$all = is_array( $all ) ? $all : array();

		$all[ $key ] = $value;

		update_option( self::OPTION, $all );
	}

	/**
	 * Whether the connection is switched on.
	 *
	 * @return bool
	 */
	public static function enabled() {
		return (bool) self::get( 'enabled' );
	}

	/**
	 * Whether tools that change data are offered.
	 *
	 * @return bool
	 */
	public static function writes_enabled() {
		return (bool) self::get( 'allow_write' );
	}

	/**
	 * Note that something reached the link, and what happened to it.
	 *
	 * @param string $outcome 'ok' when the caller authenticated, otherwise why not.
	 * @return void
	 */
	public static function record_attempt( $outcome, $via = '' ) {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- sanitised on each line.
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		$attempts = self::attempts();

		array_unshift(
			$attempts,
			array(
				'time' => time(),
				'ip' => substr( $address, 0, 45 ),
				'agent' => substr( trim( $agent ), 0, 120 ),
				'outcome' => (string) $outcome,
				// Which credential got the caller in ('key' for a signed-in person).
				'via' => (string) $via,
			)
		);

		update_option( self::ATTEMPTS, array_slice( $attempts, 0, self::ATTEMPTS_KEEP ), false );
	}

	/**
	 * The recent inbound attempts, newest first.
	 *
	 * @return array
	 */
	public static function attempts() {
		$attempts = get_option( self::ATTEMPTS, array() );
		return is_array( $attempts ) ? $attempts : array();
	}
}

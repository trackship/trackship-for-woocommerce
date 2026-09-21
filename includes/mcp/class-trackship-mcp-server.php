<?php
/**
 * TrackShip MCP - the server.
 *
 * One endpoint an AI client connects to as a remote MCP server:
 *
 * https://<site>/wp-json/trackship/v1/mcp/<connection-token>
 *
 * Transport is MCP "Streamable HTTP" in stateless JSON mode: POST carries JSON-RPC 2.0
 * (initialize / tools/list / tools/call / ping) and GET is answered with 405, which the
 * specification permits for a server that never speaks first.
 *
 * WHY THE TOKEN IS IN THE URL
 *
 * Because the places a shop owner actually uses AI cannot send a header. claude.ai's
 * connector dialog takes a URL and nothing else; so does ChatGPT's; so does a phone. A
 * header-authenticated server is a server those three can never reach, which is precisely
 * the wall WooCommerce's own MCP route runs into. The cost is that the URL IS the
 * credential, and every screen that shows it says so.
 *
 * THIS CLASS OWNS NO TOOLS
 *
 * It authenticates, unwraps JSON-RPC, asks Trackship_MCP_Registry what exists, runs the tool,
 * and writes down what happened.
 *
 * @package TrackShip for WooCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Trackship_MCP_Server {

	/**
	 * REST namespace. Identical on every installation; only the site prefix differs, and
	 * that is resolved at runtime by rest_url().
	 */
	const REST_NS = 'trackship/v1';

	/**
	 * MCP protocol revision this server implements. A client asking for a revision we
	 * recognise gets that one back, so older clients keep working.
	 */
	const PROTOCOL = '2025-06-18';

	/**
	 * Revisions this server can speak.
	 *
	 * @var array
	 */
	protected $supported_protocols = array( '2025-06-18', '2025-03-26', '2024-11-05' );

	/**
	 * The single instance.
	 *
	 * @var Trackship_MCP_Server|null
	 */
	protected static $instance = null;

	/**
	 * The calling app's name for this request, once worked out.
	 *
	 * @var string
	 */
	protected $client = '';

	/**
	 * Get the server.
	 *
	 * @return Trackship_MCP_Server
	 */
	public static function instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Register hooks.
	 *
	 * @return void
	 */
	public function init() {
		add_action( 'rest_api_init', array( $this, 'register_routes' ) );
	}

	/*
	|--------------------------------------------------------------------------
	| Links
	|--------------------------------------------------------------------------
	*/

	/**
	 * The connection URL: one address for everyone. Whoever adds it signs in as themselves.
	 *
	 * @return string Empty while the connection is switched off.
	 */
	public static function endpoint_url() {
		return Trackship_MCP_Settings::enabled() ? rest_url( self::REST_NS . '/mcp' ) : '';
	}

	/**
	 * Register the routes.
	 *
	 * Nothing is registered while the connection is off, so the URL simply does not exist
	 * -- a store that has not opted in has no endpoint to find, not a closed one.
	 *
	 * @return void
	 */
	public function register_routes() {
		if ( ! Trackship_MCP_Settings::enabled() ) {
			return;
		}

		$args = array(
			array(
				'methods' => 'POST, GET',
				'callback' => array( $this, 'handle' ),
				'permission_callback' => array( $this, 'permission_check' ),
			),
		);

		// One route. Credentials travel in the Authorization header, never in the address.
		register_rest_route( self::REST_NS, '/mcp', $args );
	}

	/**
	 * Refuse, and say where to sign in.
	 *
	 * WHY EVERY 401 GOES THROUGH HERE
	 *
	 * There were four ways to refuse a caller and only one of them sent the
	 * WWW-Authenticate header. Without that header an AI client has no way of learning that
	 * this address HAS a sign-in service -- it reports "the connection failed" and stops.
	 *
	 * The three silent ones were the worst three to be silent on, because all three are
	 * recoverable and the client could have recovered by itself. Chief among them: a
	 * connection the merchant had just revoked. Revoking is meant to mean "sign in again";
	 * it meant "this is broken now, remove the connector and add it back".
	 *
	 * @param string $code Error code.
	 * @param string $message What went wrong, written for a person.
	 * @return WP_Error
	 */
	protected function refuse( $code, $message ) {
		if ( class_exists( 'Trackship_MCP_OAuth' ) ) {
			Trackship_MCP_OAuth::challenge();
		}

		return new WP_Error( $code, $message, array( 'status' => 401 ) );
	}

	/**
	 * Authenticate the caller and establish the WordPress user the request acts as.
	 *
	 * Two accepted credentials, in order:
	 * 1. a token issued to one person when they signed in, sent as Authorization: Bearer,
	 * 2. an already-authenticated WordPress user (application password).
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return true|WP_Error
	 */
	public function permission_check( $request ) {
		$presented = '';
		$header = (string) $request->get_header( 'authorization' );

		if ( 0 === stripos( $header, 'bearer ' ) ) {
			$presented = trim( substr( $header, 7 ) );
		}

		// A token issued to one person when they signed in.
		if ( '' !== $presented && class_exists( 'Trackship_MCP_Keys' ) ) {
			$key = Trackship_MCP_Keys::match( $presented );

			if ( $key ) {
				if ( $key['revoked'] ) {
					Trackship_MCP_Settings::record_attempt( 'revoked' );

					return $this->refuse(
						'trackship_mcp_revoked',
						__( 'This connection has been turned off. Connect again to get a new one.', 'trackship-for-woocommerce' )
					);
				}

				if ( get_userdata( $key['user_id'] ) ) {
					wp_set_current_user( $key['user_id'] );
					Trackship_MCP_Settings::record_attempt( 'ok', 'key' );

					return true;
				}

				// The person was deleted from WordPress and their connection outlived them.
				Trackship_MCP_Settings::record_attempt( 'no-user' );

				return $this->refuse(
					'trackship_mcp_no_user',
					__( 'The account this connection belongs to no longer exists.', 'trackship-for-woocommerce' )
				);
			}
		}

		if ( is_user_logged_in() ) {
			Trackship_MCP_Settings::record_attempt( 'ok' );
			return true;
		}

		Trackship_MCP_Settings::record_attempt( 'bad-token' );

		return $this->refuse(
			'trackship_mcp_unauthorized',
			__( 'Authentication required.', 'trackship-for-woocommerce' )
		);
	}

	/**
	 * Entry point: unwrap the JSON-RPC envelope and dispatch.
	 *
	 * @param WP_REST_Request $request Request object.
	 * @return WP_REST_Response
	 */
	public function handle( $request ) {
		if ( 'POST' !== $request->get_method() ) {
			$response = new WP_REST_Response( null, 405 );
			$response->header( 'Allow', 'POST' );
			return $response;
		}

		/*
		 * A GET here is a request for the server-initiated event stream, not a mistake.
		 *
		 * MCP's Streamable HTTP transport lets a client open one with GET, and requires the
		 * server to answer in one of exactly two ways: hand back a text/event-stream, or
		 * refuse with 405 so the client knows this endpoint has no stream and everything
		 * happens over POST.
		 *
		 * This used to fall through to the line below and answer 200 with a JSON-RPC parse
		 * error -- a success status on a stream the client had just asked for. VS Code took
		 * that 200 as the stream being open and sat waiting for events that were never
		 * coming, reporting "Waiting for server to respond to initialize" every five seconds
		 * while its POST sat unread. Cursor and claude.ai never open the GET, which is why
		 * the same endpoint worked perfectly for them and hung for VS Code.
		 *
		 * 405 is not a failure. It is the answer.
		 */
		$method = strtoupper( (string) $request->get_method() );

		if ( 'POST' !== $method ) {
			return new WP_REST_Response(
				$this->rpc_error( null, -32600, 'This endpoint does not offer a server-initiated stream. Send JSON-RPC over POST.' ),
				405,
				array( 'Allow' => 'POST' )
			);
		}

		$body = $request->get_json_params();

		if ( ! is_array( $body ) || empty( $body ) ) {
			return new WP_REST_Response( $this->rpc_error( null, -32700, 'Parse error' ), 200 );
		}

		// JSON-RPC batch. Capped: a batch is a convenience, not a way to ask one HTTP
		// request to do unbounded work.
		if ( isset( $body[0] ) ) {
			$out = array();

			foreach ( array_slice( $body, 0, 20 ) as $message ) {
				$result = is_array( $message ) ? $this->dispatch( $message ) : $this->rpc_error( null, -32600, 'Invalid Request' );
				if ( null !== $result ) {
					$out[] = $result;
				}
			}

			return empty( $out ) ? new WP_REST_Response( null, 202 ) : new WP_REST_Response( $out, 200 );
		}

		$result = $this->dispatch( $body );

		// Notifications carry no id and get no body.
		if ( null === $result ) {
			return new WP_REST_Response( null, 202 );
		}

		return new WP_REST_Response( $result, 200 );
	}

	/**
	 * Dispatch one JSON-RPC message.
	 *
	 * @param array $message Decoded JSON-RPC message.
	 * @return array|null Response, or null for notifications.
	 */
	protected function dispatch( $message ) {
		$method = isset( $message['method'] ) ? (string) $message['method'] : '';
		$id = isset( $message['id'] ) ? $message['id'] : null;
		$params = isset( $message['params'] ) && is_array( $message['params'] ) ? $message['params'] : array();

		// A message without an id is a notification: acknowledge, answer nothing.
		if ( null === $id ) {
			return null;
		}

		if ( '' === $method ) {
			return $this->rpc_error( $id, -32600, 'Invalid Request' );
		}

		switch ( $method ) {

			case 'initialize':
				$this->remember_client( $params );

				$requested = isset( $params['protocolVersion'] ) ? (string) $params['protocolVersion'] : '';

				return $this->rpc_result(
					$id,
					array(
						'protocolVersion' => in_array( $requested, $this->supported_protocols, true ) ? $requested : self::PROTOCOL,
						'capabilities' => array( 'tools' => array( 'listChanged' => false ) ),
						'serverInfo' => $this->server_info(),
					)
				);

			case 'ping':
				return $this->rpc_result( $id, new stdClass() );

			case 'tools/list':
				return $this->rpc_result( $id, array( 'tools' => array_values( $this->tools() ) ) );

			case 'tools/call':
				$name = isset( $params['name'] ) ? (string) $params['name'] : '';
				$args = isset( $params['arguments'] ) && is_array( $params['arguments'] ) ? $params['arguments'] : array();

				return $this->rpc_result( $id, $this->call_tool( $name, $args ) );
		}

		return $this->rpc_error( $id, -32601, 'Method not found' );
	}

	/**
	 * Build a JSON-RPC success envelope.
	 *
	 * @param mixed $id Request id.
	 * @param mixed $result Result payload.
	 * @return array
	 */
	protected function rpc_result( $id, $result ) {
		return array(
			'jsonrpc' => '2.0',
			'id' => $id,
			'result' => $result,
		);
	}

	/**
	 * Build a JSON-RPC error envelope.
	 *
	 * @param mixed $id Request id.
	 * @param int $code JSON-RPC error code.
	 * @param string $message Error message.
	 * @return array
	 */
	protected function rpc_error( $id, $code, $message ) {
		return array(
			'jsonrpc' => '2.0',
			'id' => $id,
			'error' => array(
				'code' => (int) $code,
				'message' => $message,
			),
		);
	}

	/**
	 * What this server calls itself: TrackShip, titled with the store's name.
	 *
	 * @return array
	 */
	protected function server_info() {
		return array(
			'name' => 'TrackShip',
			'title' => get_bloginfo( 'name' ) ? get_bloginfo( 'name' ) : __( 'Your store', 'trackship-for-woocommerce' ),
			'version' => trackship_for_woocommerce()->version,
		);
	}

	/**
	 * The tools on offer for the scope being served.
	 *
	 * @return array
	 */
	protected function tools() {
		return Trackship_MCP_Registry::instance()->tools();
	}

	/**
	 * Run one tool and wrap the outcome in an MCP tool result.
	 *
	 * A failure comes back as a tool error rather than a JSON-RPC error on purpose: the
	 * model should be able to read what went wrong, explain it, and try something else,
	 * which a transport-level error does not allow.
	 *
	 * @param string $name Tool name.
	 * @param array $args Tool arguments.
	 * @return array
	 */
	protected function call_tool( $name, $args ) {
		$result = Trackship_MCP_Registry::instance()->call( $name, $args );

		$this->log( $name, $args, $result );

		if ( is_wp_error( $result ) ) {
			return $this->tool_error( $result->get_error_message() );
		}

		return array(
			'content' => array(
				array(
					'type' => 'text',
					'text' => wp_json_encode( $result, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE ),
				),
			),
		);
	}

	/**
	 * Wrap a failure as an MCP tool error.
	 *
	 * @param string $message Human-readable message.
	 * @return array
	 */
	protected function tool_error( $message ) {
		return array(
			'content' => array(
				array(
					'type' => 'text',
					'text' => $message,
				),
			),
			'isError' => true,
		);
	}

	/*
	|--------------------------------------------------------------------------
	| Recording what happened
	|--------------------------------------------------------------------------
	*/

	/**
	 * Write the call to the audit log, and fire trackship_mcp_tool_called for anything else that wants to know.
	 *
	 * @param string $tool Tool name.
	 * @param array $args Tool arguments.
	 * @param array|WP_Error $result Tool outcome.
	 * @return void
	 */
	public function log( $tool, $args, $result ) {
		Trackship_MCP_Audit::record( $tool, 'trackship-link', $result, $this->client_identity() );

		/**
		 * Fires after every tool call, for plugins that keep a log of their own.
		 *
		 * @since 1.0.0
		 *
		 * @param string $tool Tool name.
		 * @param array $args Tool arguments.
		 * @param array|WP_Error $result Tool outcome.
		 */
		do_action( 'trackship_mcp_tool_called', $tool, $args, $result );
	}

	/**
	 * Which AI app is making this call.
	 *
	 * The link says which door the call came through; a merchant reading the log wants the
	 * other half -- was that me in VS Code, or something running unattended?
	 *
	 * MCP carries the answer in `initialize`, as clientInfo.name. The awkwardness is that
	 * this server is stateless by design: initialize and the tool call that follows are
	 * separate HTTP requests with nothing tying them together. So the name is remembered
	 * from initialize in a short-lived transient, keyed by the caller rather than by a
	 * session id we do not have.
	 *
	 * All of this is best-effort and the log must never imply otherwise. The key cannot be
	 * the token alone -- two apps on one connection would overwrite each other -- so it
	 * mixes in the user agent and address, which is enough to keep VS Code and a phone
	 * apart and not enough to be certain. With nothing remembered the user agent is shown
	 * as-is, and with no user agent either the column stays empty. A blank cell is a
	 * smaller lie than a confident wrong name.
	 *
	 * @return string
	 */
	protected function client_identity() {
		if ( '' !== $this->client ) {
			return $this->client;
		}

		$remembered = get_transient( $this->client_key() );

		if ( is_string( $remembered ) && '' !== $remembered ) {
			$this->client = $remembered;
			return $this->client;
		}

		$this->client = $this->user_agent();

		return $this->client;
	}

	/**
	 * Remember the client name an initialize message announced.
	 *
	 * @param array $params Params of the initialize message.
	 * @return void
	 */
	protected function remember_client( $params ) {
		$info = isset( $params['clientInfo'] ) && is_array( $params['clientInfo'] ) ? $params['clientInfo'] : array();
		$name = isset( $info['name'] ) ? trim( wp_strip_all_tags( (string) $info['name'] ) ) : '';

		if ( '' === $name ) {
			return;
		}

		$this->client = substr( $name, 0, 64 );

		set_transient( $this->client_key(), $this->client, DAY_IN_SECONDS );
	}

	/**
	 * Transient key for the caller of this request.
	 *
	 * @return string
	 */
	protected function client_key() {
		// phpcs:disable WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- each value is passed through sanitize_text_field below.
		$request = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
		$address = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '';
		// phpcs:enable WordPress.Security.ValidatedSanitizedInput.InputNotValidated

		return 'trackship_mcp_client_' . md5( $request . '|' . $this->user_agent() . '|' . $address );
	}

	/**
	 * The calling app's user agent, trimmed to something a person can read.
	 *
	 * @return string
	 */
	protected function user_agent() {
		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated -- sanitised on the same line.
		$agent = isset( $_SERVER['HTTP_USER_AGENT'] ) ? sanitize_text_field( wp_unslash( $_SERVER['HTTP_USER_AGENT'] ) ) : '';

		return substr( trim( $agent ), 0, 64 );
	}
}

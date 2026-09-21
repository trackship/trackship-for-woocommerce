<?php
/**
 * TrackShip MCP - view helpers for the AI Assistant screen.
 *
 * The status rows, client instructions, steps, disclosures, tool list and audit log shown on
 * the AI Assistant tab. includes/mcp/screen.php arranges them.
 *
 * The screen's whole job is telling the merchant the truth about what an AI can currently
 * do with their store, so every status is MEASURED, never assumed.
 *
 * @package TrackShip for WooCommerce
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'trackship_mcp_woo_mcp_active' ) ) {
	/**
	 * Whether WooCommerce's own MCP feature is switched on for this store.
	 *
	 * @return bool
	 */
	function trackship_mcp_woo_mcp_active() {
		// Asked directly: whether WooCommerce's own MCP feature is on is a fact about the store.
		if ( ! function_exists( 'wp_register_ability' ) ) {
			return false;
		}

		if ( class_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil' )
			&& method_exists( '\\Automattic\\WooCommerce\\Utilities\\FeaturesUtil', 'feature_is_enabled' ) ) {
			return (bool) \Automattic\WooCommerce\Utilities\FeaturesUtil::feature_is_enabled( 'mcp_integration' );
		}

		return 'yes' === get_option( 'woocommerce_feature_mcp_integration_enabled' );
	}
}

if ( ! function_exists( 'trackship_mcp_own_connection_active' ) ) {
	/**
	 * Whether TrackShip's own MCP endpoint is switched on AND has issued a URL.
	 *
	 * @return bool
	 */
	function trackship_mcp_own_connection_active() {
		return class_exists( 'Trackship_MCP_Server' )
			&& Trackship_MCP_Settings::enabled()
			&& '' !== Trackship_MCP_Server::endpoint_url();
	}
}

if ( ! function_exists( 'trackship_mcp_writes_allowed' ) ) {
	/**
	 * Whether the AI may change tracking through TrackShip's own connection.
	 *
	 * @return bool
	 */
	function trackship_mcp_writes_allowed() {
		return class_exists( 'Trackship_MCP_Settings' ) && Trackship_MCP_Settings::writes_enabled();
	}
}

if ( ! function_exists( 'trackship_mcp_endpoint_field_id' ) ) {
	/**
	 * The id of the input holding the connection URL.
	 *
	 * One source of truth because two places need to agree on it and they are far apart: the
	 * row that renders the input, and every per-client Copy button, which reaches the input by
	 * selector. They drifted once, when the package was renamed and the row moved to the new
	 * id while the buttons kept asking for the old one. The symptom was silent: a copy button
	 * that finds no field simply does nothing at all.
	 *
	 * @return string
	 */
	function trackship_mcp_endpoint_field_id() {
		return 'trackship-mcp-endpoint';
	}
}

if ( ! function_exists( 'trackship_mcp_registered_tools' ) ) {
	/**
	 * TrackShip's tools, as the AI Assistant screen lists them.
	 *
	 * Read from the registry, which is the same array the connection answers with -- so a tool
	 * listed here is a tool that exists, and one withheld (because the merchant set the link
	 * read-only) is absent from both.
	 *
	 * The "TrackShip › " prefix is stripped back off the title: on the connection it tells an AI
	 * client which connector a tool belongs to; on this screen everything is already TrackShip's.
	 *
	 * @return array List of array( 'name', 'label', 'description', 'writes', 'destructive' ).
	 */
	function trackship_mcp_registered_tools() {
		if ( ! class_exists( 'Trackship_MCP_Registry' ) ) {
			return array();
		}

		$prefix = Trackship_MCP_Registry::TITLE_PREFIX;
		$out = array();

		foreach ( Trackship_MCP_Registry::instance()->tools() as $name => $tool ) {
			$label = $tool['title'];

			if ( 0 === strpos( $label, $prefix ) ) {
				$label = substr( $label, strlen( $prefix ) );
			}

			$out[] = array(
				'name' => $name,
				'label' => $label,
				'description' => $tool['description'],
				'writes' => empty( $tool['annotations']['readOnlyHint'] ),
				'destructive' => ! empty( $tool['annotations']['destructiveHint'] ),
			);
		}

		return $out;
	}
}

if ( ! function_exists( 'trackship_mcp_client_targets' ) ) {
	/**
	 * The AI clients a connection can be handed to, and how each one takes it.
	 *
	 * Copying a URL is the easy half; a merchant who has never set up an MCP server is
	 * stuck on the other half -- where does this go? So each client gets a row, and each
	 * row does as much of the work as that client actually allows:
	 *
	 * 'link' A deeplink the client itself handles: one click and the server is added.
	 * Only Cursor and VS Code publish one. Claude has no such scheme, so no
	 * button is offered there -- a button that silently does nothing is worse
	 * than an instruction that works.
	 * 'command' Ready text (a terminal command, or a config block), with a Copy button.
	 * 'paste' Nothing to automate. The client wants the URL in its own connector UI,
	 * so the row says exactly which screen.
	 * 'none' That client genuinely cannot reach this route. Say so plainly and point
	 * at the one that works.
	 *
	 * The two routes are NOT interchangeable, which is why one function serves both: the
	 * differences are all in this table, where they can be seen side by side.
	 *
	 * TrackShip ($key empty) A plain address the app signs in to, so anything that accepts a URL
	 * works -- including claude.ai and Claude Desktop's connector
	 * UI, neither of which can send a custom header.
	 * WooCommerce ($key) The key must travel as an X-MCP-API-Key header. Claude's
	 * connector UI has nowhere to put one, so Claude Desktop needs
	 * a config file and claude.ai cannot do it at all.
	 *
	 * Every value here embeds a secret -- the token or the key -- so all of it is built
	 * only for a user already allowed to see it.
	 *
	 * @param string $url The connection URL.
	 * @param string $key Optional WooCommerce key as "ck_...:cs_...". Empty for the shared link.
	 * @return array List of array( 'name', 'note', 'action', ... ).
	 */
	function trackship_mcp_client_targets( $url, $key = '' ) {
		if ( '' === $url ) {
			return array();
		}

		$woo = ( '' !== $key );

		/*
		 * The server name is what a client files the connection under, so the two routes on
		 * a store must not share one: installing the second would quietly overwrite the
		 * first. The WooCommerce route keeps WooCommerce's name because that is genuinely
		 * whose server it is; TrackShip only contributes tools to it.
		 */
		$name = $woo ? 'woocommerce' : 'trackship';

		$cursor_config = array( 'url' => $url );

		if ( $woo ) {
			$cursor_config['headers'] = array( 'X-MCP-API-Key' => $key );
		}

		// Cursor takes the name separately and the rest as base64 JSON. The base64 is
		// URL-encoded on top: raw base64 can contain '+', which a query parser reads as
		// a space and would hand Cursor a broken URL.
		$cursor = 'cursor://anysphere.cursor-deeplink/mcp/install?name=' . rawurlencode( $name )
			. '&config=' . rawurlencode( base64_encode( (string) wp_json_encode( $cursor_config ) ) );

		if ( $woo ) {
			$targets = array(
				array(
					'name' => __( 'claude.ai', 'trackship-for-woocommerce' ),
					'note' => __( 'Cannot send an API key header. Use your own link for claude.ai and for phones.', 'trackship-for-woocommerce' ),
					'action' => 'none',
					'match' => array( 'claude-ai', 'claude.ai' ),
				),
				array(
					'name' => __( 'ChatGPT', 'trackship-for-woocommerce' ),
					'note' => __( 'Cannot send an API key header either. Use your own link for ChatGPT.', 'trackship-for-woocommerce' ),
					'action' => 'none',
					'match' => array( 'chatgpt', 'openai' ),
				),
				/*
				 * One Claude Desktop row, not two.
				 *
				 * There was a second, offering a prompt to paste into Claude Desktop so it
				 * would edit its own settings file. It reads well and it does not work:
				 * claude_desktop_config.json sits in a protected folder that Claude Desktop
				 * will not grant itself access to, so the attempt ends in a folder picker,
				 * a refusal, and a merchant who now believes the connection is broken.
				 *
				 * The file edit is the only route that works, so it is the only one offered,
				 * with the whole path to it rather than "by hand" as an apology.
				 */
				array(
					'name' => __( 'Claude Desktop', 'trackship-for-woocommerce' ),
					'note' => __( 'In Claude Desktop: Settings > Developer > Edit Config. Paste this inside "mcpServers", save, and restart Claude Desktop.', 'trackship-for-woocommerce' ),
					'action' => 'command',
					'label' => __( 'Copy config', 'trackship-for-woocommerce' ),
					'command' => trackship_mcp_desktop_config_block( $name, $url, $key ),
					'match' => array( 'claude desktop', 'claude-desktop' ),
				),
			);
		} else {
			$targets = array(
				array(
					'name' => __( 'claude.ai', 'trackship-for-woocommerce' ),
					'note' => __( 'Settings > Connectors > Add custom connector, then paste the URL.', 'trackship-for-woocommerce' ),
					'action' => 'paste',
					'match' => array( 'claude-ai', 'claude.ai' ),
				),
				array(
					'name' => __( 'ChatGPT', 'trackship-for-woocommerce' ),
					'note' => __( 'Turn on Developer mode in the ChatGPT settings first, then add the URL as a connector. OpenAI still ships this as a developer feature, so it is not on every plan yet.', 'trackship-for-woocommerce' ),
					'action' => 'paste',
					'match' => array( 'chatgpt', 'openai' ),
				),
				array(
					'name' => __( 'Claude Desktop', 'trackship-for-woocommerce' ),
					'note' => __( 'Settings > Connectors > Add custom connector, then paste the URL. No config file to edit.', 'trackship-for-woocommerce' ),
					'action' => 'paste',
					'match' => array( 'claude desktop', 'claude-desktop' ),
				),
			);
		}

		/*
		 * NO CLAUDE CODE ROW.
		 *
		 * It worked -- one command pasted into a terminal and the connection was made -- and
		 * it was on the wrong screen. This screen belongs to a shop owner: somebody who looks
		 * at orders, adds tracking, approves returns. "Run this once in your terminal" is not
		 * a sentence they can act on, and an instruction a reader cannot act on does not sit
		 * there harmlessly; it teaches them that parts of this screen are not for them, and
		 * that is a bad thing to teach on a screen you want them to trust.
		 *
		 * A developer who wants it does not need a button. They have the link, and
		 * `claude mcp add --transport http <name> "<url>"` is the whole of it.
		 */
		$targets[] = array(
			'name' => __( 'Cursor', 'trackship-for-woocommerce' ),
			'note' => __( 'Opens Cursor and adds the server.', 'trackship-for-woocommerce' ),
			'action' => 'link',
			'label' => __( 'Add to Cursor', 'trackship-for-woocommerce' ),
			'link' => $cursor,
			'match' => array( 'cursor' ),
		);

		/**
		 * Filter the AI clients offered on the connection screen.
		 *
		 * @since 5.0.4
		 *
		 * @param array $targets Client rows.
		 * @param string $url The connection URL.
		 * @param string $key WooCommerce key, or '' for the TrackShip route.
		 */
		return apply_filters( 'trackship_mcp_client_targets', $targets, $url, $key );
	}
}

if ( ! function_exists( 'trackship_mcp_desktop_config_block' ) ) {
	/**
	 * The claude_desktop_config.json entry for a header-authenticated server.
	 *
	 * Claude Desktop launches local processes over stdio and has no field for a header,
	 * so mcp-remote bridges the remote HTTP server into that transport. The key travels
	 * through mcp-remote's environment rather than inline in --header because mcp-remote
	 * splits that argument on the first colon, and a WooCommerce key is itself
	 * "ck_...:cs_..." -- inlined, the split would tear it in half.
	 *
	 * Returned as the inner property only, since it is pasted into an mcpServers object
	 * the merchant already has open.
	 *
	 * @param string $name Server name.
	 * @param string $url Endpoint URL.
	 * @param string $key WooCommerce key as "ck_...:cs_...".
	 * @return string Pretty-printed JSON fragment.
	 */
	function trackship_mcp_desktop_config_block( $name, $url, $key ) {
		$block = (string) wp_json_encode(
			array(
				$name => array(
					'command' => 'npx',
					'args' => array( '-y', 'mcp-remote', $url, '--header', 'X-MCP-API-Key:${MCP_KEY}' ),
					'env' => array( 'MCP_KEY' => $key ),
				),
			),
			JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES
		);

		// Drop the wrapping braces: what is wanted is the property, not a whole object.
		$block = trim( (string) preg_replace( '/^\{\s*|\s*\}$/', '', trim( $block ) ) );

		// json_encode indented every line for a wrapper that is now gone, so pull the body
		// back one level. Without this the block lands in the merchant's file a step to the
		// right of the servers either side of it.
		return (string) preg_replace( '/^    /m', '', $block );
	}
}

if ( ! function_exists( 'trackship_mcp_client_seen' ) ) {
	/**
	 * When this client last ran something, if it ever has.
	 *
	 * This is a web server: what sits in a config file on somebody's laptop is not
	 * knowable from here. What IS knowable is which apps have actually called, because
	 * every call is recorded with the name the app announced in its MCP handshake. So the
	 * row reports use, not configuration -- and "it has worked" is the more useful thing
	 * to tell a merchant anyway.
	 *
	 * Matching is by substring, because those names belong to the apps and are not
	 * consistent: VS Code says "Visual Studio Code", Claude Code says "claude-code". An app
	 * nobody recognises still appears in the audit log under whatever it called itself; it
	 * simply does not light up a row here. That is the right way round -- a row claiming a
	 * connection that is not there would send someone hunting for a fault in the wrong
	 * place.
	 *
	 * @param array $target One entry from trackship_mcp_client_targets().
	 * @return int|null UTC timestamp, or null if this app has never called.
	 */
	function trackship_mcp_client_seen( $target ) {
		if ( empty( $target['match'] ) || ! class_exists( 'Trackship_MCP_Audit' ) ) {
			return null;
		}

		$latest = null;

		foreach ( Trackship_MCP_Audit::clients_seen( 'trackship-link' ) as $client => $when ) {
			foreach ( (array) $target['match'] as $needle ) {
				if ( false !== strpos( $client, $needle ) && ( null === $latest || $when > $latest ) ) {
					$latest = $when;
				}
			}
		}

		return $latest;
	}
}

if ( ! function_exists( 'trackship_mcp_client_row' ) ) {
	/**
	 * Echo one client row: name, what to do, and the control that does it.
	 *
	 * @param array $target One entry from trackship_mcp_client_targets().
	 * @return void
	 */
	function trackship_mcp_client_row( $target ) {
		?>
		<?php $seen = trackship_mcp_client_seen( $target ); ?>
		<div class="trackship-mcp-client">
			<div class="trackship-mcp-client__head">
				<span class="trackship-mcp-client__name">
					<?php echo esc_html( $target['name'] ); ?>
					<?php if ( $seen ) : ?>
						<span class="trackship-mcp-badge trackship-mcp-badge--ok"><?php esc_html_e( 'In use', 'trackship-for-woocommerce' ); ?></span>
					<?php endif; ?>
				</span>
				<p class="trackship-mcp-client__note">
					<?php if ( $seen ) : ?>
						<?php
						printf(
							/* translators: %s: how long ago this app last ran a tool, e.g. "5 mins". */
							esc_html__( 'Connected and working — last used %s ago.', 'trackship-for-woocommerce' ),
							esc_html( human_time_diff( $seen, time() ) )
						);
						?>
					<?php else : ?>
						<?php echo esc_html( $target['note'] ); ?>
					<?php endif; ?>
				</p>
			</div>
			<div class="trackship-mcp-client__control">
				<?php if ( 'link' === $target['action'] ) : ?>
					<?php
					/*
					 * 'cursor' and 'vscode' are not in wp_allowed_protocols(), so esc_url()
					 * would return an empty string for these without being told about them.
					 */
					?>
					<?php
					/*
					 * Reset sits beside Add, because that is where a merchant is standing when
					 * they need it: they have pressed Add, the app has done nothing visible,
					 * and pressing Add again does nothing visible either.
					 *
					 * It cannot reach inside the app -- there is no uninstall deeplink for
					 * either editor, and this page has no business in their settings anyway.
					 * What it can do is withdraw the token the app is holding, which is the
					 * actual reason the app is not asking to sign in. With the token gone the
					 * next call is refused, and the app has to start over.
					 *
					 * Shown whether or not anything is currently connected. It was hidden
					 * when nothing was -- which sounds reasonable and was exactly backwards:
					 * a merchant who has just signed everyone out is the likeliest person to
					 * need it, and they found the button gone. Turning off nothing costs
					 * nothing, and reopening the app is half the point of pressing it.
					 */
					if ( ! empty( $target['match'] ) ) :
						?>
						<button type="button" class="trackship-mcp-reset" data-trackship-mcp-reset="<?php echo esc_attr( implode( '|', (array) $target['match'] ) ); ?>"
							data-label-working="<?php esc_attr_e( 'Resetting…', 'trackship-for-woocommerce' ); ?>"
							data-trackship-mcp-confirm="<?php
								/* translators: %s: the AI app's name, e.g. Cursor. */
								echo esc_attr( sprintf( __( 'Make %s sign in again? Its connection is turned off here, so the next time it calls it has to ask you to sign in. Nothing else is affected.', 'trackship-for-woocommerce' ), $target['name'] ) );
							?>">
							<?php esc_html_e( 'Reset', 'trackship-for-woocommerce' ); ?>
						</button>
						<?php
					endif;
					?>
					<a class="button button-trackship" href="<?php echo esc_url( $target['link'], array( 'cursor', 'vscode' ) ); ?>"
						data-trackship-mcp-open
						data-label-opening="<?php
							/* translators: %s: the AI app's name, e.g. Cursor. */
							echo esc_attr( sprintf( __( 'Opening %s…', 'trackship-for-woocommerce' ), $target['name'] ) );
						?>"
						data-label-missing="<?php
							/* translators: %s: the AI app's name, e.g. Cursor. */
							echo esc_attr( sprintf( __( 'Is %s installed?', 'trackship-for-woocommerce' ), $target['name'] ) );
						?>">
						<?php echo esc_html( $target['label'] ); ?>
					</a>
				<?php elseif ( 'command' === $target['action'] ) : ?>
					<button type="button" class="button button-trackship"
						data-trackship-mcp-copy-text="<?php echo esc_attr( $target['command'] ); ?>"
						data-label-copied="<?php esc_attr_e( 'Copied', 'trackship-for-woocommerce' ); ?>"
						data-label-copyfailed="<?php esc_attr_e( 'Copy failed — press Ctrl+C', 'trackship-for-woocommerce' ); ?>">
						<?php echo esc_html( $target['label'] ); ?>
					</button>
				<?php elseif ( 'none' === $target['action'] ) : ?>
					<span class="trackship-mcp-client__unavailable"><?php esc_html_e( 'Not supported', 'trackship-for-woocommerce' ); ?></span>
				<?php else : ?>
					<button type="button" class="button button-trackship"
						data-trackship-mcp-copy="#<?php echo esc_attr( trackship_mcp_endpoint_field_id() ); ?>"
						data-label-copied="<?php esc_attr_e( 'Copied', 'trackship-for-woocommerce' ); ?>"
						data-label-copyfailed="<?php esc_attr_e( 'Copy failed — press Ctrl+C', 'trackship-for-woocommerce' ); ?>">
						<?php esc_html_e( 'Copy URL', 'trackship-for-woocommerce' ); ?>
					</button>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_enqueue_assets' ) ) {
	/**
	 * Load the connection screen's stylesheet and script.
	 *
	 * Called from WC_Trackship_Actions on TrackShip's settings page, from admin_enqueue_scripts.
	 * Versioned by the file's own timestamp, so a merchant never has to hard-refresh to get a
	 * fixed screen.
	 *
	 * @return void
	 */
	function trackship_mcp_enqueue_assets() {
		$base = __DIR__ . '/assets/';
		$url = plugins_url( 'assets/', __FILE__ );

		$css = $base . 'trackship-mcp.css';
		$js = $base . 'trackship-mcp.js';

		if ( file_exists( $css ) ) {
			wp_enqueue_style( 'trackship-mcp', $url . 'trackship-mcp.css', array(), (string) filemtime( $css ) );
		}

		if ( file_exists( $js ) ) {
			wp_enqueue_script( 'trackship-mcp', $url . 'trackship-mcp.js', array( 'jquery' ), (string) filemtime( $js ), true );
		}
	}
}

if ( ! function_exists( 'trackship_mcp_tool_icon' ) ) {
	/**
	 * The glyph for one power, as inline SVG.
	 *
	 * Decorative on purpose -- aria-hidden, because the word next to it already says the same
	 * thing, and a screen reader announcing "eye Read" is worse than one announcing "Read".
	 * What the icon is for is the case colour cannot cover: a printed page, a high-contrast
	 * mode, or a merchant who does not separate amber from red. Drawn in currentColor so it
	 * inherits whatever tint the chip carries and can never drift from it.
	 *
	 * @param string $kind 'read', 'write' or 'delete'.
	 * @return string Inline SVG markup. Built from a fixed map -- no caller input reaches it.
	 */
	function trackship_mcp_tool_icon( $kind ) {
		$paths = array(
			// Eye.
			'read' => '<path d="M1 8s2.5-4.5 7-4.5S15 8 15 8s-2.5 4.5-7 4.5S1 8 1 8Z"/><circle cx="8" cy="8" r="1.9"/>',
			// Pencil.
			'write' => '<path d="M11 2.4 13.6 5 5.6 13H3v-2.6Z" stroke-linejoin="round"/>',
			// Bin.
			'delete' => '<path d="M2.5 4h11M6 4V2.5h4V4M4 4l.7 9.5h6.6L12 4" stroke-linecap="round"/>',
		);

		if ( ! isset( $paths[ $kind ] ) ) {
			return '';
		}

		return '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.4" aria-hidden="true" focusable="false">' . $paths[ $kind ] . '</svg>';
	}
}

if ( ! function_exists( 'trackship_mcp_render_tools' ) ) {
	/**
	 * Echo the "what the AI can do" list.
	 *
	 * Each tool gets one badge -- Read, Write or Delete -- taken from the annotations the tools
	 * already advertise to the AI, so the merchant sees the same facts the model is told. A tool
	 * that adds tracking and one that deletes it are not the same risk, and a merchant deciding
	 * whether to leave writes switched on is deciding exactly between them.
	 *
	 * @param array $args {
	 * @type string $empty Shown when there are no tools.
	 * @type string $note Shown under the list.
	 * }
	 * @return void
	 */
	function trackship_mcp_render_tools( $args = array() ) {
		$args = array_merge(
			array(
				'empty' => __( 'No tools are available on this connection yet.', 'trackship-for-woocommerce' ),
				'note' => '',
			),
			is_array( $args ) ? $args : array()
		);

		$tools = trackship_mcp_registered_tools();
		?>
		<?php /* One tight line per tool. Not .trackship-mcp-row -- that carries 32px of padding meant for a form control, which turns a four-item list into a screenful. */ ?>
		<div class="trackship-mcp-tools">
			<?php if ( empty( $tools ) ) : ?>
				<p class="trackship-mcp-tools__note"><?php echo esc_html( $args['empty'] ); ?></p>
			<?php else : ?>
				<?php foreach ( $tools as $tool ) : ?>
					<?php
					if ( ! empty( $tool['destructive'] ) ) {
						$kind = 'delete';
						$word = __( 'Delete', 'trackship-for-woocommerce' );
					} elseif ( ! empty( $tool['writes'] ) ) {
						$kind = 'write';
						$word = __( 'Write', 'trackship-for-woocommerce' );
					} else {
						$kind = 'read';
						$word = __( 'Read', 'trackship-for-woocommerce' );
					}
					?>
					<div class="trackship-mcp-tools__item">
						<span class="trackship-mcp-tools__name"><?php echo esc_html( $tool['label'] ); ?></span>
						<span class="trackship-mcp-badge trackship-mcp-badge--tool trackship-mcp-badge--<?php echo esc_attr( $kind ); ?>"><?php echo trackship_mcp_tool_icon( $kind ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG markup from a local map, no input reaches it. ?><?php echo esc_html( $word ); ?></span>
					</div>
				<?php endforeach; ?>
				<?php if ( '' !== $args['note'] ) : ?>
					<p class="trackship-mcp-tools__note"><?php echo esc_html( $args['note'] ); ?></p>
				<?php endif; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_woo_key_hint' ) ) {
	/**
	 * The sentence above the key fields, saying whether one is stored.
	 *
	 * Built here rather than inline because two things write it: this screen on load, and the
	 * reply to entering or forgetting a key, which repaints it without a page load. Two copies
	 * of a sentence drift, and the drift shows as a screen that contradicts itself.
	 *
	 * @param array $record What record() returned. Empty for "no key stored".
	 * @return string Escaped, ready to place as text.
	 */
	function trackship_mcp_woo_key_hint( $record ) {
		if ( empty( $record['truncated'] ) ) {
			return __( 'Make one in WooCommerce, then paste both halves here. Copy them before you leave that screen — the secret is shown once and never again.', 'trackship-for-woocommerce' );
		}

		return sprintf(
			/* translators: 1: last characters of the key, 2: how long ago it was entered. */
			__( 'A key ending %1$s was entered here %2$s ago, and your setup steps are below. Enter another to replace it.', 'trackship-for-woocommerce' ),
			$record['truncated'],
			human_time_diff( (int) $record['created'], time() )
		);
	}
}

if ( ! function_exists( 'trackship_mcp_woo_key_last_used' ) ) {
	/**
	 * When this key was last used, from whichever source actually knows.
	 *
	 * WooCommerce keeps last_access on the key row, but it is written by its REST
	 * authentication and the MCP transport does not go through it — so on a key used only by
	 * assistants that column stays empty forever, and the screen reported "never" directly
	 * above a table of sessions active minutes ago. The sessions are the better witness; the
	 * key row is the fallback for a key that reaches WooCommerce some other way.
	 *
	 * @param array $details What key_details() returned.
	 * @return string
	 */
	function trackship_mcp_woo_key_last_used( $details ) {
		$newest = 0;

		if ( class_exists( 'Trackship_MCP_Woo_Key' ) && method_exists( 'Trackship_MCP_Woo_Key', 'sessions' ) ) {
			foreach ( Trackship_MCP_Woo_Key::sessions() as $session ) {
				$newest = max( $newest, (int) $session['last'] );
			}
		}

		if ( ! $newest && ! empty( $details['last_access'] ) ) {
			$newest = (int) strtotime( $details['last_access'] );
		}

		if ( ! $newest ) {
			return __( 'never — nothing has connected with it yet', 'trackship-for-woocommerce' );
		}

		/* translators: %s: how long ago, e.g. "2 hours". */
		return sprintf( __( '%s ago', 'trackship-for-woocommerce' ), human_time_diff( $newest, time() ) );
	}
}

if ( ! function_exists( 'trackship_mcp_render_woo_connections' ) ) {
	/**
	 * Echo who currently holds a session on WooCommerce's MCP route.
	 *
	 * Apps rather than people, and no Revoke beside them, because neither is available here.
	 * Every session is the one account the key acts as, and closing a session would achieve
	 * nothing: the app still holds the key and opens another on its next request. The only
	 * way to end access is to delete the key, which is WooCommerce's screen to offer — so
	 * this says that instead of a button that looks like it works.
	 *
	 * @return void
	 */
	function trackship_mcp_render_woo_connections() {
		$sessions = class_exists( 'Trackship_MCP_Woo_Key' ) && method_exists( 'Trackship_MCP_Woo_Key', 'sessions' )
			? Trackship_MCP_Woo_Key::sessions()
			: array();

		if ( empty( $sessions ) ) {
			?>
			<div class="trackship-mcp-tools">
				<p class="trackship-mcp-tools__note"><?php esc_html_e( 'Nothing is connected with this key yet. Set up an app with the steps above and it will appear here.', 'trackship-for-woocommerce' ); ?></p>
			</div>
			<?php
			return;
		}
		?>
		<?php
		$details = class_exists( 'Trackship_MCP_Woo_Key' ) ? Trackship_MCP_Woo_Key::key_details() : array();
		$acts_as = ! empty( $details['email'] ) ? $details['email'] : ( ! empty( $details['user'] ) ? $details['user'] : __( 'the key owner', 'trackship-for-woocommerce' ) );
		?>
		<?php /* The same shape as the shared link's connection list, which sits above it: a merchant comparing the two routes is comparing exactly these rows. Every row acts as the same account here, because that is all a WooCommerce key can be. */ ?>
		<div class="trackship-mcp-log">
		<table class="trackship-mcp-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Acting as', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'App', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Connected', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Last used', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Status', 'trackship-for-woocommerce' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $sessions as $session ) : ?>
					<?php
					/*
					 * No button on any row, for the same reason the shared link's rows have
					 * none: closing a session here would not take anything away. The app still
					 * holds the key and opens another session on its next request. Only
					 * deleting the key ends access, and that is WooCommerce's screen to offer.
					 */
					?>
					<tr>
						<td><?php echo esc_html( $acts_as ); ?></td>
						<?php
						/*
						 * The app, and nothing beside it.
						 *
						 * There was a session count here. An app opens a fresh session every
						 * time it reconnects -- a restart, a dropped network, mcp-remote's own
						 * retries -- so the number counted reconnections, which is machinery,
						 * not information: eight or two, there is nothing a merchant would do
						 * differently. What they came to read is on this row already.
						 */
						?>
						<td><?php echo esc_html( $session['app'] ); ?></td>
						<td><?php echo esc_html( $session['created'] ? date_i18n( 'Y-m-d', $session['created'] ) : '—' ); ?></td>
						<td>
							<?php
							echo esc_html(
								$session['last']
									/* translators: %s: how long ago, e.g. "2 hours". */
									? sprintf( __( '%s ago', 'trackship-for-woocommerce' ), human_time_diff( $session['last'], time() ) )
									: '—'
							);
							?>
						</td>
						<td>
							<span class="trackship-mcp-badge trackship-mcp-badge--ok"><?php esc_html_e( 'Active', 'trackship-for-woocommerce' ); ?></span>
						</td>
						<td></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>

		<?php
		/*
		 * One button, under the table rather than on any row.
		 *
		 * There is no per-app revoke to offer: every row holds the same key, and closing one
		 * session only sends that app back for another. What genuinely ends access is deleting
		 * the key, and that ends it for all of them at once — so the control sits where its
		 * scope is, and says what it does before it is pressed.
		 */
		?>
		<div class="trackship-mcp-connections__foot">
			<button type="button" class="trackship-mcp-revoke" data-trackship-mcp-deletekey
				data-trackship-mcp-confirm="<?php esc_attr_e( 'Delete this key in WooCommerce? Every app using it stops working immediately, and a new key has to be made before anything can connect again. If you gave this key to something other than an AI assistant, that stops too. This cannot be undone.', 'trackship-for-woocommerce' ); ?>"
				data-label-working="<?php esc_attr_e( 'Deleting…', 'trackship-for-woocommerce' ); ?>">
				<?php esc_html_e( 'Delete this key', 'trackship-for-woocommerce' ); ?>
			</button>
			<?php esc_html_e( 'They cannot be turned off one at a time — each is the same account, and an app that still has the key opens a new session on its next request. Deleting the key ends all of them, and a new one must be made to connect again.', 'trackship-for-woocommerce' ); ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_render_woo_key_details' ) ) {
	/**
	 * Echo what WooCommerce knows about the stored key.
	 *
	 * Not a "who is connected" table, because this route has no such thing: one key is one
	 * identity, and everyone holding it is that identity. What a merchant can act on is
	 * whether the key still exists, what it reaches, and whether anything has used it.
	 *
	 * @return void
	 */
	function trackship_mcp_render_woo_key_details() {
		$details = class_exists( 'Trackship_MCP_Woo_Key' ) && method_exists( 'Trackship_MCP_Woo_Key', 'key_details' )
			? Trackship_MCP_Woo_Key::key_details()
			: array();

		if ( empty( $details ) ) {
			return;
		}

		if ( empty( $details['found'] ) ) {
			?>
			<div class="trackship-mcp-tools">
				<p class="trackship-mcp-tools__note"><?php esc_html_e( 'This key no longer exists in WooCommerce — it has been deleted there, so nothing can connect with it. Make a new one and paste it above.', 'trackship-for-woocommerce' ); ?></p>
			</div>
			<?php
			return;
		}

		$access = array(
			'read' => __( 'Read only', 'trackship-for-woocommerce' ),
			'write' => __( 'Write only', 'trackship-for-woocommerce' ),
			'read_write' => __( 'Read and write', 'trackship-for-woocommerce' ),
		);

		$rows = array(
			__( 'Acting as', 'trackship-for-woocommerce' ) => '' !== $details['user'] ? $details['user'] : __( 'an account that no longer exists', 'trackship-for-woocommerce' ),
			__( 'Named', 'trackship-for-woocommerce' ) => '' !== $details['description'] ? $details['description'] : __( 'no description', 'trackship-for-woocommerce' ),
			__( 'Access', 'trackship-for-woocommerce' ) => isset( $access[ $details['permissions'] ] ) ? $access[ $details['permissions'] ] : $details['permissions'],
			__( 'Last used', 'trackship-for-woocommerce' ) => trackship_mcp_woo_key_last_used( $details ),
		);
		?>
		<div class="trackship-mcp-tools">
			<?php foreach ( $rows as $label => $value ) : ?>
				<div class="trackship-mcp-tools__item">
					<span class="trackship-mcp-tools__name"><?php echo esc_html( $label ); ?></span>
					<span><?php echo esc_html( $value ); ?></span>
				</div>
			<?php endforeach; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_woo_mcp_tools' ) ) {
	/**
	 * The tools WooCommerce's own MCP server hands an assistant.
	 *
	 * Read live rather than listed here, because the answer is per store: it changes with the
	 * WooCommerce version and with whatever else on the site registers an ability. A list
	 * written into this file would be a claim about somebody else's plugin that nothing would
	 * ever check, and merchants would find out it was stale by being surprised.
	 *
	 * WooCommerce does not expose every registered ability on that server. It keeps the ones
	 * whose meta carries `expose_in_deprecated_woocommerce_mcp`, which is the same key TrackShip
	 * sets to get its own tools listed there — so this returns both, which is the
	 * honest answer to "what can an assistant do through this route".
	 *
	 * @return array List of tools, each with label, whether it writes, and whether it deletes.
	 */
	function trackship_mcp_woo_mcp_tools() {
		if ( ! function_exists( 'wp_get_abilities' ) ) {
			return array();
		}

		$out = array();

		foreach ( (array) wp_get_abilities() as $ability ) {
			if ( ! is_object( $ability ) || ! method_exists( $ability, 'get_meta' ) ) {
				continue;
			}

			$meta = (array) $ability->get_meta();

			if ( empty( $meta['expose_in_deprecated_woocommerce_mcp'] ) ) {
				continue;
			}

			$notes = isset( $meta['annotations'] ) ? (array) $meta['annotations'] : array();
			$label = method_exists( $ability, 'get_label' ) ? (string) $ability->get_label() : '';
			$name = method_exists( $ability, 'get_name' ) ? (string) $ability->get_name() : '';

			// Both spellings: the MCP specification says readOnlyHint and destructiveHint, and
			// an ability's meta says readonly and destructive. Whichever an ability carries.
			$out[] = array(
				'name' => $name,
				'label' => '' !== $label ? $label : $name,
				'writes' => empty( $notes['readonly'] ) && empty( $notes['readOnlyHint'] ),
				'destructive' => ! empty( $notes['destructive'] ) || ! empty( $notes['destructiveHint'] ),
			);
		}

		usort(
			$out,
			function ( $a, $b ) {
				return strcasecmp( $a['label'], $b['label'] );
			}
		);

		return $out;
	}
}

if ( ! function_exists( 'trackship_mcp_render_woo_tools' ) ) {
	/**
	 * Echo the list of tools reachable over WooCommerce's MCP route.
	 *
	 * Deliberately the same tight row as the connection screen's own tool list, badge and all,
	 * because a merchant comparing the two routes is comparing exactly this: what each one
	 * lets an assistant do.
	 *
	 * @param string $empty Sentence shown when the route exposes nothing.
	 * @return void
	 */
	function trackship_mcp_render_woo_tools( $empty = '' ) {
		// Asked of the server when a key is stored, because WooCommerce's own tools exist only
		// during an MCP request and cannot be read from an admin screen. Without a key there
		// is nothing to ask with, so fall back to the tools this plugin registers — those are
		// on the route either way — and say what is missing.
		$answer = class_exists( 'Trackship_MCP_Woo_Key' ) && method_exists( 'Trackship_MCP_Woo_Key', 'server_tools' )
			? Trackship_MCP_Woo_Key::server_tools()
			: array( 'ok' => false, 'tools' => array(), 'error' => '' );

		$tools = ! empty( $answer['ok'] ) ? $answer['tools'] : trackship_mcp_woo_mcp_tools();
		$note = ! empty( $answer['ok'] ) ? '' : (string) $answer['error'];

		if ( '' === $empty ) {
			$empty = __( 'WooCommerce is not exposing any tools on this route yet.', 'trackship-for-woocommerce' );
		}
		?>
		<div class="trackship-mcp-tools">
			<?php if ( '' !== $note ) : ?>
				<p class="trackship-mcp-tools__note"><?php echo esc_html( $note ); ?></p>
			<?php endif; ?>

			<?php if ( empty( $tools ) ) : ?>
				<p class="trackship-mcp-tools__note"><?php echo esc_html( $empty ); ?></p>
			<?php else : ?>
				<?php foreach ( $tools as $tool ) : ?>
					<?php
					if ( ! empty( $tool['destructive'] ) ) {
						$kind = 'delete';
						$word = __( 'Delete', 'trackship-for-woocommerce' );
					} elseif ( ! empty( $tool['writes'] ) ) {
						$kind = 'write';
						$word = __( 'Write', 'trackship-for-woocommerce' );
					} else {
						$kind = 'read';
						$word = __( 'Read', 'trackship-for-woocommerce' );
					}
					?>
					<div class="trackship-mcp-tools__item">
						<span class="trackship-mcp-tools__name"><?php echo esc_html( $tool['label'] ); ?></span>
						<span class="trackship-mcp-badge trackship-mcp-badge--tool trackship-mcp-badge--<?php echo esc_attr( $kind ); ?>"><?php echo trackship_mcp_tool_icon( $kind ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG markup from a local map, no input reaches it. ?><?php echo esc_html( $word ); ?></span>
					</div>
				<?php endforeach; ?>
			<?php endif; ?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_render_woo_mcp' ) ) {
	/**
	 * Echo the WooCommerce MCP screen: its status, the way in, and the key it needs.
	 *
	 * WooCommerce ships an MCP server of its own, and TrackShip's tools appear on it the moment
	 * the plugin is active -- there is nothing to switch on for that. What a merchant needs here
	 * is the REST API key that route authenticates with (made by Trackship_MCP_Woo_Key), and an
	 * honest account of what that key opens.
	 *
	 * Kept apart from the shared link on purpose. The two routes look similar and are not:
	 * one is a link, the other a key in a header; one reaches a plugin's tools, the other the
	 * whole store. Shown together they blur, and a merchant who blurs them is a merchant who
	 * pastes a store-wide credential somewhere it should not go.
	 *
	 * @param array $args {
	 * @type string $status_note Shown only when WooCommerce MCP is switched off.
	 * @type string $key_sub Under the Connection key heading.
	 * @type string $scope_note What a WooCommerce key opens, compared with the link.
	 * }
	 * @return void
	 */
	function trackship_mcp_render_woo_mcp( $args = array() ) {
		$args = array_merge(
			array(
				'status_note' => __( 'WooCommerce MCP is switched off in WooCommerce, so this route is not answering at all. Your own link is unaffected.', 'trackship-for-woocommerce' ),
				'key_sub' => __( 'Only needed if you connect through WooCommerce MCP instead of your own link.', 'trackship-for-woocommerce' ),
				'scope_note' => __( 'A WooCommerce key opens the whole store — orders, products, customers — because the key belongs to WooCommerce and cannot be narrowed. Your own link reaches only what this plugin adds.', 'trackship-for-woocommerce' ),
				'tools_sub' => __( 'Everything an assistant can reach through WooCommerce MCP — WooCommerce\'s own tools, plus the ones this plugin adds.', 'trackship-for-woocommerce' ),
				'tools_empty' => __( 'WooCommerce is not exposing any tools on this route yet.', 'trackship-for-woocommerce' ),
				'about_sub' => __( 'Everyone who uses this key is this one account — the route has no way to tell them apart. Your own link above does, and can turn one person off.', 'trackship-for-woocommerce' ),
				'conns_sub' => __( 'Every app connected with this key. They all act as the same account, because that is all a WooCommerce key can be.', 'trackship-for-woocommerce' ),
			),
			is_array( $args ) ? $args : array()
		);

		$woo_on = trackship_mcp_woo_mcp_active();

		// Read once, up here, because four separate blocks below decide what to show from it
		// -- the confirm on the key button, the setup rows, the Forget row, and the two cards
		// after them. It was worked out in the middle of that run, so everything above the
		// line that set it read an undefined variable.
		$woo_pair = class_exists( 'Trackship_MCP_Woo_Key' ) && method_exists( 'Trackship_MCP_Woo_Key', 'pair' )
			? Trackship_MCP_Woo_Key::pair()
			: '';
		?>

		<?php
		/*
		 * No status card.
		 *
		 * There was one, reporting "READY" on WooCommerce's route, and it was a badge for a
		 * thing the merchant is not using and cannot act on: ready means WooCommerce has the
		 * feature switched on, which says nothing about whether anything works -- that route
		 * needs a key, and without one it answers nobody. A green word about somebody else's
		 * endpoint, on a screen about a key, is a claim the merchant has to learn to ignore.
		 *
		 * What is left is what they came for: the key, and what it opens.
		 */
		?>

		<?php if ( ! $woo_on ) : ?>
			<div class="trackship-mcp-card">
				<div class="trackship-mcp-row">
					<div class="trackship-mcp-row__head">
						<p class="trackship-mcp-row__hint"><?php echo esc_html( $args['status_note'] ); ?></p>
						<p class="trackship-mcp-row__hint">
							<a class="ptw_a" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=features' ) ); ?>">
								<?php esc_html_e( 'Open WooCommerce feature settings', 'trackship-for-woocommerce' ); ?>
							</a>
						</p>
					</div>
				</div>
			</div>
		<?php endif; ?>

		<div class="trackship-mcp-card">
			<div class="trackship-mcp-card__head">
				<h3 class="trackship-mcp-card__title"><?php esc_html_e( 'Connection key', 'trackship-for-woocommerce' ); ?></h3>
				<p class="trackship-mcp-card__sub"><?php echo esc_html( $args['key_sub'] ); ?></p>
			</div>

			<?php if ( $woo_on && class_exists( 'Trackship_MCP_Woo_Key' ) && Trackship_MCP_Woo_Key::available() ) : ?>
				<?php $woo_key = Trackship_MCP_Woo_Key::record(); ?>

				<?php
				/*
				 * The merchant makes the key; this takes it.
				 *
				 * There was a button here that created one. It worked, and it was the wrong
				 * thing to offer: a WooCommerce key opens orders, products and customers, and
				 * minting one on somebody's behalf from a shipment-tracking screen gives them
				 * a store-wide credential they never quite decided to create. WooCommerce's
				 * own screen asks who it is for and what it may do, and those questions
				 * deserve to be answered there.
				 *
				 * What is genuinely hard is the part after: turning two long strings into the
				 * shape each AI client wants. That is what this does.
				 */
				?>
				<div class="trackship-mcp-row">
					<div class="trackship-mcp-row__head">
						<span class="trackship-mcp-row__label"><?php esc_html_e( 'Your WooCommerce key', 'trackship-for-woocommerce' ); ?></span>
						<p class="trackship-mcp-row__hint" data-trackship-mcp-woo-hint><?php echo esc_html( trackship_mcp_woo_key_hint( $woo_key ) ); ?></p>
						<p class="trackship-mcp-row__hint">
							<a class="ptw_a" href="<?php echo esc_url( admin_url( 'admin.php?page=wc-settings&tab=advanced&section=keys&create-key=1' ) ); ?>" target="_blank" rel="noopener">
								<?php esc_html_e( 'Create a key in WooCommerce', 'trackship-for-woocommerce' ); ?>
							</a>
						</p>
						<p class="trackship-mcp-row__hint" data-trackship-mcp-keyerror hidden></p>
					</div>
					<div class="trackship-mcp-row__control trackship-mcp-keyform">
						<input type="text" class="trackship-mcp-input" id="trackship-mcp-ck" placeholder="ck_…" autocomplete="off" spellcheck="false" aria-label="<?php esc_attr_e( 'Consumer key', 'trackship-for-woocommerce' ); ?>" />
						<input type="text" class="trackship-mcp-input" id="trackship-mcp-cs" placeholder="cs_…" autocomplete="off" spellcheck="false" aria-label="<?php esc_attr_e( 'Consumer secret', 'trackship-for-woocommerce' ); ?>" />
						<?php
						/*
						 * Asked before it is taken, like every other control on this screen
						 * that changes something. What is being handed over is a credential
						 * for the whole store, and when one is already here it is replaced —
						 * neither is a thing to do by mis-clicking a button.
						 */
						?>
						<button type="button" class="button button-trackship" data-trackship-mcp-usekey
							data-trackship-mcp-confirm="<?php
								echo esc_attr(
									'' !== $woo_pair
										? __( 'Replace the key this site is using? The one stored now is dropped, and any app set up with it stops working until you set it up again with the new one. The old key is not deleted in WooCommerce.', 'trackship-for-woocommerce' )
										: __( 'Store this key on the site? It is kept encrypted so your setup steps survive a reload, and it opens the whole store — orders, products, customers — to whatever you connect with it.', 'trackship-for-woocommerce' )
								);
							?>"
							data-label-working="<?php esc_attr_e( 'Working…', 'trackship-for-woocommerce' ); ?>">
							<?php esc_html_e( 'Use this key', 'trackship-for-woocommerce' ); ?>
						</button>
					</div>
				</div>

				<?php
				/*
				 * Drawn here when a key is already stored, and by the reply to the button when
				 * one is entered.
				 *
				 * It used to be empty on every page load, because the key was thrown away as
				 * soon as the rows were drawn. That made a refresh — or simply coming back —
				 * look like nothing had ever been set up, and the only way forward was to
				 * fetch the key from WooCommerce and paste it again. The key is kept sealed
				 * now, so the steps are simply here.
				 */
				?>
				<div class="trackship-mcp-woo" data-trackship-mcp-woo<?php echo '' === $woo_pair ? ' hidden' : ''; ?>>
					<div class="trackship-mcp-clients" data-trackship-mcp-woo-rows>
						<?php
						if ( '' !== $woo_pair ) {
							foreach ( trackship_mcp_client_targets( Trackship_MCP_Woo_Key::endpoint_url(), $woo_pair ) as $woo_target ) {
								trackship_mcp_client_row( $woo_target );
							}
						}
						?>
					</div>
				</div>

				<?php /* Present either way, hidden until there is a key to forget, so the reply to entering one can simply reveal it. */ ?>
				<div class="trackship-mcp-row" data-trackship-mcp-woo-forget<?php echo '' === $woo_pair ? ' hidden' : ''; ?>>
					<div class="trackship-mcp-row__head">
						<span class="trackship-mcp-row__label"><?php esc_html_e( 'Forget this key', 'trackship-for-woocommerce' ); ?></span>
						<p class="trackship-mcp-row__hint"><?php esc_html_e( 'Removes the key from this site so the steps above are no longer shown. The key itself keeps working until you delete it in WooCommerce.', 'trackship-for-woocommerce' ); ?></p>
					</div>
					<div class="trackship-mcp-row__control">
						<button type="button" class="button button-trackship" data-trackship-mcp-forgetkey
							data-trackship-mcp-confirm="<?php esc_attr_e( 'Forget the stored key? The setup steps will be hidden until you paste it again. Nothing is deleted in WooCommerce.', 'trackship-for-woocommerce' ); ?>"
							data-label-working="<?php esc_attr_e( 'Working…', 'trackship-for-woocommerce' ); ?>">
							<?php esc_html_e( 'Forget key', 'trackship-for-woocommerce' ); ?>
						</button>
					</div>
				</div>

				<div class="trackship-mcp-clients">
					<p class="trackship-mcp-clients__note">
						<?php echo esc_html( $args['scope_note'] ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>

		<?php /* Repainted with the rest when a key is entered or forgotten. */ ?>
		<div class="trackship-mcp-card" data-trackship-mcp-woo-conns<?php echo '' === $woo_pair ? ' hidden' : ''; ?>>
			<div class="trackship-mcp-card__head">
				<h3 class="trackship-mcp-card__title"><?php esc_html_e( 'Who is connected', 'trackship-for-woocommerce' ); ?></h3>
				<p class="trackship-mcp-card__sub"><?php echo esc_html( $args['conns_sub'] ); ?></p>
			</div>

			<div data-trackship-mcp-woo-conns-body>
				<?php trackship_mcp_render_woo_connections(); ?>
			</div>
		</div>

		<div class="trackship-mcp-card" data-trackship-mcp-woo-about<?php echo '' === $woo_pair ? ' hidden' : ''; ?>>
			<div class="trackship-mcp-card__head">
				<h3 class="trackship-mcp-card__title"><?php esc_html_e( 'About this key', 'trackship-for-woocommerce' ); ?></h3>
				<p class="trackship-mcp-card__sub"><?php echo esc_html( $args['about_sub'] ); ?></p>
			</div>

			<div data-trackship-mcp-woo-about-body>
				<?php trackship_mcp_render_woo_key_details(); ?>
			</div>
		</div>

		<?php /* Only while the route exists. Listing what it would carry, on a store where it carries nothing, reads as a promise rather than a description. */ ?>
		<?php if ( $woo_on ) : ?>
			<div class="trackship-mcp-card">
				<div class="trackship-mcp-card__head">
					<h3 class="trackship-mcp-card__title"><?php esc_html_e( 'What this key can do', 'trackship-for-woocommerce' ); ?></h3>
					<p class="trackship-mcp-card__sub"><?php echo esc_html( $args['tools_sub'] ); ?></p>
				</div>

				<?php /* Repainted in place when a key is entered or forgotten, because what the route offers depends on the key that asks. */ ?>
				<div data-trackship-mcp-woo-tools>
					<?php trackship_mcp_render_woo_tools( $args['tools_empty'] ); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_connect_url' ) ) {
	/**
	 * The link to put on screen: one address for everyone. Whoever adds it signs in as
	 * themselves and gets a connection of their own. Empty while sign-in is unavailable.
	 *
	 * @return string
	 */
	function trackship_mcp_connect_url() {
		return ( class_exists( 'Trackship_MCP_OAuth' ) && Trackship_MCP_OAuth::available() ) ? Trackship_MCP_OAuth::endpoint_url() : '';
	}
}

if ( ! function_exists( 'trackship_mcp_link_hint' ) ) {
	/**
	 * What to say under the link.
	 *
	 * @return string
	 */
	function trackship_mcp_link_hint() {
		return __( 'The same link for everyone. Whoever adds it signs in with their own store account, and you can turn any one of them off on its own.', 'trackship-for-woocommerce' );
	}
}

if ( ! function_exists( 'trackship_mcp_is_caller' ) ) {
	/**
	 * Whether something that reached the link counts as somebody connecting.
	 *
	 * The link is an address now rather than a password, which means it can safely be pasted
	 * where addresses get pasted -- and the moment it lands in a chat window, that chat's
	 * preview bot fetches it. Those fetches arrive with no credential and are turned away,
	 * exactly as they should be, and they used to arrive on this screen as rows saying
	 * REFUSED: a list of people supposedly trying to get in, none of whom exist.
	 *
	 * Two more are dropped for the same reason: requests the store made to itself, and this
	 * package's own link test. None of the three is a person, and a list of who is connected
	 * that reports machinery as people is worse than no list.
	 *
	 * Anything not recognised is kept. An unknown caller on a store's own connection is the
	 * one thing here most worth showing, so the doubt is spent in favour of showing it.
	 *
	 * @param string $agent The caller's reported name.
	 * @return bool
	 */
	function trackship_mcp_is_caller( $agent ) {
		$agent = strtolower( (string) $agent );

		$machinery = array(
			// The store, and this package, talking to themselves.
			'wordpress/',
			'trackship link test',
			// Link previews. They follow any address a person pastes into a chat.
			'slackbot',
			'twitterbot',
			'facebookexternalhit',
			'whatsapp',
			'telegrambot',
			'discordbot',
			'skypeuripreview',
			'linkedinbot',
			'embedly',
			'redditbot',
			'bingbot',
			'googlebot',
		);

		/**
		 * Filter the callers treated as machinery rather than as people.
		 *
		 * @since 1.5.1
		 *
		 * @param string[] $machinery Lowercased fragments matched against the caller's name.
		 * @param string $agent The caller being judged.
		 */
		$machinery = apply_filters( 'trackship_mcp_machinery_agents', $machinery, $agent );

		foreach ( $machinery as $fragment ) {
			if ( false !== strpos( $agent, $fragment ) ) {
				return false;
			}
		}

		return true;
	}
}

if ( ! function_exists( 'trackship_mcp_render_connections' ) ) {
	/**
	 * Echo the list of people connected to this store, each revocable on its own.
	 *
	 * The screen that makes per-person tokens worth having. Without it a merchant can see
	 * that something connected -- the audit log says that much -- but not who holds access
	 * today, and has no way to take one person's away.
	 *
	 * Deliberately not a "keys" screen. Nothing is generated here: a row appears because
	 * somebody connected and signed in, and the only control is the one that ends it. A
	 * merchant reading this list is asking "who can reach my store", and every column
	 * answers part of that: who, from which app, when last, and whether it still works.
	 *
	 * @param array $args {
	 * @type string $empty Shown while nobody has connected yet.
	 * }
	 * @return void
	 */
	function trackship_mcp_render_connections( $args = array() ) {
		if ( ! class_exists( 'Trackship_MCP_Keys' ) ) {
			return;
		}

		$args = array_merge(
			array(
				'empty' => __( 'Nobody has connected yet. Anyone you give the link to appears here once they connect, and you can turn any of them off without affecting the others.', 'trackship-for-woocommerce' ),
			),
			is_array( $args ) ? $args : array()
		);

		$rows = Trackship_MCP_Keys::all();

		if ( empty( $rows ) ) {
			?>
			<div class="trackship-mcp-row">
				<div class="trackship-mcp-row__head">
					<p class="trackship-mcp-row__hint"><?php echo esc_html( $args['empty'] ); ?></p>
				</div>
			</div>
			<?php
			return;
		}

		trackship_mcp_nonce_field();
		?>
		<div class="trackship-mcp-log">
		<table class="trackship-mcp-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'Acting as', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'App', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Connected', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Last used', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Status', 'trackship-for-woocommerce' ); ?></th>
					<th></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $rows as $row ) : ?>
					<?php
					$user = get_userdata( (int) $row['user_id'] );
					$revoked = ! empty( $row['revoked_at'] );

					// A person is named by their email, because that is how a merchant knows
					// their colleagues. A deleted account still gets a row -- the connection
					// existed, and hiding it would hide that it once had access.
					$who = $user ? $user->user_email : sprintf(
						/* translators: %d: the WordPress user id of a deleted account. */
						__( 'Deleted account #%d', 'trackship-for-woocommerce' ),
						(int) $row['user_id']
					);

					$last = ! empty( $row['last_used_at'] )
						? sprintf(
							/* translators: %s: a length of time, e.g. "2 hours". */
							__( '%s ago', 'trackship-for-woocommerce' ),
							human_time_diff( (int) strtotime( $row['last_used_at'] . ' UTC' ), time() )
						)
						: __( 'never', 'trackship-for-woocommerce' );
					?>
					<tr data-trackship-mcp-conn="<?php echo esc_attr( $row['id'] ); ?>">
						<td><?php echo esc_html( $who ); ?></td>
						<td><?php echo esc_html( '' !== $row['client'] ? $row['client'] : $row['name'] ); ?></td>
						<td><?php echo esc_html( get_date_from_gmt( $row['created_at'], 'Y-m-d' ) ); ?></td>
						<td><?php echo esc_html( $last ); ?></td>
						<td>
							<span class="trackship-mcp-badge <?php echo $revoked ? 'trackship-mcp-badge--error' : 'trackship-mcp-badge--ok'; ?>">
								<?php echo $revoked ? esc_html__( 'Revoked', 'trackship-for-woocommerce' ) : esc_html__( 'Active', 'trackship-for-woocommerce' ); ?>
							</span>
						</td>
						<td>
							<?php if ( ! $revoked ) : ?>
								<button type="button" class="trackship-mcp-revoke" data-trackship-mcp-revoke="<?php echo esc_attr( $row['id'] ); ?>"
									data-label-working="<?php esc_attr_e( 'Turning off…', 'trackship-for-woocommerce' ); ?>"
									data-trackship-mcp-confirm="<?php
										/* translators: %s: the person's email address. */
										echo esc_attr( sprintf( __( 'Turn off this connection for %s? Their AI assistant stops working straight away. Everyone else is unaffected.', 'trackship-for-woocommerce' ), $who ) );
									?>">
									<?php esc_html_e( 'Revoke', 'trackship-for-woocommerce' ); ?>
								</button>
							<?php endif; ?>
						</td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>

		<?php
		/*
		 * The lever a merchant reaches for when an app will not ask again.
		 *
		 * An AI app keeps its token and nothing done inside the app clears it -- remove the
		 * connection in VS Code, add it straight back, and it reuses what it already has.
		 * No sign-in, no Allow screen, and no way for the merchant to make one happen.
		 *
		 * Withdrawing the tokens is the only thing that works from this side, so it is
		 * offered plainly rather than left as something only support knows about. The link
		 * does not change; everyone simply proves who they are once more.
		 */
		if ( Trackship_MCP_Keys::live_count() > 0 ) :
			?>
			<div class="trackship-mcp-connections__foot">
				<button type="button" class="trackship-mcp-revoke" data-trackship-mcp-revoke-all
					data-label-working="<?php esc_attr_e( 'Turning off…', 'trackship-for-woocommerce' ); ?>"
					data-trackship-mcp-confirm="<?php esc_attr_e( 'Sign everyone out? Every AI assistant stops working until each person connects again. Your link does not change.', 'trackship-for-woocommerce' ); ?>">
					<?php esc_html_e( 'Sign everyone out', 'trackship-for-woocommerce' ); ?>
				</button>
				<p class="trackship-mcp-connections__hint">
					<?php esc_html_e( 'Use this when an app will not ask to sign in again — removing it inside the app is not enough, because the app keeps its own copy of the connection.', 'trackship-for-woocommerce' ); ?>
				</p>
			</div>
			<?php
		endif;
		?>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_channel_label' ) ) {
	/**
	 * Turn a stored channel slug into something a merchant recognises.
	 *
	 * @param string $channel Stored channel.
	 * @return string
	 */
	function trackship_mcp_channel_label( $channel ) {
		$known = array(
			'woocommerce-mcp' => __( 'WooCommerce MCP', 'trackship-for-woocommerce' ),
			'ast-connection' => __( 'Your link', 'trackship-for-woocommerce' ),
			'trackship-link' => __( 'Your link', 'trackship-for-woocommerce' ),
		);

		/**
		 * Filter the display name of an audit channel.
		 *
		 * @since 1.1.0
		 *
		 * @param string[] $known Label keyed by stored channel slug.
		 * @param string $channel The channel being labelled.
		 */
		$known = apply_filters( 'trackship_mcp_channel_labels', $known, $channel );

		return isset( $known[ $channel ] ) ? $known[ $channel ] : '—';
	}
}

if ( ! function_exists( 'trackship_mcp_render_audit_log' ) ) {
	/**
	 * Echo the audit log: every action an AI assistant took, with the result and any error.
	 *
	 * The caller supplies only how to build a link back to its own screen for paging.
	 *
	 * @param array $args {
	 * @type callable $page_url Given a page number, returns a URL to this screen at that
	 * page. Omit to render without paging links.
	 * @type int $page Page to show. Defaults to the ai_log_page query argument.
	 * @type string $empty Shown when nothing has been logged yet.
	 * }
	 * @return void
	 */
	function trackship_mcp_render_audit_log( $args = array() ) {
		if ( ! class_exists( 'Trackship_MCP_Audit' ) ) {
			return;
		}

		$args = array_merge(
			array(
				'page_url' => null,
				'page' => 0,
				'empty' => __( 'Nothing yet. Once an AI assistant reads or updates anything on this store, every action it takes will be listed here.', 'trackship-for-woocommerce' ),
			),
			is_array( $args ) ? $args : array()
		);

		$page = (int) $args['page'];

		if ( $page < 1 ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only paging state, no action taken.
			$page = isset( $_GET['ai_log_page'] ) ? max( 1, absint( wp_unslash( $_GET['ai_log_page'] ) ) ) : 1;
		}

		$entries = Trackship_MCP_Audit::entries( $page );
		$total = Trackship_MCP_Audit::count();
		$pages = $total ? (int) ceil( $total / Trackship_MCP_Audit::PER_PAGE ) : 1;

		if ( empty( $entries ) ) {
			?>
			<div class="trackship-mcp-row">
				<div class="trackship-mcp-row__head">
					<p class="trackship-mcp-row__hint"><?php echo esc_html( $args['empty'] ); ?></p>
				</div>
			</div>
			<?php
			return;
		}
		?>
		<div class="trackship-mcp-log">
		<table class="trackship-mcp-table">
			<thead>
				<tr>
					<th><?php esc_html_e( 'When', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Tool', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Channel', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'App', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'User', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Result', 'trackship-for-woocommerce' ); ?></th>
					<th><?php esc_html_e( 'Error', 'trackship-for-woocommerce' ); ?></th>
				</tr>
			</thead>
			<tbody>
				<?php foreach ( $entries as $entry ) : ?>
					<?php
					$failed = ( 'error' === $entry['result'] );
					// Stored in UTC; show it in the store's own timezone.
					$when = get_date_from_gmt( $entry['created_at'], 'Y-m-d H:i:s' );
					?>
					<tr>
						<td><?php echo esc_html( $when ); ?></td>
						<td><code><?php echo esc_html( $entry['tool'] ); ?></code></td>
						<td><?php echo esc_html( trackship_mcp_channel_label( $entry['channel'] ) ); ?></td>
						<td><?php echo esc_html( ! empty( $entry['client'] ) ? $entry['client'] : '—' ); ?></td>
						<td><?php echo esc_html( ! empty( $entry['user_login'] ) ? $entry['user_login'] : '—' ); ?></td>
						<td>
							<span class="trackship-mcp-badge <?php echo $failed ? 'trackship-mcp-badge--error' : 'trackship-mcp-badge--ok'; ?>">
								<?php echo $failed ? esc_html__( 'Refused', 'trackship-for-woocommerce' ) : esc_html__( 'OK', 'trackship-for-woocommerce' ); ?>
							</span>
						</td>
						<td class="trackship-mcp-log__error" title="<?php echo esc_attr( ! empty( $entry['error'] ) ? $entry['error'] : '' ); ?>"><?php echo esc_html( ! empty( $entry['error'] ) ? $entry['error'] : '—' ); ?></td>
					</tr>
				<?php endforeach; ?>
			</tbody>
		</table>
		</div>

		<?php if ( $pages > 1 && is_callable( $args['page_url'] ) ) : ?>
			<div class="trackship-mcp-row">
				<div class="trackship-mcp-row__head">
					<p class="trackship-mcp-row__hint">
						<?php
						printf(
							/* translators: 1: current page number, 2: total pages, 3: total number of entries. */
							esc_html__( 'Page %1$d of %2$d — %3$d entries', 'trackship-for-woocommerce' ),
							(int) $page,
							(int) $pages,
							(int) $total
						);
						?>
					</p>
				</div>
				<div class="trackship-mcp-row__control">
					<?php if ( $page > 1 ) : ?>
						<a class="trackship-mcp-pagebtn" href="<?php echo esc_url( call_user_func( $args['page_url'], $page - 1 ) ); ?>"><?php esc_html_e( 'Newer', 'trackship-for-woocommerce' ); ?></a>
					<?php endif; ?>
					<?php if ( $page < $pages ) : ?>
						<a class="trackship-mcp-pagebtn" href="<?php echo esc_url( call_user_func( $args['page_url'], $page + 1 ) ); ?>"><?php esc_html_e( 'Older', 'trackship-for-woocommerce' ); ?></a>
					<?php endif; ?>
				</div>
			</div>
		<?php endif; ?>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_nonce_field' ) ) {
	/**
	 * Print the nonce the AI Assistant's AJAX actions check.
	 *
	 * Printed at most once per request, because two fields would share an id and the script
	 * reads it by id.
	 *
	 * @return void
	 */
	function trackship_mcp_nonce_field() {
		static $done = false;

		if ( $done || ! class_exists( 'Trackship_MCP_Woo_Key' ) ) {
			return;
		}

		$done = true;

		wp_nonce_field( Trackship_MCP_Woo_Key::NONCE, Trackship_MCP_Woo_Key::NONCE . '_nonce' );
	}
}

if ( ! function_exists( 'trackship_mcp_render_connection' ) ) {
	/**
	 * Echo the whole connection block: the link, the self-test, the steps and the folds.
	 *
	 * The caller chooses which URL, what to call it, what to warn about, and the steps.
	 *
	 * @param array $args {
	 * @type string $url The connection URL. An empty string renders the block hidden,
	 * ready for the script to fill in after the toggle saves.
	 * @type string $wrap Value for data-trackship-mcp-linkwrap, unique on the screen.
	 * @type string $field_id id of the URL input, unique on the screen.
	 * @type string $label Row label. Defaults to "Your link".
	 * @type string $hint Warning under the label.
	 * @type string[] $steps The numbered steps. Defaults to the claude.ai path.
	 * @type bool $selftest Whether to offer "Test my link". Default true.
	 * }
	 * @return void
	 */
	function trackship_mcp_render_connection( $args ) {
		$args = array_merge(
			array(
				'url' => '',
				'wrap' => 'tracking',
				'field_id' => trackship_mcp_endpoint_field_id(),
				'label' => __( 'Your link', 'trackship-for-woocommerce' ),
				'hint' => trackship_mcp_link_hint(),
				'steps' => array(),
				'selftest' => true,
				'empty_note' => '',
			),
			is_array( $args ) ? $args : array()
		);

		trackship_mcp_nonce_field();

		// A caller with its own on/off switch above this block wants nothing here: the switch
		// is the answer, and the block fills itself in the moment it is saved. A caller
		// without one -- a plugin that joined a connection somebody else switches on -- has to
		// say where it is switched on, or its screen is an empty space with no explanation.
		if ( '' === $args['url'] && '' !== $args['empty_note'] ) {
			?>
			<div class="trackship-mcp-row">
				<div class="trackship-mcp-row__head">
					<p class="trackship-mcp-row__hint"><?php echo esc_html( $args['empty_note'] ); ?></p>
				</div>
			</div>
			<?php
			return;
		}

		if ( empty( $args['steps'] ) ) {
			$args['steps'] = array(
				__( 'Open claude.ai and go to Settings, then Connectors.', 'trackship-for-woocommerce' ),
				__( 'Choose Add custom connector, give it any name, and paste your link.', 'trackship-for-woocommerce' ),
			);
		}

		// The sign-in is part of connecting now, so it belongs in the instructions rather than
		// arriving as a surprise halfway through.
		if ( class_exists( 'Trackship_MCP_OAuth' ) && Trackship_MCP_OAuth::available() && ! empty( $args['steps'] ) ) {
			array_splice(
				$args['steps'],
				2,
				0,
				array( __( 'Press Connect and sign in with your store account. Once only.', 'trackship-for-woocommerce' ) )
			);
		}
		?>
		<div data-trackship-mcp-linkwrap="<?php echo esc_attr( $args['wrap'] ); ?>"<?php echo '' === $args['url'] ? ' hidden' : ''; ?>>
			<div class="trackship-mcp-row trackship-mcp-row--stacked">
				<div class="trackship-mcp-row__head">
					<span class="trackship-mcp-row__label"><?php echo esc_html( $args['label'] ); ?></span>
					<p class="trackship-mcp-row__hint"><?php echo esc_html( $args['hint'] ); ?></p>
				</div>
				<div class="trackship-mcp-row__control trackship-mcp-url">
					<input type="text" class="trackship-mcp-input trackship-mcp-url__field" id="<?php echo esc_attr( $args['field_id'] ); ?>" value="<?php echo esc_attr( $args['url'] ); ?>" readonly onfocus="this.select();" />
					<button type="button" class="button button-trackship" data-trackship-mcp-copy="#<?php echo esc_attr( $args['field_id'] ); ?>" data-label-copied="<?php esc_attr_e( 'Copied', 'trackship-for-woocommerce' ); ?>"
						data-label-copyfailed="<?php esc_attr_e( 'Copy failed — press Ctrl+C', 'trackship-for-woocommerce' ); ?>">
						<?php esc_html_e( 'Copy', 'trackship-for-woocommerce' ); ?>
					</button>
				</div>
			</div>

			<?php if ( $args['selftest'] ) : ?>
				<?php
				/*
				 * One self-test per screen, not one per link. The test asks whether anything
				 * from outside is reaching this site at all, and that answer is the same for
				 * every link on it -- a second button would run the same check and print the
				 * same sentence, while looking like it had checked something else.
				 */
				?>
				<div class="trackship-mcp-row">
					<div class="trackship-mcp-row__head">
						<span class="trackship-mcp-row__label"><?php esc_html_e( 'Not working in your AI app?', 'trackship-for-woocommerce' ); ?></span>
						<p class="trackship-mcp-row__hint" data-trackship-mcp-testresult>
							<?php esc_html_e( 'Checks whether an AI assistant can actually reach your link, and says what stopped it if not.', 'trackship-for-woocommerce' ); ?>
						</p>
						<p class="trackship-mcp-row__hint trackship-mcp-testdetail" data-trackship-mcp-testdetail hidden></p>
						<p class="trackship-mcp-row__hint" data-trackship-mcp-testlinks hidden></p>
						<?php
						/*
						 * Offered only when the test has PROVED the store refuses callers by name, and
						 * only where .htaccess can actually answer it. A button that edits a server
						 * config file has to be earned by evidence; sitting there permanently it would
						 * be an invitation to change something that was never wrong.
						 */
						?>
						<p class="trackship-mcp-row__hint" data-trackship-mcp-fixwrap hidden>
							<button type="button" class="button button-trackship" data-trackship-mcp-fix
								data-label-working="<?php esc_attr_e( 'Fixing…', 'trackship-for-woocommerce' ); ?>"
								data-trackship-mcp-confirm="<?php esc_attr_e( 'This adds four lines to your .htaccess file so requests to this plugin, and nothing else, skip your bad-bot rules. Nothing else on your site changes, and no rule is deleted. A copy of the file is kept, and the change is undone automatically if it turns out not to help. Continue?', 'trackship-for-woocommerce' ); ?>">
								<?php esc_html_e( 'Fix this for me', 'trackship-for-woocommerce' ); ?>
							</button>
						</p>
					</div>
					<div class="trackship-mcp-row__control">
						<button type="button" class="button button-trackship" data-trackship-mcp-selftest
							data-label-working="<?php esc_attr_e( 'Testing…', 'trackship-for-woocommerce' ); ?>">
							<?php esc_html_e( 'Test my link', 'trackship-for-woocommerce' ); ?>
						</button>
					</div>
				</div>
			<?php endif; ?>

			<?php
			trackship_mcp_steps( $args['steps'] );

			trackship_mcp_disclosure_open( __( 'Using ChatGPT, or an app instead of claude.ai?', 'trackship-for-woocommerce' ) );
			?>
				<div class="trackship-mcp-clients">
					<?php foreach ( trackship_mcp_client_targets( $args['url'] ) as $target ) : ?>
						<?php trackship_mcp_client_row( $target ); ?>
					<?php endforeach; ?>
				</div>
			<?php
			trackship_mcp_disclosure_close();
			?>
		</div>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_steps' ) ) {
	/**
	 * Echo a numbered "do this, then this" list.
	 *
	 * The single most useful thing on this screen for someone who has never connected an
	 * AI assistant, and the reason the rest of the connection detail is folded away: a
	 * merchant needs one path spelled out, not a menu of five.
	 *
	 * @param string[] $steps Steps in order.
	 * @return void
	 */
	function trackship_mcp_steps( $steps ) {
		if ( empty( $steps ) ) {
			return;
		}
		?>
		<ol class="trackship-mcp-steps">
			<?php foreach ( $steps as $step ) : ?>
				<li><?php echo esc_html( $step ); ?></li>
			<?php endforeach; ?>
		</ol>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_disclosure_open' ) ) {
	/**
	 * Open a folded section.
	 *
	 * <details> rather than a scripted accordion: it works before any JavaScript runs, it
	 * is what a screen reader already understands, and browser find-in-page opens it.
	 *
	 * @param string $summary Visible label.
	 * @param string $class Extra class for the details element.
	 * @return void
	 */
	function trackship_mcp_disclosure_open( $summary, $class = '' ) {
		?>
		<details class="trackship-mcp-more <?php echo esc_attr( $class ); ?>">
			<summary class="trackship-mcp-more__summary"><?php echo esc_html( $summary ); ?></summary>
			<div class="trackship-mcp-more__body">
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_disclosure_close' ) ) {
	/**
	 * Close a folded section opened by trackship_mcp_disclosure_open().
	 *
	 * @return void
	 */
	function trackship_mcp_disclosure_close() {
		?>
			</div>
		</details>
		<?php
	}
}

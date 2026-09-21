<?php
/**
 * The AI Assistant screen, whole.
 *
 * Sidebar, sections and cards for the AI Assistant tab. includes/views/ai-assistant.php
 * supplies the wording; the settings fields save through trackship_mcp_save_setting.
 *
 * @package TrackShip for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'trackship_mcp_icon' ) ) {
	/**
	 * A named icon, as inline SVG (copied from the ZUI set, same viewBox and stroke).
	 *
	 * @param string $name Icon key.
	 * @return string Inline SVG, empty for an unknown name.
	 */
	function trackship_mcp_icon( $name ) {
		$paths = array(
			// Your own link.
			'sparkles' => '<path d="m12 3-1.9 5.8a2 2 0 0 1-1.3 1.3L3 12l5.8 1.9a2 2 0 0 1 1.3 1.3L12 21l1.9-5.8a2 2 0 0 1 1.3-1.3L21 12l-5.8-1.9a2 2 0 0 1-1.3-1.3Z"/><path d="M5 3v4"/><path d="M19 17v4"/><path d="M3 5h4"/><path d="M17 19h4"/>',
			// WooCommerce's route.
			'store' => '<path d="m2 7 4.41-4.41A2 2 0 0 1 7.83 2h8.34a2 2 0 0 1 1.42.59L22 7"/><path d="M4 12v8a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-8"/><path d="M15 22v-4a2 2 0 0 0-2-2h-2a2 2 0 0 0-2 2v4"/><path d="M2 7h20"/><path d="M22 7v3a2 2 0 0 1-4 0V7"/><path d="M18 10a2 2 0 0 1-4 0V7"/><path d="M14 10a2 2 0 0 1-4 0V7"/><path d="M10 10a2 2 0 0 1-4 0V7"/><path d="M6 10a2 2 0 0 1-4 0V7"/>',
			// The audit log.
			'clipboard-list' => '<rect width="8" height="4" x="8" y="2" rx="1" ry="1"/><path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/><path d="M12 11h4"/><path d="M12 16h4"/><path d="M8 11h.01"/><path d="M8 16h.01"/>',
			// Close the mobile drawer.
			'x' => '<line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/>',
			// The tooltip marker.
			'help-circle' => '<circle cx="12" cy="12" r="10"/><path d="M9.09 9a3 3 0 0 1 5.83 1c0 2-3 3-3 3"/><line x1="12" y1="17" x2="12.01" y2="17"/>',
			// The Quick Help links.
			'arrow-right' => '<line x1="5" y1="12" x2="19" y2="12"/><polyline points="12 5 19 12 12 19"/>',
		);

		if ( ! isset( $paths[ $name ] ) ) {
			return '';
		}

		return '<svg class="trackship-mcp-icon" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24"'
			. ' fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"'
			. ' aria-hidden="true" focusable="false">' . $paths[ $name ] . '</svg>';
	}
}

if ( ! function_exists( 'trackship_mcp_render_quick_help' ) ) {
	/**
	 * The Quick Help card at the foot of the sidebar.
	 *
	 * Links default to TrackShip's documentation and support; `words` can override them.
	 *
	 * @param array $config Screen configuration, already defaulted.
	 * @return void
	 */
	function trackship_mcp_render_quick_help( $config ) {
		$words = $config['words'];

		if ( '' === $words['help_title'] ) {
			return;
		}
		?>
		<div class="trackship-mcp-quickhelp">
			<h4 class="trackship-mcp-quickhelp__title"><?php echo esc_html( $words['help_title'] ); ?></h4>
			<p class="trackship-mcp-quickhelp__text"><?php echo esc_html( $words['help_text'] ); ?></p>

			<div class="trackship-mcp-quickhelp__links">
				<a class="trackship-mcp-quickhelp__link" href="<?php echo esc_url( $words['help_docs_url'] ); ?>" target="_blank" rel="noreferrer noopener">
					<span><?php echo esc_html( $words['help_docs'] ); ?></span>
					<?php echo trackship_mcp_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG from the icon set. ?>
				</a>
				<a class="trackship-mcp-quickhelp__link trackship-mcp-quickhelp__link--muted" href="<?php echo esc_url( $words['help_support_url'] ); ?>" target="_blank" rel="noreferrer noopener">
					<span><?php echo esc_html( $words['help_support'] ); ?></span>
					<?php echo trackship_mcp_icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG from the icon set. ?>
				</a>
			</div>

			<?php /* Decoration, and announced as such: two sheets of paper and a tick. */ ?>
			<div class="trackship-mcp-quickhelp__art" aria-hidden="true">
				<span class="trackship-mcp-quickhelp__glow"></span>
				<span class="trackship-mcp-quickhelp__sphere"></span>

				<span class="trackship-mcp-quickhelp__paper trackship-mcp-quickhelp__paper--1">
					<span class="trackship-mcp-quickhelp__line trackship-mcp-quickhelp__line--brand"></span>
					<span class="trackship-mcp-quickhelp__line trackship-mcp-quickhelp__line--mid"></span>
					<span class="trackship-mcp-quickhelp__line trackship-mcp-quickhelp__line--short"></span>
				</span>
				<span class="trackship-mcp-quickhelp__paper trackship-mcp-quickhelp__paper--2">
					<span class="trackship-mcp-quickhelp__line trackship-mcp-quickhelp__line--accent"></span>
					<span class="trackship-mcp-quickhelp__line trackship-mcp-quickhelp__line--mid"></span>
					<span class="trackship-mcp-quickhelp__line trackship-mcp-quickhelp__line--mid"></span>
					<span class="trackship-mcp-quickhelp__line trackship-mcp-quickhelp__line--short"></span>
				</span>

				<span class="trackship-mcp-quickhelp__check">&#10003;</span>
			</div>
		</div>
		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_screen_defaults' ) ) {
	/**
	 * Fill in everything the caller did not say.
	 *
	 * @param array $config Caller's configuration.
	 * @return array
	 */
	function trackship_mcp_screen_defaults( $config ) {
		$config = is_array( $config ) ? $config : array();

		$config = array_merge(
			array(
				'title' => __( 'AI Assistant', 'trackship-for-woocommerce' ),
				'label' => __( 'Your link', 'trackship-for-woocommerce' ),
				'param' => 'ai_section',
				'base_url' => '',
				'ids' => array(),
				'sidebar' => null,
				'words' => array(),
			),
			$config
		);

		$config['ids'] = array_merge(
			array(
				'connection' => 'connection',
				'woo' => 'woo-mcp',
				'audit' => 'audit',
			),
			is_array( $config['ids'] ) ? $config['ids'] : array()
		);

		$config['words'] = array_merge(
			array(
				'connection_sub' => __( 'Your own link', 'trackship-for-woocommerce' ),
				'connect_title' => __( 'Connect Claude to your store', 'trackship-for-woocommerce' ),
				'connect_sub' => __( 'Switch on, copy your link, paste it into Claude. Nothing to install.', 'trackship-for-woocommerce' ),
				'heading' => __( 'AI Assistant', 'trackship-for-woocommerce' ),
				'description' => __( 'Let an AI assistant work with this store. Changes save as you make them.', 'trackship-for-woocommerce' ),
				'enable_title' => __( 'Enable the connection', 'trackship-for-woocommerce' ),
				'enable_tooltip' => __( 'Gives you one link an AI assistant can connect to, from claude.ai in a browser, a phone or a desktop app. Whoever adds it signs in with their own store account.', 'trackship-for-woocommerce' ),
				'writes_title' => __( 'Allow the AI to make changes', 'trackship-for-woocommerce' ),
				'writes_tooltip' => __( 'On by default. Turn it off to make the link read-only — the AI can then look things up but not change them.', 'trackship-for-woocommerce' ),
				'writes_hint' => __( 'Turn off to make the connection URL read-only. WooCommerce MCP is not affected — it signs in a real store user instead.', 'trackship-for-woocommerce' ),
				'steps' => array(),
				'tools_title' => __( 'What Claude can do', 'trackship-for-woocommerce' ),
				'tools_note' => '',
				'tools_empty' => __( 'No tools are available on this connection yet.', 'trackship-for-woocommerce' ),
				'connected_title' => __( 'Who is connected', 'trackship-for-woocommerce' ),
				'connected_sub' => __( 'Everyone who has connected an AI assistant to your link. Turn any one of them off without affecting the rest.', 'trackship-for-woocommerce' ),
				'woo_label' => __( 'Woo MCP', 'trackship-for-woocommerce' ),
				'woo_sub' => __( "WooCommerce's own route", 'trackship-for-woocommerce' ),
				'woo_heading' => __( 'WooCommerce MCP', 'trackship-for-woocommerce' ),
				'woo_description' => __( "WooCommerce's own AI connection. Your tools are already on it.", 'trackship-for-woocommerce' ),
				'woo_status_note' => '',
				'woo_key_sub' => '',
				'woo_scope_note' => '',
				'audit_label' => __( 'Audit Log', 'trackship-for-woocommerce' ),
				'audit_sub' => __( 'What the AI did', 'trackship-for-woocommerce' ),
				'audit_heading' => __( 'Audit Log', 'trackship-for-woocommerce' ),
				'audit_desc' => __( 'Every action taken by an AI assistant, with the result and any error.', 'trackship-for-woocommerce' ),
				'audit_keep' => __( 'Keep entries for', 'trackship-for-woocommerce' ),
				'audit_keep_tip' => __( 'Entries older than this are deleted automatically once a day. Set 0 to keep everything.', 'trackship-for-woocommerce' ),
				'audit_keep_hint' => __( 'Days of history to keep. Default is 30.', 'trackship-for-woocommerce' ),
				'audit_empty' => __( 'Nothing yet. Once an AI assistant works on this store, every action it takes will be listed here.', 'trackship-for-woocommerce' ),
				'help_title' => __( 'Quick Help', 'trackship-for-woocommerce' ),
				'help_text' => __( 'Learn how to connect an AI assistant and what it can do.', 'trackship-for-woocommerce' ),
				'help_docs' => __( 'View Documentation', 'trackship-for-woocommerce' ),
				'help_support' => __( 'Get Support', 'trackship-for-woocommerce' ),
				'help_docs_url' => 'https://docs.trackship.com/docs/trackship-for-woocommerce/',
				'help_support_url' => 'https://my.trackship.com/?support=1',
			),
			is_array( $config['words'] ) ? $config['words'] : array()
		);

		return $config;
	}
}

if ( ! function_exists( 'trackship_mcp_screen_sections' ) ) {
	/**
	 * The sidebar: the connection, WooCommerce MCP and the audit log.
	 *
	 * @param array $config Screen configuration, already defaulted.
	 * @return array Section id => meta.
	 */
	function trackship_mcp_screen_sections( $config ) {
		$words = $config['words'];
		$ids = $config['ids'];

		$sections = array(
			$ids['connection'] => array(
				'label' => $config['label'],
				'sub' => $words['connection_sub'],
				'heading' => $words['heading'],
				'description' => $words['description'],
				'icon' => 'sparkles',
				'render' => 'trackship_mcp_screen_connection',
			),
			$ids['woo'] => array(
				'label' => $words['woo_label'],
				'sub' => $words['woo_sub'],
				'heading' => $words['woo_heading'],
				'description' => $words['woo_description'],
				'icon' => 'store',
				'render' => 'trackship_mcp_screen_woo',
			),
			$ids['audit'] => array(
				'label' => $words['audit_label'],
				'sub' => $words['audit_sub'],
				'heading' => $words['audit_heading'],
				'description' => $words['audit_desc'],
				'icon' => 'clipboard-list',
				'render' => 'trackship_mcp_screen_audit',
			),
		);

		return $sections;
	}
}

if ( ! function_exists( 'trackship_mcp_active_section' ) ) {
	/**
	 * Which section the screen opens on.
	 *
	 * An unknown id falls back to the first rather than to an error: the value arrives in a
	 * URL, and a merchant who follows a stale bookmark should land on the screen rather than
	 * on a blank panel telling them their link was wrong.
	 *
	 * @param array $config Screen configuration, already defaulted.
	 * @return string
	 */
	function trackship_mcp_active_section( $config ) {
		$sections = trackship_mcp_screen_sections( $config );

		/*
		 * Two parameters, because two things write the address of this screen.
		 *
		 * The configured one is what a link to a section carries -- a bookmark, or a support
		 * reply pointing straight at the audit log. The host's settings script writes the
		 * other: it puts `?section=<id>` on the URL as the merchant clicks through the
		 * sidebar, so a refresh lands where they were rather than flashing the wrong panel.
		 *
		 * Reading only the first meant every refresh of this screen came back on the opening
		 * section, however far in the merchant had navigated -- the URL said woo-mcp and the
		 * page showed the connection.
		 *
		 * An id only counts if this screen actually has a section by that name, so `section`
		 * left behind by another tab cannot steer this one.
		 */
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- read-only navigation state; nothing is acted on.
		$get = wp_unslash( $_GET );

		foreach ( array( $config['param'], 'section' ) as $param ) {
			if ( '' === (string) $param || ! isset( $get[ $param ] ) ) {
				continue;
			}

			$wanted = sanitize_key( $get[ $param ] );

			if ( isset( $sections[ $wanted ] ) ) {
				return $wanted;
			}
		}

		return (string) key( $sections );
	}
}

/*
|--------------------------------------------------------------------------
| Fields
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'trackship_mcp_enable_hint' ) ) {
	/**
	 * The line under the enable switch, matched to what this store is actually in.
	 *
	 * Three situations, three different next steps, and telling a merchant the wrong one is
	 * worse than telling them nothing: someone whose link is already live does not need
	 * instructions for switching it on, and someone who has WooCommerce MCP already deserves
	 * to know this is not a second copy of it.
	 *
	 * @return string
	 */
	function trackship_mcp_enable_hint() {
		if ( trackship_mcp_own_connection_active() ) {
			if ( ! get_option( 'permalink_structure' ) ) {
				return __( 'Your connection URL is below. Set Settings > Permalinks to anything other than Plain for a cleaner URL.', 'trackship-for-woocommerce' );
			}

			return __( 'Your connection URL is below.', 'trackship-for-woocommerce' );
		}

		if ( trackship_mcp_woo_mcp_active() ) {
			return __( 'WooCommerce MCP already gives these tools to AI assistants on a computer. Switch this on as well only if you also want to connect from claude.ai or a phone, which WooCommerce MCP cannot do.', 'trackship-for-woocommerce' );
		}

		return __( 'Switch on, then paste the URL that appears into your AI assistant as a custom connector.', 'trackship-for-woocommerce' );
	}
}

if ( ! function_exists( 'trackship_mcp_save_setting' ) ) {
	/**
	 * Store one of the AI Assistant's own settings.
	 *
	 * Saved one field at a time, as it is changed, because that is how the screen behaves --
	 * there is no Save button, and a screen that saves on change should not be posting a whole
	 * form to do it.
	 *
	 * @return void
	 */
	function trackship_mcp_save_setting() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_send_json_error( array( 'message' => __( 'You are not allowed to do that.', 'trackship-for-woocommerce' ) ), 403 );
		}

		check_ajax_referer( 'trackship_mcp_form', 'trackship_mcp_form_nonce' );

		// phpcs:disable WordPress.Security.NonceVerification.Missing -- verified immediately above.
		$key = isset( $_POST['key'] ) ? sanitize_key( wp_unslash( $_POST['key'] ) ) : '';
		$value = isset( $_POST['value'] ) ? sanitize_text_field( wp_unslash( $_POST['value'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		// An allow-list, not a sanitiser. These three are the only options this screen owns,
		// and the request names the key -- so anything else arriving here is either a bug or
		// somebody probing what else Trackship_MCP_Settings will write.
		$allowed = array(
			'enabled' => 'bool',
			'allow_write' => 'bool',
			'audit_retention' => 'int',
		);

		if ( ! isset( $allowed[ $key ] ) ) {
			wp_send_json_error( array( 'message' => __( 'Unknown setting.', 'trackship-for-woocommerce' ) ), 400 );
		}

		$value = ( 'int' === $allowed[ $key ] ) ? max( 0, (int) $value ) : (int) (bool) $value;

		Trackship_MCP_Settings::set( $key, $value );

		wp_send_json_success(
			array(
				'key' => $key,
				'value' => $value,
				'url' => trackship_mcp_connect_url(),
			)
		);
	}
}

add_action( 'wp_ajax_trackship_mcp_save_setting', 'trackship_mcp_save_setting' );


if ( ! function_exists( 'trackship_mcp_render_field' ) ) {
	/**
	 * One of the AI Assistant's own settings, as a row.
	 *
	 * Stored by Trackship_MCP_Settings. The input carries `data-trackship-mcp-field` and
	 * assets/trackship-mcp.js saves it through trackship_mcp_save_setting.
	 *
	 * @param string $key Setting key in Trackship_MCP_Settings.
	 * @param array $args Label, tooltip, hint, type ('toggle' or 'number').
	 * @return void
	 */
	function trackship_mcp_render_field( $key, $args ) {
		if ( ! class_exists( 'Trackship_MCP_Settings' ) ) {
			return;
		}

		$args = array_merge(
			array(
				'type' => 'toggle',
				'title' => '',
				'tooltip' => '',
				'hint' => '',
				'min' => 0,
			),
			is_array( $args ) ? $args : array()
		);

		$value = Trackship_MCP_Settings::get( $key );
		$id = 'trackship-mcp-' . sanitize_key( $key );
		?>
		<div class="trackship-mcp-row trackship-mcp-row--inline">
			<div class="trackship-mcp-row__head">
				<span class="trackship-mcp-row__label">
					<?php echo esc_html( $args['title'] ); ?>
					<?php if ( '' !== $args['tooltip'] ) : ?>
						<span class="trackship-mcp-tooltip" tabindex="0" role="img" aria-label="<?php echo esc_attr( $args['tooltip'] ); ?>">
							<?php echo trackship_mcp_icon( 'help-circle' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG from the icon set. ?>
							<span class="trackship-mcp-tooltip__bubble"><?php echo esc_html( $args['tooltip'] ); ?><span class="trackship-mcp-tooltip__arrow"></span></span>
						</span>
					<?php endif; ?>
				</span>
				<?php if ( '' !== $args['tooltip'] ) : ?>
					<p class="trackship-mcp-row__desc"><?php echo esc_html( $args['tooltip'] ); ?></p>
				<?php endif; ?>
				<?php if ( '' !== $args['hint'] ) : ?>
					<p class="trackship-mcp-row__hint"><?php echo esc_html( $args['hint'] ); ?></p>
				<?php endif; ?>
			</div>
			<div class="trackship-mcp-row__control">
				<?php if ( 'number' === $args['type'] ) : ?>
					<?php /* Sized to its content. A three-digit day count in a full-width box reads as a field that wants a sentence. */ ?>
					<input
						type="number"
						class="trackship-mcp-input trackship-mcp-number"
						id="<?php echo esc_attr( $id ); ?>"
						value="<?php echo esc_attr( $value ); ?>"
						min="<?php echo esc_attr( $args['min'] ); ?>"
						data-trackship-mcp-field="<?php echo esc_attr( $key ); ?>"
					>
				<?php else : ?>
					<label class="trackship-mcp-toggle">
						<input
							type="checkbox"
							class="trackship-mcp-toggle__input"
							id="<?php echo esc_attr( $id ); ?>"
							value="1"
							<?php checked( (bool) $value, true ); ?>
							data-trackship-mcp-field="<?php echo esc_attr( $key ); ?>"
						>
						<span class="trackship-mcp-toggle__track"><span class="trackship-mcp-toggle__thumb"></span></span>
					</label>
				<?php endif; ?>
			</div>
		</div>
		<?php
	}
}

/*
|--------------------------------------------------------------------------
| The three shared sections
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'trackship_mcp_screen_connection' ) ) {
	/**
	 * The connection section: the switch, the link, who holds one, and what they can do.
	 *
	 * @param array $config Screen configuration, already defaulted.
	 * @return void
	 */
	function trackship_mcp_screen_connection( $config ) {
		$words = $config['words'];
		$url = trackship_mcp_connect_url();
		?>

		<div class="trackship-mcp-card">
			<div class="trackship-mcp-card__head">
				<h3 class="trackship-mcp-card__title"><?php echo esc_html( $words['connect_title'] ); ?></h3>
				<p class="trackship-mcp-card__sub"><?php echo esc_html( $words['connect_sub'] ); ?></p>
			</div>

			<?php
			trackship_mcp_render_field(
				'enabled',
				array(
					'title' => $words['enable_title'],
					'tooltip' => $words['enable_tooltip'],
					'hint' => trackship_mcp_enable_hint(),
				)
			);

			trackship_mcp_render_connection(
				array(
					'url' => $url,
					'wrap' => 'tracking',
					'field_id' => trackship_mcp_endpoint_field_id(),
					'hint' => trackship_mcp_link_hint(),
					'steps' => $words['steps'],
				)
			);
			?>
		</div>

		<?php if ( class_exists( 'Trackship_MCP_Keys' ) && Trackship_MCP_Keys::available() ) : ?>
			<div class="trackship-mcp-card">
				<div class="trackship-mcp-card__head">
					<h3 class="trackship-mcp-card__title"><?php echo esc_html( $words['connected_title'] ); ?></h3>
					<p class="trackship-mcp-card__sub"><?php echo esc_html( $words['connected_sub'] ); ?></p>
				</div>

				<?php /* Replaced in place when everyone is signed out — every row's status changes at once, and none of it is worth a page load. */ ?>
				<div data-trackship-mcp-connections>
					<?php trackship_mcp_render_connections(); ?>
				</div>
			</div>
		<?php endif; ?>

		<div class="trackship-mcp-card">
			<div class="trackship-mcp-card__head">
				<h3 class="trackship-mcp-card__title"><?php echo esc_html( $words['tools_title'] ); ?></h3>
			</div>

			<?php
			// The permission sits with the list it governs. Split apart, a merchant reads what
			// the AI can reach on one card and decides whether it may write on another, having
			// forgotten the first by the time they get there.
			trackship_mcp_render_field(
				'allow_write',
				array(
					'title' => $words['writes_title'],
					'tooltip' => $words['writes_tooltip'],
					'hint' => $words['writes_hint'],
				)
			);

			trackship_mcp_render_tools(
				array(
					'empty' => $words['tools_empty'],
					'note' => $words['tools_note'],
				)
			);
			?>
		</div>

		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_screen_woo' ) ) {
	/**
	 * The WooCommerce MCP section.
	 *
	 * @param array $config Screen configuration, already defaulted.
	 * @return void
	 */
	function trackship_mcp_screen_woo( $config ) {
		$words = $config['words'];
		$args = array();

		foreach ( array( 'status_note', 'key_sub', 'scope_note' ) as $key ) {
			if ( '' !== $words[ 'woo_' . $key ] ) {
				$args[ $key ] = $words[ 'woo_' . $key ];
			}
		}

		trackship_mcp_render_woo_mcp( $args );
	}
}

if ( ! function_exists( 'trackship_mcp_screen_audit' ) ) {
	/**
	 * The audit log section, and how long it is kept.
	 *
	 * The retention setting lives with the table it governs rather than with the connection
	 * settings: a merchant who thinks the log is too long or too short is already looking at
	 * the log.
	 *
	 * @param array $config Screen configuration, already defaulted.
	 * @return void
	 */
	function trackship_mcp_screen_audit( $config ) {
		$words = $config['words'];
		?>

		<div class="trackship-mcp-card">
			<?php
			trackship_mcp_render_field(
				'audit_retention',
				array(
					'type' => 'number',
					'title' => $words['audit_keep'],
					'tooltip' => $words['audit_keep_tip'],
					'hint' => $words['audit_keep_hint'],
				)
			);
			?>
		</div>

		<div class="trackship-mcp-card">
			<?php
			trackship_mcp_render_audit_log(
				array(
					'page_url' => trackship_mcp_screen_pager( $config ),
					'empty' => $words['audit_empty'],
				)
			);
			?>
		</div>

		<?php
	}
}

if ( ! function_exists( 'trackship_mcp_screen_pager' ) ) {
	/**
	 * A link to another page of the log, landing back on this screen and this section.
	 *
	 * The one thing the log renderer cannot work out for itself: it has no idea which admin
	 * screen it is being drawn on.
	 *
	 * @param array $config Screen configuration, already defaulted.
	 * @return callable
	 */
	function trackship_mcp_screen_pager( $config ) {
		$base = $config['base_url'];
		$param = $config['param'];
		$section = trackship_mcp_active_section( $config );

		return static function ( $page ) use ( $base, $param, $section ) {
			return add_query_arg(
				array(
					$param => $section,
					'ai_log_page' => (int) $page,
				),
				$base
			);
		};
	}
}

/*
|--------------------------------------------------------------------------
| The screen
|--------------------------------------------------------------------------
*/

if ( ! function_exists( 'trackship_mcp_render_screen' ) ) {
	/**
	 * Draw the whole AI Assistant screen: sidebar, sections and all.
	 *
	 * The markup shape -- .trackship-mcp-layout > .trackship-mcp-sidebar + .trackship-mcp-content > .trackship-mcp-section -- is not
	 * decoration. assets/trackship-mcp.js scopes sidebar navigation to the enclosing screen, so
	 * this sidebar drives these sections and never touches another tab's. Change the shape and
	 * the sidebar stops working silently.
	 *
	 * @param array $config {
	 * @type string $title Screen name, used on the mobile header and for aria.
	 * @type string $label Sidebar label for the connection section.
	 * @type string $param Query argument the sidebar navigates on.
	 * @type string $base_url Screen URL, without the section argument.
	 * @type array $ids Override the three shared section ids.
	 * @type callable $sidebar Drawn at the foot of the sidebar. Optional.
	 * @type array $words Every sentence on the screen.
	 * }
	 * @return void
	 *
	 * @see trackship_mcp_enqueue_assets() Call it from admin_enqueue_scripts; this is too late.
	 */
	function trackship_mcp_render_screen( $config ) {
		$config = trackship_mcp_screen_defaults( $config );

		$sections = trackship_mcp_screen_sections( $config );
		$active = trackship_mcp_active_section( $config );

		/*
		 * No enqueue here, deliberately.
		 *
		 * This runs while the admin page is being printed, which is long after wp_print_styles
		 * has fired -- a stylesheet enqueued at this point is simply dropped, with no error and
		 * no missing-file warning, and the screen renders unstyled. The failure is silent,
		 * which is the worst kind to build in.
		 *
		 * So WC_Trackship_Actions calls trackship_mcp_enqueue_assets() from its own
		 * admin_enqueue_scripts hook, where it is early enough to work. Three lines, and they
		 * are the only three this screen asks a plugin to write outside its configuration.
		 */
		?>
		<div class="trackship-mcp-screen">

			<div class="trackship-mcp-sidebar__overlay" data-ast-drawer-close data-trackship-mcp-drawer-close></div>

			<aside class="trackship-mcp-sidebar">

				<div class="trackship-mcp-sidebar__mobile-head">
					<span class="trackship-mcp-sidebar__mobile-title"><?php echo esc_html( $config['title'] ); ?></span>
					<button type="button" class="trackship-mcp-sidebar__close" data-ast-drawer-close data-trackship-mcp-drawer-close aria-label="<?php esc_attr_e( 'Close menu', 'trackship-for-woocommerce' ); ?>">
						<?php echo trackship_mcp_icon( 'x' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG from the icon set. ?>
					</button>
				</div>

				<nav class="trackship-mcp-sidebar__nav" aria-label="<?php echo esc_attr( $config['title'] ); ?>">
					<?php foreach ( $sections as $id => $meta ) : ?>
						<?php $is_on = ( $id === $active ); ?>
						<button
							type="button"
							class="trackship-mcp-sidebar__item<?php echo $is_on ? ' is-active' : ''; ?>"
							data-section="<?php echo esc_attr( $id ); ?>"
							data-trackship-mcp-section="<?php echo esc_attr( $id ); ?>"
							<?php echo $is_on ? 'aria-current="true"' : ''; ?>
						>
							<span class="trackship-mcp-sidebar__icon"><?php echo trackship_mcp_icon( $meta['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG from the icon set. ?></span>
							<span class="trackship-mcp-sidebar__label"><?php echo esc_html( $meta['label'] ); ?></span>
						</button>
					<?php endforeach; ?>
				</nav>

				<?php
				trackship_mcp_render_quick_help( $config );

				if ( is_callable( $config['sidebar'] ) ) {
					call_user_func( $config['sidebar'] );
				}
				?>

			</aside>

			<main class="trackship-mcp-content">

				<?php trackship_mcp_nonce_field(); ?>

				<?php foreach ( $sections as $id => $meta ) : ?>
					<?php $is_on = ( $id === $active ); ?>
					<section
						class="trackship-mcp-section<?php echo $is_on ? ' is-active' : ''; ?>"
						data-section="<?php echo esc_attr( $id ); ?>"
						data-trackship-mcp-section="<?php echo esc_attr( $id ); ?>"
						<?php echo $is_on ? '' : 'hidden'; ?>
					>
						<?php
						/*
						 * ZUI's own section header, class for class.
						 *
						 * Not a simplified version of it. These class names are what
						 * components/section-header.css styles, and a near-miss renders as
						 * unstyled text rather than as an error -- so the shape is copied
						 * exactly.
						 */
						?>
						<div class="trackship-mcp-section-header">
							<div class="trackship-mcp-section-header__main">
								<span class="trackship-mcp-section-header__icon"><?php echo trackship_mcp_icon( $meta['icon'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- fixed SVG from the icon set. ?></span>
								<div class="trackship-mcp-section-header__text">
									<div class="trackship-mcp-section-header__titlewrap">
										<h2 class="trackship-mcp-section-header__title"><?php echo esc_html( $meta['heading'] ); ?></h2>
									</div>
									<?php if ( '' !== $meta['description'] ) : ?>
										<p class="trackship-mcp-section-header__sub"><?php echo esc_html( $meta['description'] ); ?></p>
									<?php endif; ?>
								</div>
							</div>
						</div>

						<?php call_user_func( $meta['render'], $config ); ?>
					</section>
				<?php endforeach; ?>

			</main>

		</div>
		<?php
	}
}

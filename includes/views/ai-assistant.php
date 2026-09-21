<?php
/**
 * TrackShip - AI Assistant tab panel.
 *
 * The sidebar, sections, connection card, settings fields, WooCommerce key section and audit
 * log are drawn by includes/mcp/screen.php; this file supplies TrackShip's wording.
 *
 * @package TrackShip for WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'trackship_mcp_render_screen' ) ) {
	return;
}

trackship_mcp_render_screen(
	array(
		'title' => __( 'AI Assistant', 'trackship-for-woocommerce' ),
		'label' => __( 'TrackShip MCP', 'trackship-for-woocommerce' ),
		'param' => 'ai_section',
		'base_url' => add_query_arg(
			array(
				'page' => 'trackship-for-woocommerce',
				'tab' => 'ai-assistant',
			),
			admin_url( 'admin.php' )
		),
		'words' => array(
			'heading' => __( 'AI Assistant', 'trackship-for-woocommerce' ),
			'description' => __( 'Let an AI assistant read and update your shipment tracking, notifications and TrackShip settings. Changes save as you make them.', 'trackship-for-woocommerce' ),
			'connect_title' => __( 'Connect Claude to your shipments', 'trackship-for-woocommerce' ),
			'connect_sub' => __( 'Switch on, copy your link, paste it into Claude. Nothing to install.', 'trackship-for-woocommerce' ),
			'enable_title' => __( 'Enable the TrackShip connection', 'trackship-for-woocommerce' ),
			'writes_title' => __( 'Allow the AI to make changes', 'trackship-for-woocommerce' ),
			'writes_tooltip' => __( 'On by default, so this URL offers the same tools as WooCommerce MCP. Turn it off to make the URL read-only — the AI can then look up shipments and notifications but not change settings, mappings, or resync tracking.', 'trackship-for-woocommerce' ),
			'steps' => array(
				__( 'Open claude.ai and go to Settings, then Connectors.', 'trackship-for-woocommerce' ),
				__( 'Choose Add custom connector, give it any name, and paste your link.', 'trackship-for-woocommerce' ),
				__( 'Ask Claude: which shipments are late?', 'trackship-for-woocommerce' ),
			),
			'tools_empty' => __( 'WooCommerce MCP needs WordPress 6.9 or newer. The link above works either way.', 'trackship-for-woocommerce' ),
			'tools_note' => __( 'That is everything TrackShip adds — shipments, notifications, carrier mappings and settings. No customer payment details.', 'trackship-for-woocommerce' ),
			'woo_description' => __( "WooCommerce's own AI connection. Your TrackShip tools are already on it.", 'trackship-for-woocommerce' ),
			'woo_status_note' => __( 'WooCommerce MCP is switched off in WooCommerce, so this route is not answering at all. Your TrackShip link is unaffected.', 'trackship-for-woocommerce' ),
			'woo_key_sub' => __( 'Only needed if you connect through WooCommerce MCP instead of your TrackShip link.', 'trackship-for-woocommerce' ),
			'woo_scope_note' => __( 'A WooCommerce key opens the whole store — orders, products, customers — not just tracking. Your link above reaches TrackShip and nothing else.', 'trackship-for-woocommerce' ),
			'audit_empty' => __( 'Nothing yet. Once an AI assistant reads or updates your shipments, every action it takes will be listed here.', 'trackship-for-woocommerce' ),
			'help_text' => __( 'Learn how to configure TrackShip for best results.', 'trackship-for-woocommerce' ),
		),
	)
);

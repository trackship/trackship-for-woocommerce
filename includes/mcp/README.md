# TrackShip MCP

TrackShip's AI Assistant: an MCP server that lets Claude, ChatGPT, Cursor and other AI clients
read and update shipment tracking, notifications and TrackShip settings.

This is TrackShip's own connection. It is not shared with Zorem plugins (AST PRO and others run
their own `zorem-mcp` on a different URL), and nothing here should read or write their routes,
options or tables.

## Endpoint

```
https://<site>/wp-json/trackship/v1/mcp
```

There is one link and no credential in it. Whoever adds it signs in with their own store account (OAuth 2.1 + PKCE) and gets a personal token, sent as `Authorization: Bearer`, that can be revoked on its own. There is no token-in-URL link. The 401 challenge points clients at `/wp-json/trackship/v1/oauth-protected-resource` (TrackShip's own copy of the discovery document, which no other plugin can answer), and the `/.well-known/oauth-*` documents are answered on `setup_theme`, ahead of plugins that answer every `/.well-known/` address. The namespace is `Trackship_MCP_Server::REST_NS`. Every route (MCP, OAuth `authorize` /
`token` / `register` / `revoke`, the `/.well-known/oauth-*` discovery documents, the self-test
and the `.htaccess` repair) is built from it.

## Files

| File | What it does |
|---|---|
| `class-trackship-abilities.php` | **The tool definitions** (`tool_map()`), also registered as WordPress abilities for the WooCommerce MCP route |
| `class-trackship-settings-service.php` | Settings reads/writes used by the settings tools |
| `class-trackship-mcp-registry.php` | Turns `tool_map()` into MCP tools (`trackship-*` names), applies the writes switch and the `manage_woocommerce` check |
| `class-trackship-mcp-server.php` | The REST route and JSON-RPC handling (`initialize`, `tools/list`, `tools/call`) |
| `class-trackship-mcp-oauth.php` | OAuth 2.1 + PKCE sign-in and dynamic client registration |
| `class-trackship-mcp-keys.php` | Per-person tokens and registered clients (tables `trackship_mcp_keys`, `trackship_mcp_clients`) |
| `class-trackship-mcp-settings.php` | Options `trackship_mcp_settings`, `trackship_mcp_attempts` |
| `class-trackship-mcp-audit.php` | Audit log (table `trackship_mcp_audit`) |
| `class-trackship-mcp-woo-key.php` | Creates the WooCommerce REST key for the WooCommerce MCP route |
| `class-trackship-mcp-selftest.php`, `class-trackship-mcp-htaccess.php` | "Test my link" and the `.htaccess` repair for security plugins that block AI clients |
| `screen.php`, `ui-helpers.php`, `assets/` | The AI Assistant tab (rendered from `includes/views/ai-assistant.php`) |

Everything is loaded from `trackship-for-woocommerce.php` (`init()`). The assets are enqueued
by `trackship_mcp_enqueue_assets()` from `class-wc-trackship-actions.php`.

## Adding a tool

1. Add an entry to `Trackship_Abilities::tool_map()` with `label`, `description`,
   `input_schema`, `callback`, and `writes => true` if it changes data
   (`destructive => true` only if it deletes).
2. Add the `exec_*` callback in the same class. Clamp and sanitize every argument, and return an
   array or a `WP_Error` with a sentence the AI can act on.

The tool then appears on both routes: `trackship-<key>` on the TrackShip link and
`trackship/<key>` on WooCommerce MCP. Never rename a shipped tool; AI clients store the names.

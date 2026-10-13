# Configuration

All options live under `nowo_console_debug`:

| Option | Default | Description |
| --- | --- | --- |
| `enabled` | `true` | Master switch |
| `roles` | `['ROLE_CONSOLE_DEBUG']` | Any matching role enables collection |
| `console_method` | `log` | Browser console API: `log`, `info`, `warn`, `debug`, `error` |
| `label_prefix` | `[cdbg]` | Prefix in grouped console output |
| `csp_nonce_attribute` | `csp_nonce` | Request attribute read for a CSP nonce; when it holds a non-empty string, both injected `<script>` tags get `nonce="…"`. `null`/`''` disables |
| `shorten_paths` | `true` | Strip `kernel.project_dir` from file paths |
| `query_param` | `null` | When set, URL must include this query key |
| `gate_service` | `null` | Custom service id implementing `ConsoleDebugGateInterface` |

### Content-Security-Policy

The injected `<script>` tags are inline. Under a nonce-based CSP, publish the per-request nonce in a request attribute (nowo-tech convention: `csp_nonce`, set by `nowo-tech/http-hardening-bundle`) and the bundle stamps it on both tags. Use `csp_nonce_attribute` when your app stores the nonce under another attribute name.

When `gate_service` is set, it replaces the default enabled + role (+ optional query param) gate entirely.

Performance implications of each option (especially `enabled`, `roles`, and `query_param`) are described in [Performance](PERFORMANCE.md).

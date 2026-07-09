# Configuration

All options live under `nowo_console_debug`:

| Option | Default | Description |
| --- | --- | --- |
| `enabled` | `true` | Master switch |
| `roles` | `['ROLE_CONSOLE_DEBUG']` | Any matching role enables collection |
| `console_method` | `log` | Browser console API: `log`, `info`, `warn`, `debug`, `error` |
| `label_prefix` | `[cdbg]` | Prefix in grouped console output |
| `shorten_paths` | `true` | Strip `kernel.project_dir` from file paths |
| `query_param` | `null` | When set, URL must include this query key |
| `gate_service` | `null` | Custom service id implementing `ConsoleDebugGateInterface` |

When `gate_service` is set, it replaces the default enabled + role (+ optional query param) gate entirely.

Performance implications of each option (especially `enabled`, `roles`, and `query_param`) are described in [Performance](PERFORMANCE.md).

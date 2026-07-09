# Performance

This bundle is designed for **occasional, authorized debugging** in production-like environments. It is not a zero-cost no-op, but under normal conditions the overhead is **small and predictable**. There is no background worker, no database, and no I/O beyond the HTTP response you already send.

## Short answer

| Scenario | Server impact |
| --- | --- |
| Gate closed (`enabled: false`, wrong role, missing query param) | **Negligible** — one gate check per `cdbg()` call, then an immediate return |
| Gate open, no `cdbg()` / `{% cdbg %}` calls | **Negligible** — empty registry; response subscriber exits early |
| Gate open, a few debug calls on an HTML page | **Low** — backtrace + JSON normalization + small script injection |
| Gate open, many calls or huge payloads | **Noticeable** — CPU for normalization, memory for registry, larger HTML body |

For most applications, leaving a handful of `cdbg()` calls in code used only by trusted staff is acceptable. Spraying debug calls inside tight loops or dumping entire Twig contexts on every request is not.

## What runs on each request

### 1. Kernel response subscriber (always registered)

On every **main** HTTP response, `ConsoleDebugResponseSubscriber` runs at priority `-4096` (after the Web Profiler). Cost when the registry is empty:

- A few condition checks (`isMainRequest`, `registry->isEmpty()`)

**No** body read, **no** JSON encoding, **no** HTML mutation.

### 2. Each `cdbg()` / Twig call (gate closed)

When the gate returns `false`:

1. `ConsoleDebugGateInterface::isEnabled()` (default: master switch + Symfony authorization checks)
2. Return — **no** backtrace, **no** normalization, **no** registry write

Typical cost: microseconds per call, dominated by the security authorization checker when roles are evaluated.

### 3. Each `cdbg()` / Twig call (gate open)

When collection is allowed:

1. **Gate check** (same as above)
2. **`debug_backtrace()`** — limited to 6 frames for PHP calls; Twig tag uses template name/line instead
3. **`DebugValueNormalizer`** — walks arrays/objects up to **depth 8**; objects without `__toString` become `{ object, hash }` stubs (no full property dump like VarDumper)
4. **Registry append** — in-memory list for the current request

Cost scales with **number of calls** and **size/complexity of values**.

### 4. HTML response injection (gate open + registry not empty)

Only when **all** of the following are true:

- Registry has entries
- Response is HTML (`text/html` or `application/xhtml+xml`)
- Body is non-empty
- Script marker not already present

Then the subscriber:

1. Reads the **full response body** (`getContent()`)
2. **`json_encode`s** all collected entries (with hex-escaping for safe embedding)
3. Inserts a `<script type="application/json">` payload and a runner `<script>` before `</body>` (or appends if no `</body>`)
4. Writes the modified body back

Effects:

- Extra CPU proportional to response size + payload size
- **Larger HTML download** (payload duplicated in a JSON script block)
- Slightly higher memory peak (body string + JSON)

Non-HTML responses (JSON API, files, streams) **skip injection**, but any `cdbg()` calls during that request still paid collection cost and held data in memory until the request ends.

## Compared to alternatives

| Tool | Blocking? | Typical server cost |
| --- | --- | --- |
| `dd()` / `dump()` + die | Yes — stops the request | High impact by design |
| Web Profiler / `dump()` in HTML | No | Renders VarDumper HTML into the page (often heavier visually and in payload) |
| **`cdbg()`** | No | Serializes to JSON once; console rendering is client-side |
| Monolog / structured logging | No | Different channel; better for audit trails, not interactive inspection |

`cdbg()` is lighter than halting the request and usually lighter than embedding VarDumper output in HTML, but **heavier than doing nothing**.

## Recommendations

1. **Treat `cdbg()` like temporary instrumentation** — remove or reduce before merging hot paths, or keep calls in branches that only run for debug users.
2. **Use roles + optional `query_param`** in production so collection stays limited to trusted sessions.
3. **Set `enabled: false`** in environments where debug must be impossible (`when@prod` in config), even if stray calls remain in code.
4. **Avoid large dumps** — prefer scalars, small arrays, IDs. `{# full context #}{% cdbg %}` can serialize every Twig variable.
5. **Avoid high-frequency calls** — not inside `foreach` over thousands of rows; log a summary instead.
6. **API / JSON controllers** — if you leave `cdbg()` in an API action, data is still collected server-side even though nothing is injected; prefer HTML debug pages or logging for APIs.

## Configuration levers

```yaml
# config/packages/nowo_console_debug.yaml
when@prod:
  nowo_console_debug:
    enabled: false          # hard off in production

when@dev:
  nowo_console_debug:
    enabled: true
    roles: [ROLE_CONSOLE_DEBUG]
    # query_param: console_debug   # optional extra guard in staging/prod
```

See [Configuration](CONFIGURATION.md) for all options.

## Summary

- **No reseñable penalty** for anonymous users or when the bundle is disabled: one cheap gate check per call.
- **Low penalty** for authorized HTML debugging with a few small `cdbg()` calls — the intended use case.
- **Measurable penalty** only when you collect large payloads, call debug very often, or inject into large HTML responses.

Monitor response times and HTML size if you debug heavily in staging; adjust roles, `enabled`, and what you dump accordingly.

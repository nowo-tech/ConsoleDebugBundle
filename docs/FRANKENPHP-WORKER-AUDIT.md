# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/console-debug-bundle` (`symfony-bundle`) |
| Audited revision | `v1.0.13` (post-remediation) |
| Audit date | 2026-09-23 (remediation verified 2026-09-24) |
| Method | Manual review of every file under `src/` (services, gates, subscribers, Twig runtime/extension/node/parser, normalizer, DI extension, `Resources/config/*.yaml`, global `cdbg()` helper) |
| **Verdict** | ✅ **Viable under scenario B** (after remediation) — `ConsoleDebugRegistry` is cleared at the start of every main request and after every main response, so entries can no longer reach another request, with or without `kernel.reset` |
| Remediation (2026-09-23/24) | W-01/W-02 resolved: `ConsoleDebugHolderRequestSubscriber` clears the registry on main `kernel.request` (new optional `$registry` argument wired in `services.yaml`); `ConsoleDebugResponseSubscriber` clears it in `finally` for every main response. Tests: `tests/Unit/EventSubscriber/ConsoleDebugWorkerModeTest.php` + updated `ConsoleDebugResponseSubscriberTest` |

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | `ConsoleDebugRegistry::$entries` holds per-request debug payloads; cleared on every main `kernel.request` and after every main `kernel.response`, plus `reset()` |
| Static properties / `static` locals | ✅ | None; `ConsoleDebugHolder` uses a `$_SERVER` key instead of a static property |
| `ResetInterface` / `kernel.reset` coverage | ✅ | `nowo.console_debug.registry` implements `ResetInterface` and is tagged `kernel.reset`; `reset()` clears the only property |
| Request / user / locale captured in services | ✅ | Gates read `RequestStack` / `AuthorizationChecker` at call time; nothing captured in constructors |
| Superglobals, `$_ENV`, `putenv`, `ini_set`, `setlocale`, timezone | ⚠️ Info | `$_SERVER['__NOWO_CONSOLE_DEBUG']` holds the `ConsoleDebug` service for the global `cdbg()` helper; re-bound on every main request |
| Doctrine / EntityManager | ✅ N/A | No persistence |
| Output, headers, `exit`, shutdown functions | ✅ | None; the script is appended to the `Response` object |
| Resources (files, sockets, cURL) held open | ✅ | None |
| Memory growth across requests | ✅ | Entries are dropped at the end of each main response and at the next main request |
| Blocking I/O and timeouts | ✅ N/A | No I/O |
| Third-party static state | ✅ | Only Twig extension/runtime (stateless) |
| PHPStan FrankenPHP rulesets | ✅ | `ruleset-classic.neon` + `ruleset-worker.neon` included in `phpstan.neon.dist:2-3` |

A worker demo exists: `demo/symfony8/docker/frankenphp/Caddyfile:15` declares a `worker` block (`Caddyfile.dev` runs in classic mode).

## Services reviewed

| Service | Shared | Mutable state | Scenario A | Scenario B |
|---------|--------|---------------|------------|------------|
| `nowo.console_debug.registry` (`ConsoleDebugRegistry`) | yes, `kernel.reset` | `list<ConsoleDebugEntry> $entries` | ✅ (reset) | ✅ (cleared per main request/response, W-01, W-02 resolved) |
| `nowo.console_debug` (`ConsoleDebug`, public) | yes | none (`readonly` gate, registry, normalizer, config) | ✅ | ✅ (state lives in the registry) |
| `ConsoleDebugHolderRequestSubscriber` | yes | none; clears the registry on main `kernel.request` | ✅ | ✅ |
| `ConsoleDebugResponseSubscriber` | yes | none (`readonly` registry + config strings); clears the registry after each main response | ✅ | ✅ |
| `DebugValueNormalizer` | yes | none (`MAX_DEPTH` constant) | ✅ | ✅ |
| Gates: `nowo.console_debug.gate.enabled`, `.roles`, `.query_param` (+ `CompositeConsoleDebugGate` if wired by the app) | yes | none (`readonly`); evaluated per call | ✅ | ✅ |
| `ConsoleDebugRuntime` (`twig.runtime`), `ConsoleDebugTwigExtension` | yes | none | ✅ | ✅ |

`ConsoleDebugEntry` is an immutable value object. `TwigContextExtractor` has one pure static method. `CdbgNode` / `CdbgTokenParser` only run at template compile time.

## Findings

### W-01 — Debug entries survive to the next request and are injected into another user's HTML (High, scenario B only)

- **Where:** `src/ConsoleDebugRegistry.php:18` (`$entries`), cleared only by `clear()` / `reset()` (`:38-46`); `src/EventSubscriber/ConsoleDebugResponseSubscriber.php:45-62` (early returns), `:79` (the only runtime `clear()` call); registry tagged `kernel.reset` in `src/Resources/config/services.yaml:18-21`.
- **Worker impact:** `ConsoleDebug::record()` (`src/ConsoleDebug.php:58-77`) checks the gate when a value is logged and stores the normalized payload (which can contain user objects, Twig context, exception messages, file paths). The response subscriber injects whatever is in the registry and clears it **only** when it actually rewrites an HTML response. It returns without clearing for sub-requests, empty or `false` content (`StreamedResponse`, `BinaryFileResponse`), non-HTML content types (JSON APIs, XML, plain text), and pages that already contain the marker. It also does not re-check the gate at injection time. Under A, `services_resetter` calls `reset()` after the request, so nothing leaks. Under B, an authorized user calling `cdbg($user)` on a JSON endpoint leaves the entries in memory; the next HTML page served by the same worker — possibly to an anonymous visitor — receives them in the `<script type="application/json" data-nowo-console-debug-data>` tag. That is a cross-user data leak.
- **Recommendation:** clear the registry at the start of every main request (for example in `ConsoleDebugHolderRequestSubscriber::onKernelRequest()`, which already runs at priority 1024) and/or on `kernel.finish_request` / `kernel.terminate`, so correctness no longer depends on `kernel.reset`. Optionally call `$gate->isEnabled()` again before injecting.
- **Status:** Resolved — `src/EventSubscriber/ConsoleDebugHolderRequestSubscriber.php` clears the registry on every main `kernel.request` (priority 1024; sub-requests untouched) and `src/EventSubscriber/ConsoleDebugResponseSubscriber.php` clears it in a `finally` block for every main response, whether or not the script is injected. Re-checking the gate at injection time was not added: entries now always belong to the current request, whose gate decision was taken when they were recorded. Tests: `ConsoleDebugWorkerModeTest::testEntriesOfJsonRequestAreNotInjectedIntoNextUsersHtmlPage`, `testEntriesRecordedAfterResponseAreClearedAtNextMainRequest`, `testSubRequestDoesNotClearMainRequestEntries`.

### W-02 — Registry grows without bound on non-HTML traffic (Medium, scenario B only)

- **Where:** `src/ConsoleDebugRegistry.php:20-23` (`add()`), same early returns as W-01.
- **Worker impact:** when entries are logged on requests that never produce an injectable HTML response (APIs, file downloads, streamed responses), they are appended forever under B. Each entry holds a fully normalized copy of the dumped values (up to depth 8), so memory can grow quickly when `cdbg()` is left in a hot path.
- **Recommendation:** the fix for W-01 (clear on each main request) removes this growth as well.
- **Status:** Resolved — same change as W-01; at most one request's entries are held at any time.

No other findings. `ConsoleDebugHolder` stores the service in `$_SERVER['__NOWO_CONSOLE_DEBUG']` (`src/ConsoleDebugHolder.php:19-24`) and `ConsoleDebugHolderRequestSubscriber` re-binds it on each main `kernel.request` (`src/EventSubscriber/ConsoleDebugHolderRequestSubscriber.php:26-33`), so the global `cdbg()` helper keeps working whether or not FrankenPHP repopulates `$_SERVER` between requests. The stored value is the same container singleton, so it carries no per-request data. Outside HTTP (CLI, Messenger workers) no request event fires after boot; `NowoConsoleDebugBundle::boot()` sets the holder once and `cdbg()` falls back to a silent no-op if it is missing.

## Usage recommendations in worker mode

- Scenario B is supported by the bundle itself; keeping `services_resetter` enabled is still recommended for the application's own services.
- Keep `enabled: false` in production unless you really need it; the default role gate (`ROLE_CONSOLE_DEBUG`) protects collection, not injection.
- `cdbg()` calls in API/JSON controllers are discarded at the end of the response (they are never shown); remove them from hot paths and long-running loops.
- Custom `gate_service` implementations must stay stateless (read the request/user at call time, do not cache the decision in a property).
- A custom Twig or event listener that calls `cdbg()` after `kernel.response` priority `-4096` produces entries that are never shown; they are removed at the next main request.

## Re-audit triggers

Re-run this audit when a change adds: a property or cache to `ConsoleDebug`, the gates or the subscribers; a new place that reads or clears `ConsoleDebugRegistry`; a static property replacing the `$_SERVER` bridge; or any output outside the `Response` object.

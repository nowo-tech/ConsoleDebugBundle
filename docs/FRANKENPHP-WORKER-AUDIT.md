# FrankenPHP worker mode audit (kernel not reset between requests)

| Field | Value |
|-------|-------|
| Package | `nowo-tech/console-debug-bundle` (`symfony-bundle`) |
| Audited revision | `v1.0.13` (post-remediation); boundaries re-audited for 1.2.4 (REQ-RUNTIME-002, 2026-11-11) and 1.2.5 (console bookkeeping, audit NM, 2026-11-12) |
| Audit date | 2026-09-23 (remediation verified 2026-09-24) |
| Method | Manual review of every file under `src/` (services, gates, subscribers, Twig runtime/extension/node/parser, normalizer, DI extension, `Resources/config/*.yaml`, global `cdbg()` helper) |
| **Verdict** | ✅ **Viable under scenario B** (after remediation) — `ConsoleDebugRegistry` is cleared at the start of every main request, after every main response, at `kernel.terminate`, at every Messenger message boundary and at `console.terminate`, so entries can no longer reach another unit of work, with or without `kernel.reset` |
| Remediation (2026-09-23/24) | W-01/W-02 resolved: `ConsoleDebugHolderRequestSubscriber` clears the registry on main `kernel.request` (new optional `$registry` argument wired in `services.yaml`); `ConsoleDebugResponseSubscriber` clears it in `finally` for every main response. Tests: `tests/Unit/EventSubscriber/ConsoleDebugWorkerModeTest.php` + updated `ConsoleDebugResponseSubscriberTest` |
| Remediation (2026-11-11, 1.2.4) | W-03 resolved: new `ConsoleDebugBoundaryClearSubscriber` (Messenger, console, `kernel.terminate`); one-kernel worker test `tests/Integration/Worker/WorkerBoundariesTest.php` |
| Remediation (2026-11-12, 1.2.5, audits NM / NL2-v) | Unit-of-work bookkeeping in `Worker\UnitOfWorkFrames` at `PHP_INT_MAX`: console frames keyed by Command + input that save / restore the outer Messenger state, frames left failing by a throwing error listener healed at the next unit; no leak when another console listener throws or stops propagation |

## Table of contents

- [Execution model assumed](#execution-model-assumed)
- [Summary](#summary)
- [Services reviewed](#services-reviewed)
- [Findings](#findings)
  - [W-01 — Debug entries survive to the next request and are injected into another user's HTML (High, scenario B only)](#w-01--debug-entries-survive-to-the-next-request-and-are-injected-into-another-users-html-high-scenario-b-only)
  - [W-02 — Registry grows without bound on non-HTML traffic (Medium, scenario B only)](#w-02--registry-grows-without-bound-on-non-html-traffic-medium-scenario-b-only)
  - [W-03 — Registry not cleared in Messenger consumers and console commands (Low)](#w-03--registry-not-cleared-in-messenger-consumers-and-console-commands-low)
- [Boundary events](#boundary-events)
- [Worker test](#worker-test)
- [Host rule: entity managers](#host-rule-entity-managers)
- [Usage recommendations in worker mode](#usage-recommendations-in-worker-mode)
- [Igor findings triage (2026-11-11)](#igor-findings-triage-2026-11-11)
- [Re-audit triggers](#re-audit-triggers)

## Execution model assumed

FrankenPHP worker mode boots the Symfony kernel once per worker and serves many requests with the same container. This audit assumes the **strict** variant: the kernel is **not** rebooted between requests, so every shared service, static property and PHP global survives from one request to the next. Two scenarios are evaluated:

- **A — kernel not rebooted, `services_resetter` still runs:** services tagged `kernel.reset` (or implementing `ResetInterface`) are reset between requests.
- **B — no reset at all:** nothing is reset; any per-request state kept in a service leaks into the next request.

A bundle that is safe under **B** is safe under **A** and under classic mode / PHP-FPM.

**Precondition, precisely.** `FRANKENPHP_RESET_KERNEL=false` alone does not mean scenario B: with the stock Symfony kernel, `Kernel::handle()` → `boot()` still calls `services_resetter->reset()` before every request after the first. Scenario B happens where the resetter does not run: a host that removes, replaces or bypasses `services_resetter` (or drives `http_kernel` directly), Messenger consumers started with `messenger:consume --no-reset`, and long-running console processes. This bundle is correct in all of them.

## Summary

| Area | Status | Notes |
|------|--------|-------|
| Mutable state in shared services | ✅ | `ConsoleDebugRegistry::$entries` holds per-request debug payloads; cleared at every boundary (see [Boundary events](#boundary-events)), plus `reset()` |
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
| `nowo.console_debug.registry` (`ConsoleDebugRegistry`) | yes, `kernel.reset` | `list<ConsoleDebugEntry> $entries` | ✅ (reset) | ✅ (cleared at every boundary, W-01, W-02, W-03 resolved) |
| `nowo.console_debug` (`ConsoleDebug`, public) | yes | none (`readonly` gate, registry, normalizer, config) | ✅ | ✅ (state lives in the registry) |
| `ConsoleDebugHolderRequestSubscriber` | yes | none; clears the registry on main `kernel.request` | ✅ | ✅ |
| `ConsoleDebugResponseSubscriber` | yes | none (`readonly` registry + config strings); clears the registry after each main response | ✅ | ✅ |
| `ConsoleDebugBoundaryClearSubscriber` | yes | iterator of `IGNORE_ON_UNINITIALIZED_REFERENCE` references to the registry; a private `Worker\UnitOfWorkFrames` (since 1.2.5: console frames bounded by the nesting depth, Messenger in-flight flag, per-event verdicts in a `WeakMap`; no request data; `reset()` exists, not tagged `kernel.reset`); clears the registry at `kernel.terminate`, Messenger and console boundaries | ✅ | ✅ |
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

### W-03 — Registry not cleared in Messenger consumers and console commands (Low)

- **Where:** 1.0.0 to 1.2.3: the registry was emptied only by the HTTP subscribers (W-01) and by `kernel.reset`.
- **Worker impact:** a Messenger consumer or a console command has no `kernel.request` / `kernel.response`. With `messenger:consume --no-reset` (or a host without `services_resetter`) and a gate that is open there (custom `gate_service`, or the role gate with an impersonated token), `cdbg()` entries piled up across messages and commands for the life of the process. They were never injected into a response from those paths, so the impact is memory and data held longer than needed, not disclosure.
- **Status:** Resolved in 1.2.4 — `src/EventSubscriber/ConsoleDebugBoundaryClearSubscriber.php` empties the registry at `kernel.terminate`, `WorkerMessageReceivedEvent`, `WorkerMessageHandledEvent`, `WorkerMessageFailedEvent` and `console.terminate`. Tests: `tests/Integration/Worker/WorkerBoundariesTest.php`, `tests/Unit/EventSubscriber/ConsoleDebugBoundaryClearSubscriberTest.php`. Audit NM (1.2.5, Low): the depth counter could leak when a console listener between the `++` (4096) and the `--` (-4096) threw or stopped propagation, so the outermost command of a long-lived process stopped emptying the registry (reverse: a nested command emptied the outer unit mid-run); now `Worker\UnitOfWorkFrames` at `PHP_INT_MAX` (audit NL2-v design), tested with a real console `Application` (throwing terminate listener, unknown nested command, `console.command` stopped above 4096, self-recursive command with a throwing error listener, error listener at `PHP_INT_MIN`, throwing message-received listener inside a command) and `UnitOfWorkFramesTest`.

No other findings. `ConsoleDebugHolder` stores the service in `$_SERVER['__NOWO_CONSOLE_DEBUG']` (`src/ConsoleDebugHolder.php:19-24`) and `ConsoleDebugHolderRequestSubscriber` re-binds it on each main `kernel.request` (`src/EventSubscriber/ConsoleDebugHolderRequestSubscriber.php:26-33`), so the global `cdbg()` helper keeps working whether or not FrankenPHP repopulates `$_SERVER` between requests. The stored value is the same container singleton, so it carries no per-request data. Outside HTTP (CLI, Messenger workers) no request event fires after boot; `NowoConsoleDebugBundle::boot()` sets the holder once and `cdbg()` falls back to a silent no-op if it is missing. Entries recorded there are dropped at the Messenger and console boundaries (W-03).

## Usage recommendations in worker mode

- Scenario B is supported by the bundle itself, over HTTP, Messenger consumers and console commands; keeping `services_resetter` enabled is still recommended for the application's own services.
- Keep `enabled: false` in production unless you really need it; the default role gate (`ROLE_CONSOLE_DEBUG`) protects collection, not injection.
- `cdbg()` calls in API/JSON controllers are discarded at the end of the response (they are never shown); remove them from hot paths and long-running loops.
- Custom `gate_service` implementations must stay stateless (read the request/user at call time, do not cache the decision in a property).
- A custom Twig or event listener that calls `cdbg()` after `kernel.response` priority `-4096` produces entries that are never shown; they are removed at the next main request.

## Boundary events

| Boundary | Event | Priority | Subscriber |
|---|---|---|---|
| HTTP main request start | `kernel.request` (main only) | 1024 | `ConsoleDebugHolderRequestSubscriber` (also re-binds the `cdbg()` holder) |
| HTTP main response | `kernel.response` (main only, entries present) | -4096 | `ConsoleDebugResponseSubscriber` (injects, then clears in `finally`) |
| HTTP end | `kernel.terminate` | -4096 | `ConsoleDebugBoundaryClearSubscriber` |
| Messenger message start / end | `Symfony\Component\Messenger\Event\WorkerMessageReceivedEvent` / `WorkerMessageHandledEvent`, `WorkerMessageFailedEvent` (by name) | 4096 / -4096 | `ConsoleDebugBoundaryClearSubscriber` |
| Console command end | `console.terminate` (by name; `console.command` pushes, `console.error` marks and the early `console.terminate` removes the run's frame, all at `PHP_INT_MAX` since 1.2.5) | -4096 | `ConsoleDebugBoundaryClearSubscriber`, only when the outermost command ends and no Messenger message is in flight (`WorkerStoppedEvent` clears the in-flight flag) |
| Services resetter (when the host runs it) | `kernel.reset` | — | registry `reset()` (kept, not relied on) |

A command run in-process from another command or from a message handler (`Application::doRun()`) keeps the outer unit's entries (audit MI-C-I3; test `WorkerBoundariesTest::testNestedCommandsKeepTheOuterUnitsEntries`). One console command or one Messenger message is one unit of work: entries recorded inside a long loop of one command are held until the command terminates.

## Worker test

`tests/Integration/Worker/WorkerBoundariesTest.php` boots **one** kernel (`WorkerTestKernel`: FrameworkBundle + this bundle, an always-open custom gate, `services_resetter` replaced by a no-op as a host without the resetter would have):

- **HTTP:** 500 requests through a `KernelBrowser` with `disableReboot()`; user A records a private entry on JSON pages (never injected) and HTML pages, user B's HTML pages must never contain it, and the registry is empty after each request;
- **Messenger:** 500 messages through a real `Symfony\Component\Messenger\Worker` on the kernel's dispatcher, without `ResetServicesListener` (as `--no-reset`); every 7th handler fails;
- **Console:** 500 `Symfony\Component\Console\Application::run()` calls on the kernel's dispatcher.

Users A and B alternate; every non-HTTP unit must start with an empty registry. Memory margin: less than 1 MiB growth after a 50-unit warm-up. Without the 1.2.4 subscriber the Messenger and console cases fail (entries survive `WorkerMessageHandledEvent` and `console.terminate`). `reset()` and `clearRegistry()` are also tested directly (`tests/Unit/ConsoleDebugTest.php`, `tests/Unit/EventSubscriber/ConsoleDebugBoundaryClearSubscriberTest.php`).

Static checks: `composer igor` (0 KO, 0 WARN) and PHPStan with the FrankenPHP classic + worker rulesets pass.

## Host rule: entity managers

This bundle uses no Doctrine. A host that disables or bypasses the services resetter must still clear its **own** entity managers at every boundary (main request start or end, each Messenger message, each console command); otherwise the identity map serves stale rows (for example a downgraded permission or a soft-deleted item) across requests (REQ-RUNTIME-002 rule 7).

## Igor findings triage (2026-11-11)

`composer igor` (igor-php 0.10.1) reported 2 warnings on `main`; both are false positives, no code path changes
behaviour. Igor now runs in CI (`igor` job) and reports 0 KO / 0 WARN.

| Finding | Verdict | Resolution |
|---------|---------|------------|
| `ConsoleDebugHolder` reads / writes / unsets `$_SERVER['__NOWO_CONSOLE_DEBUG']` | False positive | The key holds the shared `ConsoleDebug` service (no request data); FrankenPHP resets `$_SERVER` per request and the main `kernel.request` re-binds it. Annotated `@igor-ignore`. |
| `ConsoleDebugRegistry::$entries` "mutated but not reset in reset()" | False positive | `reset()` already emptied it through `clear()`; igor does not follow the call. `reset()` now assigns `$this->entries = []` directly. |

## Re-audit triggers

Re-run this audit when a change adds: a property or cache to `ConsoleDebug`, the gates or the subscribers; a new place that reads or clears `ConsoleDebugRegistry`; a static property replacing the `$_SERVER` bridge; or any output outside the `Response` object.

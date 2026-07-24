# Console Debug Bundle — Baseline product specification

**Package**: `nowo-tech/console-debug-bundle`  
**Last audited**: 2026-07-24  
**Inventory**: [`code-inventory.md`](code-inventory.md)

## Overview

Console Debug Bundle provides **`cdbg()`**, a production-safe alternative to `dd()` that sends debug data to the **browser console** for authorized users only. It also exposes Twig `{{ cdbg() }}` / `{% cdbg %}` and is validated under PHPStan FrankenPHP classic + worker rulesets.

## Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | The bundle exposes a global **`cdbg(...$vars)`** helper that never stops request execution. |
| FR-02 | Debug entries are collected only when the active **gate** allows it (default: enabled + authenticated + role). |
| FR-03 | Each entry records **file**, **line**, optional **label**, and **normalized variables**. |
| FR-04 | On HTML main responses, the bundle injects a **`<script>`** that prints grouped console output. |
| FR-05 | Integrators may replace the gate via **`gate_service`** or require a **query parameter**. |
| FR-06 | Non-HTML responses (JSON, downloads) are never modified. |
| FR-07 | Twig templates may call **`{{ cdbg() }}`** or **`{% cdbg %}`** (with optional expressions); empty calls dump the Twig context. |
| FR-08 | Registry implements **`ResetInterface`** so FrankenPHP worker (and long-lived runtimes) clear entries between requests. |
| FR-09 | Global `cdbg()` resolves the service via a request-safe holder (no mutable static service property); re-bound on `kernel.request` for worker mode. |

## User scenarios

### US-01 — Authorized debugger in production

**Given** a user with `ROLE_CONSOLE_DEBUG`  
**When** they load an HTML page where code called `cdbg($data)`  
**Then** DevTools console shows grouped output with file, line, and `$data`.

### US-02 — Anonymous or unauthorized user

**Given** a user without the configured role  
**When** `cdbg()` is called during the request  
**Then** no data is collected and no script is injected.

### US-03 — Custom gate

**Given** `gate_service` pointing to a custom implementation  
**When** the custom gate returns false (e.g. missing query flag)  
**Then** `cdbg()` is ignored for that request.

### US-04 — Twig tag multitarget

**Given** a template with `{% cdbg user, roles %}`  
**When** an authorized user renders the page  
**Then** the console entry includes named keys for each expression.

### US-05 — FrankenPHP worker request boundary

**Given** FrankenPHP worker mode  
**When** request A collected debug entries and request B starts  
**Then** request B starts with an empty registry (reset / holder re-bind).

## Out of scope

- Replacing Symfony Web Profiler or VarDumper in dev.
- Injecting debug output into JSON/API responses.
- Persisting debug history server-side.

## Traceability

- FR-01 … FR-04: `tests/Unit/*`, demo `tests/Controller/DemoControllerTest.php`
- FR-05: `tests/Integration/DependencyInjection/ConsoleDebugExtensionIntegrationTest.php`
- FR-06: `tests/Unit/EventSubscriber/ConsoleDebugResponseSubscriberTest.php`
- FR-07: `tests/Unit/Twig/*`
- FR-08: `tests/Unit/ConsoleDebugTest.php` (`testRegistryResetClearsEntries`)
- FR-09: `tests/Unit/ConsoleDebugHolder*`, `ConsoleDebugHolderRequestSubscriberTest.php`

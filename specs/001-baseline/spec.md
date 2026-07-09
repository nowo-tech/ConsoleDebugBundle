# Console Debug Bundle — Baseline product specification

## Overview

Console Debug Bundle provides **`cdbg()`**, a production-safe alternative to `dd()` that sends debug data to the **browser console** for authorized users only.

## Functional requirements

| ID | Requirement |
| --- | --- |
| FR-01 | The bundle exposes a global **`cdbg(...$vars)`** helper that never stops request execution. |
| FR-02 | Debug entries are collected only when the active **gate** allows it (default: enabled + authenticated + role). |
| FR-03 | Each entry records **file**, **line**, optional **label**, and **normalized variables**. |
| FR-04 | On HTML main responses, the bundle injects a **`<script>`** that prints grouped console output. |
| FR-05 | Integrators may replace the gate via **`gate_service`** or require a **query parameter**. |
| FR-06 | Non-HTML responses (JSON, downloads) are never modified. |

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

## Out of scope

- Replacing Symfony Web Profiler or VarDumper in dev.
- Injecting debug output into JSON/API responses.
- Persisting debug history server-side.

## Traceability

- FR-01 … FR-04: `tests/Unit/*`, demo `tests/Controller/DemoControllerTest.php`
- FR-05: `tests/Integration/DependencyInjection/ConsoleDebugExtensionIntegrationTest.php`
- FR-06: `tests/Unit/EventSubscriber/ConsoleDebugResponseSubscriberTest.php`

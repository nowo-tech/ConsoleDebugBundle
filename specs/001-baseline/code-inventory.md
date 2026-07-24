# Code inventory — Console Debug Bundle (`src/`)

**Baseline spec**: [`spec.md`](spec.md)  
**Package**: `nowo-tech/console-debug-bundle`  
**Last audited**: 2026-07-24

100% inventory of production PHP under `src/`. Every file maps to at least one FR-* in the baseline product spec.

## Bundle entry

| File | Responsibility | Spec |
| --- | --- | --- |
| `../NowoConsoleDebugBundle.php` | Bundle entry; binds `ConsoleDebugHolder` on boot | FR-01, FR-09 |

## Core helpers

| File | Responsibility | Spec |
| --- | --- | --- |
| `ConsoleDebug.php` | Main service: gate check, backtrace, path shorten, registry write | FR-01, FR-02, FR-03 |
| `ConsoleDebugEntry.php` | Immutable DTO for one `cdbg()` call | FR-03 |
| `ConsoleDebugRegistry.php` | Request-scoped entry storage; `ResetInterface` for worker reset | FR-03, FR-08 |
| `ConsoleDebugHolder.php` | Request-safe bridge for global `cdbg()` (server bag, not mutable static) | FR-01, FR-09 |
| `Functions.php` | Global `cdbg()` function | FR-01 |

## Contract

| File | Responsibility | Spec |
| --- | --- | --- |
| `Contract/ConsoleDebugGateInterface.php` | Authorization gate contract | FR-02, FR-05 |

## Gates

| File | Responsibility | Spec |
| --- | --- | --- |
| `Gate/EnabledConsoleDebugGate.php` | Master config switch | FR-02 |
| `Gate/RoleBasedConsoleDebugGate.php` | Symfony Security role check | FR-02 |
| `Gate/QueryParamConsoleDebugGate.php` | Example query-param decorator | FR-05 |
| `Gate/CompositeConsoleDebugGate.php` | AND-composite of gates | FR-02, FR-05 |

## Serializer

| File | Responsibility | Spec |
| --- | --- | --- |
| `Serializer/DebugValueNormalizer.php` | JSON-safe value normalization | FR-03 |

## HTTP / events

| File | Responsibility | Spec |
| --- | --- | --- |
| `EventSubscriber/ConsoleDebugResponseSubscriber.php` | HTML script injection on RESPONSE | FR-04, FR-06 |
| `EventSubscriber/ConsoleDebugHolderRequestSubscriber.php` | Re-bind holder on `kernel.request` (FrankenPHP worker) | FR-09 |

## Twig

| File | Responsibility | Spec |
| --- | --- | --- |
| `Twig/ConsoleDebugTwigExtension.php` | Registers `cdbg` function + token parser | FR-07 |
| `Twig/ConsoleDebugRuntime.php` | Runtime for function/tag calls | FR-07 |
| `Twig/TwigContextExtractor.php` | Builds dumpable Twig context snapshot | FR-07 |
| `Twig/TokenParser/CdbgTokenParser.php` | Parses `{% cdbg %}` / multitarget expressions | FR-07 |
| `Twig/Node/CdbgNode.php` | Compiles tag into runtime calls with template/line | FR-07 |

## Dependency injection

| File | Responsibility | Spec |
| --- | --- | --- |
| `DependencyInjection/Configuration.php` | Config tree `nowo_console_debug` | FR-02, FR-05 |
| `DependencyInjection/ConsoleDebugExtension.php` | Parameter + gate wiring | FR-02, FR-05 |

## Resources (non-PHP)

| File | Responsibility | Spec |
| --- | --- | --- |
| `Resources/config/services.yaml` | Service definitions + aliases | FR-01 … FR-09 |
| `Resources/config/packages/nowo_console_debug.yaml` | Default package config | FR-02 |

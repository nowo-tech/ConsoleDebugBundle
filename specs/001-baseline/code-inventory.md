# Code inventory — Console Debug Bundle (`src/`)

100% inventory of production PHP under `src/`.

## Root helpers

| File | Responsibility |
| --- | --- |
| `ConsoleDebug.php` | Main service: gate check, backtrace, registry write |
| `ConsoleDebugEntry.php` | Immutable DTO for one `cdbg()` call |
| `ConsoleDebugRegistry.php` | Request-scoped entry storage |
| `ConsoleDebugHolder.php` | Static bridge for global `cdbg()` |
| `Functions.php` | Global `cdbg()` function |

## Contract

| File | Responsibility |
| --- | --- |
| `Contract/ConsoleDebugGateInterface.php` | Authorization gate contract |

## Gates

| File | Responsibility |
| --- | --- |
| `Gate/EnabledConsoleDebugGate.php` | Master config switch |
| `Gate/RoleBasedConsoleDebugGate.php` | Symfony Security role check |
| `Gate/QueryParamConsoleDebugGate.php` | Example query-param decorator |
| `Gate/CompositeConsoleDebugGate.php` | AND-composite of gates |

## Serializer

| File | Responsibility |
| --- | --- |
| `Serializer/DebugValueNormalizer.php` | JSON-safe value normalization |

## HTTP

| File | Responsibility |
| --- | --- |
| `EventSubscriber/ConsoleDebugResponseSubscriber.php` | HTML script injection on RESPONSE |

## Dependency injection

| File | Responsibility |
| --- | --- |
| `DependencyInjection/Configuration.php` | Config tree `nowo_console_debug` |
| `DependencyInjection/ConsoleDebugExtension.php` | Parameter + gate wiring |

## Resources

| File | Responsibility |
| --- | --- |
| `Resources/config/services.yaml` | Service definitions |
| `Resources/config/packages/nowo_console_debug.yaml` | Default package config |

## Bundle class

| File | Responsibility |
| --- | --- |
| `../NowoConsoleDebugBundle.php` | Bundle entry; sets `ConsoleDebugHolder` on boot |

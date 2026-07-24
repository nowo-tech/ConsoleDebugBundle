# Upgrading

## 1.0.4

No breaking public API changes for typical consumers of `cdbg()` / Twig helpers.

**Applications (FrankenPHP worker):** the registry now resets between requests (`ResetInterface`). The global `cdbg()` holder no longer uses a mutable static service property; ensure the bundle boots normally so `ConsoleDebugHolderRequestSubscriber` can re-bind on each request. No config changes required.

**require-dev / contributors:** PHPStan loads FrankenPHP classic + worker rulesets. Run `composer update nowo-tech/phpstan-frankenphp` (or a full `composer update`) in the bundle checkout.

**Demos only:**

- Default `FRANKENPHP_MODE` is now **`worker`** (was `classic` in 1.0.1–1.0.3). Set `classic` explicitly for per-request PHP / hot-reload, then recreate containers (`docker compose up -d`).
- Symfony 8 demo image uses **PHP 8.5** — rebuild the demo image (`make -C demo/symfony8 build` or equivalent).

See [Demo (FrankenPHP)](DEMO-FRANKENPHP.md).

## 1.0.3

No application or API changes. If you only consume the Packagist package, no upgrade steps.

## 1.0.2

No application or API changes. If you only consume the Packagist package, no upgrade steps.

**Demos / contributors:** each demo `.gitignore` now ignores `/.pnpm-store`. If you had a local store under `demo/**`, it remains on disk but is no longer tracked by git.

## 1.0.1

No application or API changes. If you only consume the Packagist package, no upgrade steps.

**Demos only:** FrankenPHP mode is no longer tied to `APP_ENV`. Set `FRANKENPHP_MODE=classic` or `worker` in the demo `.env`, then recreate containers (`docker compose up -d` / `make up`). From **1.0.4** the demo default is `worker`. See [Demo (FrankenPHP)](DEMO-FRANKENPHP.md).

## 1.0.0

Initial release. No prior versions.

Install with:

```bash
composer require nowo-tech/console-debug-bundle
```

Grant `ROLE_CONSOLE_DEBUG` (or your configured roles) to trusted users. See [Installation](INSTALLATION.md) and [Configuration](CONFIGURATION.md).

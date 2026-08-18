# Upgrading

## Table of contents

- [1.0.10](#1010)
- [1.0.8](#108)
- [1.0.7](#107)
- [1.0.6](#106)
- [1.0.5](#105)
- [1.0.4](#104)
- [1.0.3](#103)
- [1.0.2](#102)
- [1.0.1](#101)
- [1.0.0](#100)

## 1.0.10

No application or API changes. Demos only: Hot Reload Bundle `^1.4`; Symfony 8 is the only shipped demo (Symfony 6/7 demo apps removed).

## 1.0.8

No application or API changes. If you only consume the Packagist package, no upgrade steps.

**Contributors / CI:** Makefiles prefer `docker compose` (V2) and fall back to `docker-compose` (V1), using an absolute `docker` path so demos with a local `docker/` directory keep working under GNU Make. Shared monorepo `update-deps` Makefile fragments are optional so cloning only this repo (e.g. GitHub Actions) no longer errors on missing `../.scripts/*.mk`.

## 1.0.7

No breaking public API changes.

**Sample / recipe config:** the packaged default for `console_method` is **`log`** (was `info` in the sample YAML under `src/Resources/config/packages`). If you relied on copying that sample verbatim and preferred `info`, set `console_method: info` explicitly in your app config.

**Applications on Symfony 8:** package constraints again allow `symfony/*` `^7.4 || ^8.0` (a post-1.0.5 CS Fixer commit had narrowed them to `^7.4` only).

**Contributors / CI:** PHPUnit and CI set `SYMFONY_DEPRECATIONS_HELPER=max[direct]=0` (REQ-SF-005). Use `make demo-smoke` (or the Demo smoke workflow) to verify the Symfony 8 FrankenPHP demo returns HTTP 200.

## 1.0.6

No application or API changes. If you only consume the Packagist package, no upgrade steps.

**Contributors:** [CONTRIBUTING.md](CONTRIBUTING.md) notes that `nowo-tech/phpstan-frankenphp` is QA-only. GitHub repository About metadata (REQ-DOCS-018) was filled for Packagist discovery.

## 1.0.5

No breaking public API changes.

**Applications on Symfony 8:** package constraints again allow `symfony/*` `^7.4 || ^8.0` (a post-1.0.4 commit had narrowed them to `^7.4` only).

**Contributors:** README documentation links follow the org canonical order; [CONTRIBUTING.md](CONTRIBUTING.md) now links the Code of Conduct.

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

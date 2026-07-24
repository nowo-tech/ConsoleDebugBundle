# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)
- [[1.0.4] - 2026-07-24](#104---2026-07-24)
- [[1.0.3] - 2026-07-22](#103---2026-07-22)
- [[1.0.2] - 2026-07-22](#102---2026-07-22)
- [[1.0.1] - 2026-07-22](#101---2026-07-22)
- [[1.0.0] - 2026-07-09](#100---2026-07-09)

## [Unreleased]

## [1.0.4] - 2026-07-24

### Added

- **FrankenPHP worker safety** — `ConsoleDebugRegistry` implements `ResetInterface`; `ConsoleDebugHolder` stores the service in `$_SERVER` (no mutable static property) and re-binds on `kernel.request` via `ConsoleDebugHolderRequestSubscriber`.
- **PHPStan FrankenPHP** — `nowo-tech/phpstan-frankenphp` in require-dev with classic + worker rulesets (`phpstan.neon.dist`).
- **Code of Conduct** — Contributor Covenant at repository root (`CODE_OF_CONDUCT.md`).
- **Git hygiene (REQ-GIT-001)** — `.githooks`, `make setup-hooks` / `check-no-cursor-coauthor` / `strip-cursor-coauthor-from-history`, CI `git-hygiene` job, and [GITHUB_CI.md](GITHUB_CI.md).

### Changed

- **Composer** — Symfony component constraints allow `^7.4 || ^8.0`.
- **Demos** — `FRANKENPHP_MODE` default is **`worker`**; Symfony 8 demo image uses FrankenPHP **PHP 8.5** (`dunglas/frankenphp:1-php8.5-alpine`).
- **Makefile** — `down-dev`; `release-check` runs Cursor co-author history check first.

### Documentation

- README FrankenPHP Friendly Worker Mode banner and Code of Conduct / CI links.
- [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md), [CONTRIBUTING.md](CONTRIBUTING.md), [RELEASE.md](RELEASE.md), and Spec Kit baseline (`specs/001-baseline/`) updated for worker defaults and inventory.

### Tests

- Coverage restored to **100%** (Twig `{% cdbg %}` token parser/node + registry reset / holder edge cases).

## [1.0.3] - 2026-07-22

### Fixed

- **CI** — Symfony 8 matrix jobs use `composer config platform.php 8.4.1` (Composer treats `8.4` as `8.4.0`, which fails Symfony 8.1’s `php >=8.4.1`).
- **CI** — Code Style / Code Style Check / Coverage install Symfony 7.4 before resolving deps so PHP 8.2 runners work with a lockfile that may pin Symfony 8.1.

## [1.0.2] - 2026-07-22

### Fixed

- **Demos** — ignore `/.pnpm-store` in each demo `.gitignore` (REQ-GITIGNORE-003) and stop tracking the local pnpm store under `demo/**` so it is no longer versioned.

## [1.0.1] - 2026-07-22

### Changed

- **Demos (FrankenPHP)** — runtime mode is selected with `FRANKENPHP_MODE` (`classic` default, or `worker`) via `.env` / Compose; entrypoint lives in `docker/entrypoint.sh` instead of an inline Dockerfile script.
- **Docs** — [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md) documents classic vs worker switching and recreate after env changes.

### Chore

- Bump `friendsofphp/php-cs-fixer` (3.95.12 → 3.95.15).
- Bump `rector/rector` in lockfile (2.5.5 → 2.5.7).
- Bump GitHub Actions: `actions/checkout` (6 → 7), `actions/github-script` (7 → 9).

## [1.0.0] - 2026-07-09

### Added

- **`cdbg()` global helper** — non-blocking debug calls; execution continues (unlike `dd()`).
- **Role-based gate** — `ConsoleDebugGateInterface` with default enabled + Symfony Security roles.
- **Optional query parameter gate** — `QueryParamConsoleDebugGate` example and `query_param` config.
- **Custom gate service** — replace the default gate via `gate_service`.
- **HTML script injection** — `ConsoleDebugResponseSubscriber` appends console output before `</body>` on HTML responses.
- **Browser console output** — styled banner, expanded `console.group()` entries, file/line, optional label, timing offsets.
- **JSON-safe payload** — `<script type="application/json">` + `JSON.parse()` for safe embedding (namespaces, slashes, special chars).
- **`DebugValueNormalizer`** — scalars, arrays, enums, dates, throwables, `JsonSerializable`, objects (`{ object, hash }` or `__toString()`).
- **FQCN normalization** — PHP class names rendered as `App/Entity/User` in console (JSON-safe).
- **Twig integration** — `{{ cdbg() }}`, `{% cdbg %}`, full template context dump (like `{% dump %}`).
- **Symfony Flex recipe** — bundle registration and default config.
- **Demos** — FrankenPHP + Docker: `demo/symfony7` (port 8010), `demo/symfony8` (port 8011).
- **Documentation** — installation, configuration, usage, performance, security, upgrading, release process.
- **CI / release** — GitHub Actions (`ci.yml`, `release.yml`), 100% PHPUnit coverage.

### Configuration defaults

- `console_method`: `log` (visible in DevTools without filtering Info level).
- `label_prefix`: `[cdbg]`
- `roles`: `[ROLE_CONSOLE_DEBUG]`

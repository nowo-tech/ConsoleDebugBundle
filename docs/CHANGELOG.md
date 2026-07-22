# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)
- [[1.0.1] - 2026-07-22](#101---2026-07-22)
- [[1.0.0] - 2026-07-09](#100---2026-07-09)

## [Unreleased]

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

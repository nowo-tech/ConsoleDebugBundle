# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## Table of contents

- [[Unreleased]](#unreleased)
- [[1.2.2] - 2026-10-21](#122---2026-10-21)
- [[1.2.1] - 2026-10-13](#121---2026-10-13)
- [[1.2.0] - 2026-10-13](#120---2026-10-13)
- [[1.1.0] - 2026-10-13](#110---2026-10-13)
- [[1.0.15] - 2026-10-09](#1015---2026-10-09)
- [[1.0.14] - 2026-09-27](#1014---2026-09-27)
- [[1.0.13] - 2026-09-24](#1013---2026-09-24)
- [[1.0.12] - 2026-08-24](#1012---2026-08-24)
- [[1.0.11] - 2026-08-19](#1011---2026-08-19)
- [[1.0.10] - 2026-08-18](#1010---2026-08-18)
- [[1.0.9] - 2026-08-04](#109---2026-08-04)
- [[1.0.8] - 2026-07-29](#108---2026-07-29)
- [[1.0.7] - 2026-07-28](#107---2026-07-28)
- [[1.0.6] - 2026-07-27](#106---2026-07-27)
- [[1.0.5] - 2026-07-27](#105---2026-07-27)
- [[1.0.4] - 2026-07-24](#104---2026-07-24)
- [[1.0.3] - 2026-07-22](#103---2026-07-22)
- [[1.0.2] - 2026-07-22](#102---2026-07-22)
- [[1.0.1] - 2026-07-22](#101---2026-07-22)
- [[1.0.0] - 2026-07-09](#100---2026-07-09)

## [Unreleased]

### Security

- **Pages with injected debug data are `private, no-store`.** The subscriber injected the dumped values into the HTML
  without touching the cache headers; with stateless (token) authentication and a `public` / `s-maxage` page, a shared
  cache could store a page holding another user's dumped values.

### Changed

- **Demo (REQ-TWIG-003):** `security/login.html.twig` renders the login form through the child loop (`form_row(child)`) instead of `form_label`/`form_widget`/`form_errors` per field; the password hint moved to the `LoginFormType` `help` option.

## [1.2.2] - 2026-10-21

Dependency refresh, PHPDoc on public API, Rector PHP 8.2 floor, open-PR and raw-form release gates. See [UPGRADING](UPGRADING.md#to-122).

### Changed

- Dependencies refreshed (Composer and pnpm/npm lock files of the bundle and its demos, campaign 2026-10-19).
- `.php-cs-fixer.dist.php`: `new ParallelConfig(2)` (REQ-CS-001).
- PHPDoc on every public method in `src/` (REQ-CS-001); Rector targets the PHP 8.2 floor (REQ-CS-003, REQ-SF-001).
- `release-check` starts with `ensure-up` and runs the new `check-open-prs` (REQ-REL-003) and `check-no-raw-html-form` (REQ-TWIG-005) gates (REQ-MAKE-002); the co-author check accepts git worktrees.
- Demo: the login form renders its `_csrf_token` through Symfony Form (`csrf_token_id: authenticate`) instead of a raw `<input>` (REQ-DEMO-001).
- Documentation: wave-1 REQ compliance — README documentation list order and canonical FrankenPHP claim, canonical issue templates, CONTRIBUTING Cursor Agent settings (REQ-IDE-006), tables of contents (broken anchors fixed), Spec Kit baseline success criteria and validation commands, inventory 24/24 with coverage summary (`Resources/config/twig.yaml` mapped).

[1.2.2]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.2.2

## [1.2.1] - 2026-10-13

### Fixed

- `csp_nonce_attribute` is validated at container compile time: only a string (attribute name) or `null` is accepted; other scalars (e.g. `true`) are rejected with a clear configuration error instead of being cast to a bogus attribute name.

[1.2.1]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.2.1

## [1.2.0] - 2026-10-13

### Added

- CSP nonce support: the injected `<script>` tags carry `nonce="…"` when the request attribute named by the new `csp_nonce_attribute` option (default `csp_nonce`, the shared nowo-tech convention) holds a non-empty string. Set it to `null` to disable.

### Security

- `label_prefix` is JSON-encoded with `JSON_HEX_TAG | JSON_HEX_AMP` inside the inline script, so `<`/`>`/`&` in the prefix can never break out of the `<script>` element.

[1.2.0]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.2.0

## [1.1.0] - 2026-10-13

### Fixed

- Dumping a value with invalid UTF-8 or `INF`/`NAN` no longer throws a `JsonException` from `kernel.response` (HTTP 500): invalid bytes are replaced with U+FFFD and non-finite floats with `0`.
- The console debug gate is evaluated once per `cdbg()` call instead of twice.

### Changed

- `composer.json` declares `conflict: twig/twig <3.15` (the `{% cdbg %}` tag uses `Twig\Node\Nodes`, added in Twig 3.15); dev requirement bumped to `^3.15`.
- CI matrix and docs aligned with the actual `^7.4 || ^8.0` Symfony requirement (Symfony 6.4 / 7.0 cells and "6+" badge removed; PHP floor documented as 8.2).

[1.1.0]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.1.0

## [1.0.15] - 2026-10-09

### Fixed

- **Tests:** `GateTest` builds `RequestStack` via `push()` so the query-param gate tests run on the Symfony 6.4 CI matrix (the array constructor is Symfony 7.2+).

### Dependencies

- Dependabot: `symfony/framework-bundle` and `friendsofphp/php-cs-fixer` 3.95.27 (dev) lockfile bumps.
- Composer refresh: `symfony/security-core` 8.1.8; dev tooling `phpstan/phpstan` 2.3.1, `phpstan/phpstan-symfony` 2.1.0, `rector/rector` 2.7.0, `nowo-tech/phpstan-frankenphp` 1.2.3.
- Demo: Symfony 8.1.8, PHPUnit 13.4.1, `nowo-tech/password-toggle-bundle` 2.2.3; regenerated `config/reference.php`.

[1.0.15]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.0.15

## [1.0.14] - 2026-09-27

### Added

- **REQ-CS-008:** `igor-php/igor-php` (require-dev only), root `igor.json`, Composer/`Makefile` `igor` target, and `release-check` wiring for FrankenPHP worker-state audit.

### Changed

- **Worker safety (Igor):** justified `// @igor-ignore` annotations and/or `ResetInterface` / request-scoped fixes so `make igor` passes on package `src/`.

[1.0.14]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.0.14

## [1.0.13] - 2026-09-24

### Fixed

- **FrankenPHP worker mode (no kernel reset between requests):** `ConsoleDebugRegistry` entries could survive a request that did not produce an injectable HTML response (JSON, streamed, binary, already injected) and be injected into the next user's HTML page when `services_resetter` did not run. The registry is now cleared at the start of every main request (`ConsoleDebugHolderRequestSubscriber`, new optional `$registry` argument) and after every main response (`ConsoleDebugResponseSubscriber`, injected or not). See [FRANKENPHP-WORKER-AUDIT.md](FRANKENPHP-WORKER-AUDIT.md).

### Documentation

- Published worker audit (`docs/FRANKENPHP-WORKER-AUDIT.md`); linked from README and [DEMO-FRANKENPHP.md](DEMO-FRANKENPHP.md).

### Chore

- Align `GateTest` `RequestStack` construction with Symfony 7.2+ constructor (Rector).

## [1.0.12] - 2026-08-24

### Changed

- Raise minimum PHP to **8.2** and sync README badge (REQ-SF-001).
- **Docs:** PHP-FIG PSR evaluation (REQ-CS-007).

### Notes

- **No API or configuration changes** for integrators unless noted above.

[1.0.12]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.0.12

## [1.0.11] - 2026-08-19

### Security

- **CI:** run `composer audit --locked` after dependency install (REQ-SEC / P3).

## [1.0.10] - 2026-08-18

### Changed

- **Demos:** pin `nowo-tech/hot-reload-bundle` to `^1.4` with FrankenPHP Mercure/`hot_reload` (`dev`/`test` only).
- **Demos:** Symfony 8 only; Symfony 6/7 demo apps removed.

[1.0.10]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.0.10

## [1.0.9] - 2026-08-04

### Fixed
- **CI (Symfony 6.4):** build `RequestStack` via `push()` so query-param gate tests work without the 7.2+ constructor.

[1.0.9]: https://github.com/nowo-tech/ConsoleDebugBundle/releases/tag/v1.0.9

## [1.0.8] - 2026-07-29

### Changed

- **Makefiles (REQ-MAKE-010)** — prefer Docker Compose V2 (`docker compose`) with fallback to `docker-compose` V1 in root and demo Makefiles; resolve `docker` via absolute path so GNU Make does not collide with a local `docker/` directory when `PATH` has empty entries.
- **Makefiles (REQ-MAKE-009)** — monorepo `update-deps` includes are optional (`-include`) so standalone GitHub Actions checkouts do not fail.

## [1.0.7] - 2026-07-28

### Added

- **`make demo-smoke`** + `.github/workflows/demo-smoke.yml`: boot `demo/symfony8` and assert HTTP 200 (REQ-TEST-011).
- **REQ-SF-005**: `SYMFONY_DEPRECATIONS_HELPER=max[direct]=0` in `phpunit.xml.dist` and CI test jobs.

### Changed

- Default sample config `console_method` aligned to `log` (recipe + `src/Resources/config/packages`).
- **Composer** — restore Symfony component constraints to `^7.4 || ^8.0` (narrowed again by a post-1.0.5 CS Fixer commit).

### Fixed

- **CI** — Code Style job discards `composer.json` / `composer.lock` mutations from the temporary Symfony 7.4 pin before auto-committing CS Fixer changes (prevents `^7.4 || ^8.0` from being rewritten to `^7.4`).

### Documentation

- Table of contents on long docs (REQ-DOCS-005).
- `docs/PERFORMANCE.md`: English wording (REQ-DOCS-016).
- `docs/SECURITY.md`: logging/observability policy (REQ-OBS-001); AI audit Pass (conditional) (REQ-SEC-004); checklist heading aligned to 12.4.1.
- `docs/DEMO-FRANKENPHP.md`: demo-smoke command and URL.

## [1.0.6] - 2026-07-27

### Documentation

- **REQ-CS-005** — [CONTRIBUTING.md](CONTRIBUTING.md) documents PHPStan FrankenPHP classic + worker rulesets as require-dev only.
- **REQ-DOCS-018** — GitHub About: plain-text description, Packagist website, and topics (`php`, `symfony`, `symfony-bundle`, `frankenphp`, …).

## [1.0.5] - 2026-07-27

### Documentation

- **REQ-DOCS-002 / REQ-DOCS-015** — README `## Documentation` uses the canonical link order (Code of Conduct after Contributing, before Changelog); Performance and GitHub CI notes moved under `### Additional documentation`.
- **REQ-DOCS-015** — [CONTRIBUTING.md](CONTRIBUTING.md) includes a Code of Conduct section linking to `../CODE_OF_CONDUCT.md`.

### Changed

- **Composer** — restore Symfony component constraints to `^7.4 || ^8.0` (regressed after v1.0.4).
- **Tests** — `RequestStack` construction in `GateTest` aligned with Symfony constructor style (Rector).
- **Chore** — refresh `composer.lock` (php-cs-fixer, phpstan, rector, Symfony 8.x lock pins).

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

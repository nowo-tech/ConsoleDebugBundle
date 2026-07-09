# Console Debug Bundle

[![CI](https://github.com/nowo-tech/ConsoleDebugBundle/actions/workflows/ci.yml/badge.svg)](https://github.com/nowo-tech/ConsoleDebugBundle/actions/workflows/ci.yml) [![Packagist Version](https://img.shields.io/packagist/v/nowo-tech/console-debug-bundle.svg?style=flat)](https://packagist.org/packages/nowo-tech/console-debug-bundle) [![Packagist Downloads](https://img.shields.io/packagist/dt/nowo-tech/console-debug-bundle.svg)](https://packagist.org/packages/nowo-tech/console-debug-bundle) [![License](https://img.shields.io/badge/license-MIT-blue.svg)](LICENSE) [![PHP](https://img.shields.io/badge/PHP-8.1%2B-777BB4?logo=php)](https://php.net) [![Symfony](https://img.shields.io/badge/Symfony-6%2B%20%7C%207.4%20%7C%208.0%20%7C%208.1%2B-000000?logo=symfony)](https://symfony.com) [![GitHub stars](https://img.shields.io/github/stars/nowo-tech/console-debug-bundle.svg?style=social&label=Star)](https://github.com/nowo-tech/ConsoleDebugBundle) [![Coverage](https://img.shields.io/badge/Coverage-100%25-brightgreen)](#tests-and-coverage)

> ⭐ **Found this useful?** [Install from Packagist](https://packagist.org/packages/nowo-tech/console-debug-bundle) · Give it a **star** on [GitHub](https://github.com/nowo-tech/ConsoleDebugBundle) so more developers can find it.

**Console Debug Bundle** — Production-safe browser console debugging with **`cdbg()`**, similar in spirit to Symfony's `dd()` but non-blocking: it collects file, line, and variable data server-side and injects a `<script>` that prints grouped `console.log()` output for authorized users only. Tested on Symfony **7.4**, **8.0**, and **8.1** (also compatible with Symfony 6.x and 7.0–7.3) · PHP 8.1+ (Symfony 8.x requires PHP 8.4+).

## Features

- **`cdbg()` helper** — Drop-in debug calls anywhere in PHP; execution continues (no dump-and-die).
- **Role-gated** — Only authenticated users with configured roles see output, even in production.
- **Rich context** — Captures caller file, line, optional label, and normalized variable payloads.
- **HTML injection** — Appends a JSON payload and runner script before `</body>` on HTML responses.
- **Custom gate service** — Replace or extend authorization with route/query-param logic.

## Installation

```bash
composer require nowo-tech/console-debug-bundle
```

With **Symfony Flex**, the recipe registers the bundle and adds config. Without Flex, see [docs/INSTALLATION.md](docs/INSTALLATION.md).

**Manual registration** in `config/bundles.php`:

```php
return [
  // ...
  Nowo\ConsoleDebugBundle\NowoConsoleDebugBundle::class => ['all' => true],
];
```

Grant a debug role to trusted users:

```yaml
security:
  role_hierarchy:
    ROLE_CONSOLE_DEBUG: ROLE_USER
```

## Configuration

```yaml
nowo_console_debug:
  enabled: true
  roles:
    - ROLE_CONSOLE_DEBUG
  console_method: log
  label_prefix: '[cdbg]'
  shorten_paths: true
  query_param: console_debug
  # gate_service: App\ConsoleDebug\AppConsoleDebugGate
```

## Usage

```php
use function Nowo\ConsoleDebugBundle\cdbg;

public function show(Request $request): Response
{
    cdbg('items snapshot', $items, $request->query->all());

    return $this->render('page.html.twig', ['items' => $items]);
}
```

Open DevTools → Console on the rendered HTML page. Each `cdbg()` call appears as an expanded group with file, line, and values.

### Twig (requires `twig/twig`)

Same behaviour as Symfony's native `dump`, but output goes to the browser console:

```twig
{# full Twig context (macros/templates excluded), like {% dump %} #}
{% cdbg %}

{# one or more variables, like {{ dump(user) }} #}
{{ cdbg(user) }}
{% cdbg user, items %}
```

When called empty, the label is `twig context` and the template name/line is used as the source location.

## Documentation

- [Installation](docs/INSTALLATION.md)
- [Configuration](docs/CONFIGURATION.md)
- [Usage](docs/USAGE.md)
- [Performance](docs/PERFORMANCE.md)
- [Contributing](docs/CONTRIBUTING.md)
- [Changelog](docs/CHANGELOG.md)
- [Upgrading](docs/UPGRADING.md)
- [Release](docs/RELEASE.md)
- [Security](docs/SECURITY.md)
- [Engram](docs/ENGRAM.md)
- [Spec-driven development](docs/SPEC-DRIVEN-DEVELOPMENT.md)
- [GitHub Spec Kit](docs/SPEC-KIT.md)

### Additional documentation

- [Demo (FrankenPHP)](docs/DEMO-FRANKENPHP.md)

## Requirements

- PHP `>=8.1` (<8.6); **Symfony 8.0** and **8.1** require **PHP 8.4+**
- Symfony **7.4**, **8.0**, or **8.1** (minimum supported minors; also works on Symfony 6.x and 7.0–7.3 via `composer.json` constraints)
- `symfony/security-bundle` (or equivalent) in your application for role-based gates

## Development

```bash
make up
make install
make test
make cs-check
make phpstan
make release-check
```

## Demo

- `demo/symfony7` — Symfony **7.4**, host port **8010** by default (`PORT` in `.env`)
- `demo/symfony8` — Symfony **8.1** (PHP 8.4+), host port **8011** by default

Each demo runs **FrankenPHP + Caddy** in Docker. Login as `debugger / debug` and visit `/debug` to see `cdbg()` in the browser console. See [docs/DEMO-FRANKENPHP.md](docs/DEMO-FRANKENPHP.md).

Global demo commands: `make -C demo help` (e.g. `make -C demo up-symfony8`).

## Tests and coverage

- Tests: PHPUnit (PHP)
- PHP: 100%

## License and author

MIT · [Nowo.tech](https://nowo.tech) · [Héctor Franco Aceituno](https://github.com/HecFranco)

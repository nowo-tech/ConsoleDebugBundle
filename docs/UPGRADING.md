# Upgrading

## 1.0.1

No application or API changes. If you only consume the Packagist package, no upgrade steps.

**Demos only:** FrankenPHP mode is no longer tied to `APP_ENV`. Set `FRANKENPHP_MODE=classic` (default) or `worker` in the demo `.env`, then recreate containers (`docker compose up -d` / `make up`). See [Demo (FrankenPHP)](DEMO-FRANKENPHP.md).

## 1.0.0

Initial release. No prior versions.

Install with:

```bash
composer require nowo-tech/console-debug-bundle
```

Grant `ROLE_CONSOLE_DEBUG` (or your configured roles) to trusted users. See [Installation](INSTALLATION.md) and [Configuration](CONFIGURATION.md).

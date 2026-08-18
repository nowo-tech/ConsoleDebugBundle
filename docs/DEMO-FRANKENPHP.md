# Demo with FrankenPHP

This bundle includes runnable demos with FrankenPHP in:

- `demo/symfony8` — Symfony **8.1** (FrankenPHP PHP **8.5**, REQ-DEMO-010)

Each demo uses:

- Caddy on HTTP `:80` inside the container
- **`Caddyfile`**: **worker** mode (`php_server { worker ... }`) — selected when `FRANKENPHP_MODE=worker` (**default**)
- **`Caddyfile.dev`**: classic `php_server` (**no worker**) — selected when `FRANKENPHP_MODE=classic`

**Default development stack:** `docker-compose.yml` sets **`APP_ENV=dev`**, **`APP_DEBUG=1`**, and **`FRANKENPHP_MODE=worker`**, and mounts **`docker/php-dev.ini`**. Use `FRANKENPHP_MODE=classic` when you need one PHP process per request (hot-reload / first-boot before `composer install`).

## Table of contents

- [Quick start](#quick-start)
- [Development stack in demos](#development-stack-in-demos)
- [Switching classic vs worker (`FRANKENPHP_MODE`)](#switching-classic-vs-worker-frankenphp_mode)
- [Production](#production)
- [Troubleshooting](#troubleshooting)
- [Demo smoke (REQ-TEST-011)](#demo-smoke-req-test-011)

## Quick start

From the bundle root:

```bash
make -C demo up-symfony8
# or
make -C demo up-symfony8
```

Then open:

- Symfony 7.4: `http://localhost:8010`
- Symfony 8.1: `http://localhost:8011`

## Development stack in demos

Both demos include:

- **Symfony Debug** (`symfony/debug-bundle`)
- **Symfony Web Profiler** (`symfony/web-profiler-bundle`)
- **Nowo Twig Inspector** (`nowo-tech/twig-inspector-bundle`) and **Nowo Hot Reload** (`nowo-tech/hot-reload-bundle`) — required together on FrankenPHP demos (dev/test only; Caddyfile Mercure + `hot_reload`, plus `worker { watch }` in worker mode). Do not enable Hot Reload in production.
- **`APP_DEBUG=1`** in `.env.example`
- **Nowo Twig Inspector** (`nowo-tech/twig-inspector-bundle`)

Example `config/bundles.php` (same in **symfony7** and **symfony8** demos):

```php
<?php

declare(strict_types=1);

return [
    Symfony\Bundle\FrameworkBundle\FrameworkBundle::class     => ['all' => true],
    Symfony\Bundle\TwigBundle\TwigBundle::class               => ['all' => true],
    Symfony\Bundle\DebugBundle\DebugBundle::class             => ['dev' => true, 'test' => true],
    Symfony\Bundle\WebProfilerBundle\WebProfilerBundle::class => ['dev' => true, 'test' => true],
    Nowo\TwigInspectorBundle\NowoTwigInspectorBundle::class   => ['dev' => true, 'test' => true],
    Nowo\ConsoleDebugBundle\NowoConsoleDebugBundle::class   => ['all' => true],
];
```

## Switching classic vs worker (`FRANKENPHP_MODE`)

Demos select the FrankenPHP runtime via **`FRANKENPHP_MODE`** in `.env` / `.env.example` (not a Dockerfile `ENV`):

| Value | Behaviour |
| --- | --- |
| **`worker`** (default) | Keep the worker Caddyfile (`php_server { worker ... }`) |
| **`classic`** | Entrypoint copies `Caddyfile.dev` (plain `php_server`, hot-reload / first-boot friendly) |

Compose passes `FRANKENPHP_MODE=${FRANKENPHP_MODE:-worker}` into the PHP service. After changing `.env`, run `docker compose up -d` (or `make up`) so the container is **recreated** — a plain `restart` does not reload env. No image rebuild is required.

## Production

For a production-like run, keep `FRANKENPHP_MODE=worker` (default), set `APP_ENV=prod` / `APP_DEBUG=0` as needed, and ensure Composer dependencies are installed before serving traffic.

## Troubleshooting

- If app does not respond, run `make -C demo/symfony8 logs` or `make -C demo/symfony8 logs`.
- If routes/config changed, run `make -C demo/symfony8 cache-clear` (or `symfony8`).
- If dependencies are outdated, run `make -C demo/symfony8 update-bundle` (or `symfony8`).
- Unknown `FRANKENPHP_MODE` values fail fast in `docker/entrypoint.sh`.

## Demo smoke (REQ-TEST-011)

Automated smoke proves the Symfony 8 FrankenPHP demo boots and returns **HTTP 200**:

```bash
make demo-smoke
```

This runs `make -C demo/symfony8 up`, then `curl` against `http://localhost:8011/` (or `PORT` from `.env` / `.env.example`). CI runs the same target via `.github/workflows/demo-smoke.yml` (schedule / tag / workflow_dispatch). For both demos in sequence, use `make -C demo release-verify`.

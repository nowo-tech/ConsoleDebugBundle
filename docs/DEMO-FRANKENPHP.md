# Demo with FrankenPHP

This bundle includes runnable demos with FrankenPHP in:

- `demo/symfony7` — Symfony **7.4** (FrankenPHP PHP **8.4**)
- `demo/symfony8` — Symfony **8.1** (FrankenPHP PHP **8.5**, REQ-DEMO-010)

Each demo uses:

- Caddy on HTTP `:80` inside the container
- **`Caddyfile`**: **worker** mode (`php_server { worker ... }`) — selected when `FRANKENPHP_MODE=worker` (**default**)
- **`Caddyfile.dev`**: classic `php_server` (**no worker**) — selected when `FRANKENPHP_MODE=classic`

**Default development stack:** `docker-compose.yml` sets **`APP_ENV=dev`**, **`APP_DEBUG=1`**, and **`FRANKENPHP_MODE=worker`**, and mounts **`docker/php-dev.ini`**. Use `FRANKENPHP_MODE=classic` when you need one PHP process per request (hot-reload / first-boot before `composer install`).

## Quick start

From the bundle root:

```bash
make -C demo up-symfony7
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

- If app does not respond, run `make -C demo/symfony7 logs` or `make -C demo/symfony8 logs`.
- If routes/config changed, run `make -C demo/symfony7 cache-clear` (or `symfony8`).
- If dependencies are outdated, run `make -C demo/symfony7 update-bundle` (or `symfony8`).
- Unknown `FRANKENPHP_MODE` values fail fast in `docker/entrypoint.sh`.

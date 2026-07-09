# Demo with FrankenPHP

This bundle includes runnable demos with FrankenPHP in:

- `demo/symfony7` — Symfony **7.4**
- `demo/symfony8` — Symfony **8.1** (PHP **8.4+**)

Each demo uses:

- Caddy on HTTP `:80` inside the container
- **`Caddyfile`** (production image / `APP_ENV` not `dev`): **worker** mode for throughput
- **`Caddyfile.dev`**: classic `php_server` (**no worker**) — used when the entrypoint runs with **`APP_ENV=dev`** (default in `docker-compose`)

**Default development stack:** `docker-compose.yml` sets **`APP_ENV=dev`** and **`APP_DEBUG=1`**, mounts **`docker/frankenphp/Caddyfile.dev`** and **`docker/php-dev.ini`**. The container entrypoint copies `Caddyfile.dev` over the active Caddyfile so you get **one PHP process per request**, not workers.

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

## Troubleshooting

- If app does not respond, run `make -C demo/symfony7 logs` or `make -C demo/symfony8 logs`.
- If routes/config changed, run `make -C demo/symfony7 cache-clear` (or `symfony8`).
- If dependencies are outdated, run `make -C demo/symfony7 update-bundle` (or `symfony8`).

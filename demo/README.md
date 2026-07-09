# Console Debug Bundle Demo

This directory contains runnable demos for:

- `symfony7` — Symfony **7.4** (http://localhost:8010)
- `symfony8` — Symfony **8.1**, PHP **8.4+** (http://localhost:8011)

## Quick start

```bash
make up-symfony7
# or
make up-symfony8
```

Each demo includes:

- FrankenPHP with Caddy (HTTP on `:80` inside container). Default **`APP_ENV=dev`** uses **Caddyfile.dev** (no PHP worker); see [docs/DEMO-FRANKENPHP.md](../docs/DEMO-FRANKENPHP.md) for production (worker) vs development.
- Web Profiler enabled in `dev`
- Nowo Twig Inspector enabled in `dev`
- Dedicated `Makefile` per demo (`demo/symfony7/Makefile`, `demo/symfony8/Makefile`)

## Release checks

```bash
make release-check
```

This runs PHPUnit in each demo (smoke), updates the path bundle, and verifies startup + HTTP healthcheck.

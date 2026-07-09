# Contributing

## Development setup

1. Clone the repository.
2. Start the dev container: `make up` then `make install`.
3. Run tests: `make test`, `make cs-check`, `make phpstan`.
4. Pre-release: `make release-check`.

## Code style

- PHP-CS-Fixer (PSR-12 + Symfony): `make cs-fix` / `make cs-check`.
- PHPDoc and Markdown docs in **English**.
- Strict types in all PHP files.

## Pull requests

- Target the default branch.
- Ensure `make release-check` passes.
- Keep the changelog and docs updated when behaviour or config changes.

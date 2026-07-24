# Contributing

## Development setup

1. Clone the repository.
2. Install git hooks once: `make setup-hooks` (REQ-MAKE-006 / REQ-GIT-001).
3. Start the dev container: `make up` then `make install`.
4. Run tests: `make test`, `make cs-check`, `make phpstan`.
5. Pre-release: `make release-check`.

## Code style

- PHP-CS-Fixer (PSR-12 + Symfony): `make cs-fix` / `make cs-check`.
- PHPDoc and Markdown docs in **English**.
- Strict types in all PHP files.

## Pull requests

- Target the default branch.
- Ensure `make release-check` passes.
- Keep the changelog and docs updated when behaviour or config changes.

## Git hooks (REQ-GIT-001)

Do **not** add `Co-authored-by: Cursor` or `cursoragent@cursor.com` trailers to commit messages.

```bash
make setup-hooks
make check-no-cursor-coauthor
```

`make setup-hooks` sets `core.hooksPath` to `.githooks` (includes `pre-commit` for a light REQ-GIT-001 check and `commit-msg` to strip Cursor co-author trailers). Run it once per clone before your first commit.

If CI fails because trailers are already on the remote, see [GITHUB_CI.md](GITHUB_CI.md) (REQ-GIT-001) and run `make strip-cursor-coauthor-from-history` before `git push --force-with-lease`.

.PHONY: help up down composer-install build install shell logs test test-coverage cache-clear update-bundle ensure-up restart verify assets

# Prefer Compose V2; resolve docker via absolute path so GNU Make does not hit a local ./docker/ dir when PATH has empty entries (REQ-MAKE-010).
DOCKER_BIN := $(shell command -v docker 2>/dev/null)
COMPOSE := $(if $(DOCKER_BIN),$(shell $(DOCKER_BIN) compose version >/dev/null 2>&1 && echo "$(DOCKER_BIN) compose" || echo "docker-compose"),docker-compose)
DC := :; $(COMPOSE)

help:
	@echo "Symfony 8 Demo - Console Debug Bundle"
	@echo "  make up             Start containers + install + build assets"
	@echo "  make down           Stop containers"
	@echo "  make restart        Restart containers"
	@echo "  make build          Build image (no cache)"
	@echo "  make install        Install Composer + pnpm dependencies"
	@echo "  make assets         pnpm install + Vite build (public/build/)"
	@echo "  make shell          Open shell in php container"
	@echo "  make logs           Stream container logs"
	@echo "  make test           Run tests"
	@echo "  make test-coverage  Run tests with coverage"
	@echo "  make update-bundle  Update nowo-tech/console-debug-bundle and clear cache"

# Composer install without starting FrankenPHP. Fresh CI checkouts have no vendor/;
# worker mode would crash index.php before `exec composer install` can run.
composer-install:
	@echo "Installing Composer dependencies (ephemeral container)..."
	$(DC) run --rm --no-deps --entrypoint "" -e COMPOSER_MEMORY_LIMIT=-1 php \
		composer install --no-interaction

up:
	@if [ ! -f .env ]; then cp .env.example .env 2>/dev/null || true; fi
	@$(MAKE) composer-install
	$(DC) up -d
	@echo "Waiting for FrankenPHP to stay up..."
	@ok=0; \
	for i in 1 2 3 4 5 6 7 8 9 10 11 12 13 14 15; do \
		if $(COMPOSE) exec -T php true 2>/dev/null; then ok=1; break; fi; \
		sleep 2; \
	done; \
	if [ "$$ok" != "1" ]; then \
		echo "Demo php container failed to stay up. Recent logs:"; \
		$(COMPOSE) logs --tail=100 php || true; \
		exit 1; \
	fi
	@$(DC) exec -T php php bin/console assets:install public --symlink --no-interaction 2>/dev/null || true
	@$(MAKE) assets
	@PORT=$$(grep "^PORT=" .env 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	[ -z "$$PORT" ] && PORT=$$(grep "^PORT=" .env.example 2>/dev/null | cut -d= -f2 | tr -d '\r'); \
	echo "Demo started at: http://localhost:$$PORT"
ensure-up:
	@if ! $(COMPOSE) exec -T php true 2>/dev/null; then \
		echo "Starting demo container..."; \
		if [ ! -d vendor ]; then $(MAKE) composer-install; fi; \
		$(DC) up -d; \
		sleep 3; \
		if ! $(COMPOSE) exec -T php true 2>/dev/null; then \
			echo "Demo php container failed to stay up. Recent logs:"; \
			$(COMPOSE) logs --tail=100 php || true; \
			exit 1; \
		fi; \
	fi

down:
	$(COMPOSE) down

restart: ensure-up
	$(COMPOSE) restart

build:
	$(COMPOSE) build --no-cache

install: ensure-up
	$(COMPOSE) exec -T -e COMPOSER_MEMORY_LIMIT=-1 php composer install --no-interaction
	$(COMPOSE) exec -T php php bin/console assets:install public --symlink --no-interaction 2>/dev/null || true
	$(COMPOSE) exec -T php sh -c 'test -f package.json && pnpm install || true'

assets: ensure-up
	$(COMPOSE) exec -T php sh -c 'pnpm install && pnpm run build'

shell: ensure-up
	$(COMPOSE) exec php sh

logs:
	$(COMPOSE) logs -f

test: ensure-up
	$(COMPOSE) exec -T -e APP_ENV=test php php bin/phpunit

test-coverage: ensure-up
	$(COMPOSE) exec -T -e APP_ENV=test php php bin/phpunit --coverage-text

cache-clear: ensure-up
	$(COMPOSE) exec -T php php bin/console cache:clear

update-bundle: ensure-up
	$(COMPOSE) exec -T php composer update nowo-tech/console-debug-bundle --no-interaction
	$(COMPOSE) exec -T php php bin/console cache:clear --env=dev

verify: ensure-up
	@code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$${PORT:-8011} 2>/dev/null || echo "000"); \
	if [ "$$code" != "200" ]; then echo "Healthcheck failed: $$code"; exit 1; fi; \
	echo "Healthcheck OK ($$code)"


# REQ-MAKE-008: update-deps (REQ-MAKE-008)
SERVICE_PHP := php
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/../..)
# Optional: monorepo helper absent on standalone GitHub Actions checkout (REQ-MAKE-009).
-include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-update-deps.mk

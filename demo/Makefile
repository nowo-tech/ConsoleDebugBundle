DEMOS := symfony7 symfony8

.PHONY: help clean test-all test-coverage-all verify-all release-check release-verify update-bundle-all
.PHONY: $(foreach demo,$(DEMOS),up-$(demo) down-$(demo) restart-$(demo) build-$(demo) install-$(demo) update-bundle-$(demo) shell-$(demo) logs-$(demo) test-$(demo) test-coverage-$(demo) verify-$(demo))
.PHONY: up down restart build install update-bundle test shell logs test-coverage verify

help:
	@echo "Console Debug Bundle - Demo Commands"
	@echo ""
	@echo "Usage: make <target>"
	@echo ""
	@echo "  up-symfony7 / down-symfony7 / restart-symfony7"
	@echo "  up-symfony8 / down-symfony8 / restart-symfony8"
	@echo "  build-symfonyX, install-symfonyX, update-bundle-symfonyX"
	@echo "  test-symfonyX, test-coverage-symfonyX"
	@echo "  release-check    Run update-bundle-all + test-all + release-verify"
	@echo "  release-verify   For each demo: up -> healthcheck -> down"
	@echo ""
	@echo "Access: Symfony 7 http://localhost:8010 | Symfony 8 http://localhost:8011"

define target_template
$(1)-$(2):
	@$(MAKE) -C $(2) $(1)
endef

$(foreach d,$(DEMOS),$(eval $(call target_template,up,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,down,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,restart,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,build,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,install,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,update-bundle,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,shell,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,logs,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,test,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,test-coverage,$(d))))
$(foreach d,$(DEMOS),$(eval $(call target_template,verify,$(d))))

test-all:
	@$(foreach d,$(DEMOS),$(MAKE) test-$(d);)

test-coverage-all:
	@$(foreach d,$(DEMOS),$(MAKE) test-coverage-$(d);)

verify-all:
	@$(foreach d,$(DEMOS),$(MAKE) verify-$(d);)

update-bundle-all:
	@$(foreach d,$(DEMOS),$(MAKE) update-bundle-$(d);)

up:
	@[ -n "$(DEMO)" ] || { echo "Usage: make up DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) up-$(DEMO)

down:
	@[ -n "$(DEMO)" ] || { echo "Usage: make down DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) down-$(DEMO)

restart:
	@[ -n "$(DEMO)" ] || { echo "Usage: make restart DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) restart-$(DEMO)

build:
	@[ -n "$(DEMO)" ] || { echo "Usage: make build DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) build-$(DEMO)

install:
	@[ -n "$(DEMO)" ] || { echo "Usage: make install DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) install-$(DEMO)

update-bundle:
	@[ -n "$(DEMO)" ] || { echo "Usage: make update-bundle DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) update-bundle-$(DEMO)

test:
	@[ -n "$(DEMO)" ] || { echo "Usage: make test DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) test-$(DEMO)

test-coverage:
	@[ -n "$(DEMO)" ] || { echo "Usage: make test-coverage DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) test-coverage-$(DEMO)

shell:
	@[ -n "$(DEMO)" ] || { echo "Usage: make shell DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) shell-$(DEMO)

logs:
	@[ -n "$(DEMO)" ] || { echo "Usage: make logs DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) logs-$(DEMO)

verify:
	@[ -n "$(DEMO)" ] || { echo "Usage: make verify DEMO=symfony7|symfony8"; exit 1; }
	@$(MAKE) verify-$(DEMO)

release-verify:
	@for demo in $(DEMOS); do \
		port=$$( [ "$$demo" = "symfony7" ] && echo 8010 || echo 8011 ); \
		echo "Release verify: $$demo (port $$port)"; \
		$(MAKE) up-$$demo; sleep 12; \
		code=$$(curl -s -o /dev/null -w "%{http_code}" http://127.0.0.1:$$port 2>/dev/null || echo "000"); \
		$(MAKE) down-$$demo 2>/dev/null || true; \
		if [ "$$code" != "200" ]; then echo "$$demo failed with HTTP $$code"; exit 1; fi; \
		echo "$$demo OK"; \
	done

release-check: update-bundle-all test-all release-verify

clean:
	@rm -rf symfony7/var symfony8/var
	@echo "Demo caches cleaned"


# REQ-MAKE-008: update-deps-all (REQ-MAKE-008)
BUNDLE_ROOT := $(abspath $(dir $(lastword $(MAKEFILE_LIST)))/..)
include $(BUNDLE_ROOT)/../.scripts/Makefile.demo-aggregate-update-deps.mk

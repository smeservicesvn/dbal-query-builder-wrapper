# Makefile for dbal-query-builder-wrapper

.PHONY: help install update test lint clean tag

help:
	@echo "Available targets:"
	@echo "  install   Install PHP dependencies via Composer"
	@echo "  update    Update PHP dependencies via Composer"
	@echo "  test      Run PHPUnit tests"
	@echo "  lint      Lint PHP files (syntax check)"
	@echo "  clean     Remove Composer vendor and cache"
	@echo "  tag       Create a new git tag (usage: make tag VERSION=x.y.z)"

install:
	composer install

update:
	composer update

test:
	vendor/bin/phpunit --testdox

lint:
	find src/ tests/ -name '*.php' -exec php -l {} \;

clean:
	rm -rf vendor composer.lock .phpunit.result.cache

# Usage: make tag VERSION=x.y.z
# Example: make tag VERSION=1.0.0
tag:
	@if [ -z "$(VERSION)" ]; then \
	  echo "Please provide a VERSION, e.g. make tag VERSION=1.0.0"; \
	  exit 1; \
	fi; \
	git tag v$(VERSION); \
	git push origin v$(VERSION) 
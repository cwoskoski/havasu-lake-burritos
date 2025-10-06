# Havasu Lake Burritos - TDD Development Makefile
# Mobile-first burrito ordering platform development commands

.PHONY: help setup test test-unit test-feature test-integration test-browser test-performance
.PHONY: test-coverage test-parallel analyse lint quality clean sail-up sail-down
.PHONY: install update migrate seed fresh build dev

# Default target
help: ## Show this help message
	@echo 'Havasu Lake Burritos - Development Commands'
	@echo '=========================================='
	@awk 'BEGIN {FS = ":.*##"} /^[a-zA-Z_-]+:.*?##/ { printf "  %-20s %s\n", $$1, $$2 }' $(MAKEFILE_LIST)

# Environment Setup
setup: sail-up install migrate seed ## Complete development environment setup
	@echo "✅ Development environment ready!"
	@echo "🌐 Application: http://localhost"
	@echo "📧 Mailpit: http://localhost:8027"

sail-up: ## Start Laravel Sail (Docker) environment
	./vendor/bin/sail up -d

sail-down: ## Stop Laravel Sail environment
	./vendor/bin/sail down

install: ## Install PHP and NPM dependencies
	./vendor/bin/sail composer install
	./vendor/bin/sail npm install

update: ## Update dependencies
	./vendor/bin/sail composer update
	./vendor/bin/sail npm update

# Database Operations
migrate: ## Run database migrations
	./vendor/bin/sail artisan migrate

seed: ## Seed database with test data
	./vendor/bin/sail artisan db:seed

fresh: ## Fresh migration with seeding
	./vendor/bin/sail artisan migrate:fresh --seed

# Testing Commands (Mobile-First TDD)
test: ## Run all tests
	./vendor/bin/sail composer test

test-unit: ## Run unit tests only
	./vendor/bin/sail composer test:unit

test-feature: ## Run feature tests only
	./vendor/bin/sail composer test:feature

test-integration: ## Run integration tests
	./vendor/bin/sail artisan test --testsuite=Integration

test-browser: ## Run browser/Dusk tests for mobile UI
	./vendor/bin/sail artisan dusk

test-performance: ## Run performance tests for mobile optimization
	./vendor/bin/sail artisan test --testsuite=Performance

test-coverage: ## Run tests with coverage report (minimum 80%)
	./vendor/bin/sail composer test:coverage

test-parallel: ## Run tests in parallel for faster execution
	./vendor/bin/sail composer test:parallel

# Code Quality Commands
analyse: ## Run PHPStan static analysis (level 9)
	./vendor/bin/sail composer analyse

analyse-baseline: ## Generate PHPStan baseline
	./vendor/bin/sail composer analyse:baseline

lint: ## Fix code style with Laravel Pint
	./vendor/bin/sail composer lint

lint-test: ## Test code style without fixing
	./vendor/bin/sail composer lint:test

quality: ## Run all quality checks (lint + analyse + coverage)
	./vendor/bin/sail composer quality

# Additional Code Quality Tools
phpcs: ## Run PHP CodeSniffer
	./vendor/bin/sail vendor/bin/phpcs

phpmd: ## Run PHP Mess Detector
	./vendor/bin/sail vendor/bin/phpmd app text phpmd.xml

phpcpd: ## Run PHP Copy/Paste Detector
	./vendor/bin/sail vendor/bin/phpcpd app/

insights: ## Run PHP Insights for code quality
	./vendor/bin/sail artisan insights

# Frontend Development
build: ## Build frontend assets for production
	./vendor/bin/sail npm run build

dev: ## Start frontend development server with hot reload
	./vendor/bin/sail npm run dev

# Mobile-First Development Helpers
mobile-test: test-browser test-performance ## Run mobile-specific tests
	@echo "📱 Mobile testing complete!"

weekend-test: ## Test weekend production business logic
	./vendor/bin/sail artisan test --filter=Weekend

burrito-builder-test: ## Test burrito builder functionality
	./vendor/bin/sail artisan test --filter=BurritoBuilder

touch-target-test: ## Test touch target compliance (44px minimum)
	./vendor/bin/sail artisan dusk --filter=TouchTarget

# Performance Monitoring
performance-profile: ## Profile application performance
	./vendor/bin/sail artisan test --testsuite=Performance --verbose

load-test: ## Run load testing for weekend rush
	./vendor/bin/sail artisan test --filter=LoadTest

# Cleanup Commands
clean: ## Clean caches and temporary files
	./vendor/bin/sail artisan cache:clear
	./vendor/bin/sail artisan config:clear
	./vendor/bin/sail artisan route:clear
	./vendor/bin/sail artisan view:clear
	rm -rf build/coverage build/logs

clean-install: clean ## Clean and reinstall dependencies
	rm -rf vendor node_modules
	make install

# TDD Workflow Commands
tdd: ## Start TDD workflow (watch mode)
	./vendor/bin/sail artisan test --watch

tdd-unit: ## TDD workflow for unit tests only
	./vendor/bin/sail artisan test --testsuite=Unit --watch

tdd-feature: ## TDD workflow for feature tests only
	./vendor/bin/sail artisan test --testsuite=Feature --watch

# CI/CD Helpers
ci-test: ## Run tests in CI/CD environment
	./vendor/bin/sail composer quality
	make test-coverage
	make mobile-test

pre-commit: ## Run pre-commit checks
	make lint-test
	make analyse
	make test

# Documentation
coverage-report: ## Generate and open HTML coverage report
	make test-coverage
	@echo "📊 Coverage report: build/coverage/index.html"

# Quick Development Commands
quick-test: lint-test test-unit ## Quick testing for development
	@echo "⚡ Quick tests passed!"

full-test: quality mobile-test ## Full test suite including mobile
	@echo "🎉 All tests passed!"

# Weekend Production Specific
weekend-setup: ## Setup weekend production testing data
	./vendor/bin/sail artisan db:seed --class=WeekendProductionSeeder

ingredient-rotation: ## Test weekly ingredient rotation
	./vendor/bin/sail artisan test --filter=IngredientRotation
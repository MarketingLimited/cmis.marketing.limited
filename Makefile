# CMIS Docker Management Makefile
# Provides convenient shortcuts for common Docker operations

.PHONY: help build up down restart logs shell db-shell redis-shell test migrate seed fresh optimize clean

# Default target
help:
	@echo "CMIS Docker Management Commands"
	@echo "================================"
	@echo ""
	@echo "Setup & Build:"
	@echo "  make build       - Build Docker images"
	@echo "  make up          - Start all services"
	@echo "  make down        - Stop all services"
	@echo "  make restart     - Restart all services"
	@echo ""
	@echo "Logs & Monitoring:"
	@echo "  make logs        - View logs from all services"
	@echo "  make logs-app    - View application logs"
	@echo "  make logs-nginx  - View nginx logs"
	@echo "  make logs-db     - View database logs"
	@echo "  make stats       - View container resource usage"
	@echo ""
	@echo "Shell Access:"
	@echo "  make shell       - Access application container shell"
	@echo "  make db-shell    - Access PostgreSQL shell"
	@echo "  make redis-shell - Access Redis shell"
	@echo ""
	@echo "Database Operations:"
	@echo "  make migrate     - Run database migrations"
	@echo "  make seed        - Run database seeders"
	@echo "  make fresh       - Fresh migration with seeding"
	@echo "  make backup-db   - Backup database"
	@echo "  make restore-db  - Restore database (requires BACKUP_FILE)"
	@echo ""
	@echo "Testing:"
	@echo "  make test        - Run test suite"
	@echo "  make test-unit   - Run unit tests only"
	@echo "  make test-feature - Run feature tests only"
	@echo "  make coverage    - Generate test coverage report"
	@echo ""
	@echo "Laravel Commands:"
	@echo "  make optimize    - Optimize Laravel caches"
	@echo "  make clear       - Clear all Laravel caches"
	@echo "  make queue       - View queue worker logs"
	@echo "  make tinker      - Laravel Tinker REPL"
	@echo ""
	@echo "Maintenance:"
	@echo "  make clean       - Remove all containers and volumes (WARNING: deletes data)"
	@echo "  make prune       - Prune Docker system"
	@echo "  make ps          - Show running containers"
	@echo ""

# Setup & Build
build:
	docker-compose build --build-arg BUILD_DATE=$(shell date -u +'%Y-%m-%dT%H:%M:%SZ') --build-arg VCS_REF=$(shell git rev-parse --short HEAD 2>/dev/null || echo 'unknown')

up:
	docker-compose up -d

down:
	docker-compose down

restart:
	docker-compose restart

rebuild:
	docker-compose down
	docker-compose build --no-cache
	docker-compose up -d

# Logs & Monitoring
logs:
	docker-compose logs -f

logs-app:
	docker-compose logs -f app

logs-nginx:
	docker-compose logs -f nginx

logs-db:
	docker-compose logs -f postgres

logs-queue:
	docker-compose logs -f queue

stats:
	docker stats cmis-app cmis-nginx cmis-postgres cmis-redis

# Shell Access
shell:
	docker-compose exec app sh

db-shell:
	docker-compose exec postgres psql -U cmis -d cmis

redis-shell:
	docker-compose exec redis redis-cli

# Database Operations
migrate:
	docker-compose exec app php artisan migrate

migrate-rollback:
	docker-compose exec app php artisan migrate:rollback

seed:
	docker-compose exec app php artisan db:seed

fresh:
	docker-compose exec app php artisan migrate:fresh --seed

backup-db:
	@echo "Creating database backup..."
	docker-compose exec postgres pg_dump -U cmis -d cmis > backup-$(shell date +%Y%m%d-%H%M%S).sql
	@echo "Backup created: backup-$(shell date +%Y%m%d-%H%M%S).sql"

restore-db:
	@if [ -z "$(BACKUP_FILE)" ]; then \
		echo "Error: Please specify BACKUP_FILE=filename.sql"; \
		exit 1; \
	fi
	docker-compose exec -T postgres psql -U cmis -d cmis < $(BACKUP_FILE)

# Testing
test:
	docker-compose exec app php artisan test

test-unit:
	docker-compose exec app php artisan test --testsuite=Unit

test-feature:
	docker-compose exec app php artisan test --testsuite=Feature

test-integration:
	docker-compose exec app php artisan test --group=integration

coverage:
	docker-compose exec app php artisan test --coverage --min=70

# Laravel Commands
optimize:
	docker-compose exec app php artisan optimize
	docker-compose exec app php artisan config:cache
	docker-compose exec app php artisan route:cache
	docker-compose exec app php artisan view:cache

clear:
	docker-compose exec app php artisan optimize:clear
	docker-compose exec app php artisan config:clear
	docker-compose exec app php artisan route:clear
	docker-compose exec app php artisan view:clear
	docker-compose exec app php artisan cache:clear

queue:
	docker-compose logs -f queue

tinker:
	docker-compose exec app php artisan tinker

# Composer & NPM
composer-install:
	docker-compose exec app composer install

composer-update:
	docker-compose exec app composer update

npm-install:
	docker-compose exec app npm install

npm-build:
	docker-compose exec app npm run build

# Maintenance
clean:
	@echo "WARNING: This will delete all containers and volumes!"
	@echo "Press Ctrl+C to cancel, or wait 5 seconds to continue..."
	@sleep 5
	docker-compose down -v

prune:
	docker system prune -af --volumes

ps:
	docker-compose ps

# Health Checks
health:
	@echo "Checking service health..."
	@docker inspect cmis-app --format='App: {{.State.Health.Status}}' || echo "App: not running"
	@docker inspect cmis-nginx --format='Nginx: {{.State.Health.Status}}' || echo "Nginx: not running"
	@docker inspect cmis-postgres --format='PostgreSQL: {{.State.Health.Status}}' || echo "PostgreSQL: not running"
	@docker inspect cmis-redis --format='Redis: {{.State.Health.Status}}' || echo "Redis: not running"

# Installation
install: build up migrate seed
	@echo ""
	@echo "CMIS installation complete!"
	@echo "Access the application at: http://localhost"
	@echo ""

# Production deployment
deploy-prod: build
	@echo "Deploying to production..."
	docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d --build
	docker-compose exec app php artisan migrate --force
	docker-compose exec app php artisan optimize
	@echo "Production deployment complete!"

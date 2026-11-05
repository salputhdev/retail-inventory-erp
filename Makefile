.PHONY: setup up down test lint stan fresh shell

DC = docker compose
APP = $(DC) exec app

## Satu perintah dari clone sampai aplikasi jalan di http://localhost:8000
setup:
	@test -f .env || cp .env.example .env
	$(DC) build
	$(DC) up -d --wait pgsql redis mailpit
	$(DC) exec -T pgsql psql -U retail -d postgres -c "CREATE DATABASE retail_inventory_test" || true
	$(DC) run --rm app composer install --no-interaction
	$(DC) run --rm app php artisan key:generate --no-interaction
	$(DC) run --rm vite sh -c "npm install && npm run build"
	$(DC) up -d app
	$(APP) php artisan migrate --force
	@echo "Siap: http://localhost:8000 (Mailpit: http://localhost:8025)"

up:
	@test -f .env || cp .env.example .env
	$(DC) up -d --build pgsql redis mailpit app
	$(APP) composer install --no-interaction
	$(APP) php artisan key:generate --no-interaction
	$(DC) run --rm vite sh -c "npm install && npm run build"
	$(APP) php artisan migrate --force
	@echo "Siap: http://localhost:8000 (Mailpit: http://localhost:8025)"

up:
	$(DC) up -d

down:
	$(DC) down

test:
	$(APP) env DB_DATABASE=retail_inventory_test php artisan test

lint:
	$(APP) ./vendor/bin/pint --test

stan:
	$(APP) ./vendor/bin/phpstan analyse --memory-limit=1G

fresh:
	$(APP) php artisan migrate:fresh --seed

shell:
	$(APP) bash

## ---- Staging lokal prod-like (T0.09, ADR-006) ----
STG = docker compose -f compose.staging.yml --env-file .env.staging

.PHONY: staging-env staging-deploy staging-smoke staging-watch staging-down staging-logs

staging-env:
	scripts/staging-env.sh

staging-deploy:
	scripts/staging-deploy.sh

staging-smoke:
	scripts/smoke-test.sh https://localhost:8443 http://localhost:8080

# Deploy otomatis dari main: poll origin/main tiap 60 detik, deploy bila ada commit baru.
staging-watch:
	@echo "Memantau origin/main (Ctrl+C untuk berhenti)"; \
	last=""; while true; do \
	  git fetch -q origin main; cur=$$(git rev-parse origin/main); \
	  if [ "$$cur" != "$$last" ]; then \
	    echo "Commit baru: $$cur"; git switch -q main && git pull -q --ff-only origin main && scripts/staging-deploy.sh && last=$$cur || echo "Deploy gagal, coba lagi"; \
	  fi; sleep 60; done

staging-down:
	$(STG) down

staging-logs:
	$(STG) logs -f --tail=100

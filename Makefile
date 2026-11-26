# Hub – lokale Entwicklung und Generalprobe. `make` zeigt alle Ziele.
# Entwicklung: compose.yaml + compose.override.yaml (PHP 8.4 Apache, MySQL, Mailpit), ohne imgproxy –
# Bilder liefert PHP im Original (src/Media). Produktion nachstellen (mit imgproxy): make probe-up.

# Web-Port = Server-Port von hub im homelab-Inventar (inventory.yaml: hub → port 8197)
PORT    ?= 8197
DB_PORT ?= 9906
export HUB_HTTP_PORT := $(PORT)
export HUB_DB_PORT   := $(DB_PORT)

DC       = docker compose
PHP      = $(DC) exec -T php php
CONSOLE  = $(PHP) bin/console
COMPOSER = $(DC) exec -T php composer
MYSQL_ROOT = $(DC) exec -T database mysql -uroot -p'!ChangeMe!'

# Fotos und Bilder der Startseite (nicht im Repo, siehe docs/deployment.md „Umstellung“)
MEDIA_SRC ?= ../hub-altbestand/media

.DEFAULT_GOAL := help
.PHONY: help up down restart logs sh console install db db-test admin media seed advent-demo sass watch test lint qa \
        probe-up probe-down probe-status probe-logs

help: ## Diese Hilfe
	@grep -E '^[a-zA-Z_-]+:.*?## ' $(MAKEFILE_LIST) | awk 'BEGIN {FS = ":.*?## "}; {printf "  \033[36m%-14s\033[0m %s\n", $$1, $$2}'
	@echo; echo "  Ports: Web $(PORT) (= Server-Port laut homelab-Inventar), DB $(DB_PORT)"

# ---------- Entwicklung ----------

up: ## Umgebung starten (php, database, mailer) und auf die Datenbank warten
	@if docker ps --format '{{.Names}} {{.Ports}}' | grep -v '^hub-' | grep -q ':$(PORT)->'; then \
	  echo "Port $(PORT) ist schon belegt: $$(docker ps --format '{{.Names}} {{.Ports}}' | grep ':$(PORT)->' | cut -d' ' -f1)"; \
	  echo "→ Lokale Ports sollen den Server-Ports im homelab-Inventar entsprechen – das andere Projekt prüfen."; \
	  echo "  Notbehelf: make up PORT=<frei>"; exit 1; fi
	@$(DC) up -d --build --wait
	@echo; echo "  App:     http://localhost:$(PORT)"; echo "  Admin:   http://localhost:$(PORT)/admin (Anmeldung: /login)"; \
	  echo "  Health:  http://localhost:$(PORT)/healthz"; echo "  Mailpit: $$($(DC) port mailer 8025 2>/dev/null | sed 's/^0.0.0.0/http:\/\/localhost/')"

down: ## Alles stoppen (Daten bleiben im Volume)
	@$(DC) down

restart: down up ## Neu starten

logs: ## Logs verfolgen
	@$(DC) logs -f --tail=100

sh: ## Shell im php-Container
	@$(DC) exec php bash

console: ## Symfony-Konsole, z. B. make console CMD="debug:router"
	@$(CONSOLE) $(CMD)

install: ## Composer-Abhängigkeiten installieren
	@$(COMPOSER) install

db: db-test ## Datenbanken migrieren (dev und test)
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration

db-test: ## Testdatenbank app_test anlegen (falls nötig) und migrieren
	@$(MYSQL_ROOT) -e "CREATE DATABASE IF NOT EXISTS app_test CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci; GRANT ALL ON app_test.* TO 'app'@'%';" 2>&1 | grep -v Warning || true
	@$(CONSOLE) doctrine:migrations:migrate --no-interaction --allow-no-migration --env=test

admin: ## Admin anlegen: make admin EMAIL=ich@example.de PASSWORD=geheim
	@test -n "$(EMAIL)" -a -n "$(PASSWORD)" || { echo "EMAIL=… PASSWORD=… angeben"; exit 1; }
	@$(CONSOLE) app:admin:create-user "$(EMAIL)" "$(PASSWORD)"

media: ## Fotos und Startseiten-Bilder aus ../hub-altbestand/media nach var/media kopieren (überschreibt nichts)
	@test -d $(MEDIA_SRC) || { echo "$(MEDIA_SRC) fehlt – MEDIA_SRC=<ordner> angeben"; exit 1; }
	@mkdir -p var/media && cp -rn $(MEDIA_SRC)/. var/media/ && du -sh var/media/* 

seed: media ## Inhalte anlegen: Startseite, Foto-Kalender 2022 (wiederholbar)
	@$(CONSOLE) app:projects:seed
	@$(CONSOLE) app:fotokalender:import --year=2022

advent-demo: ## Beispiel-Adventskalender (docs/advent/beispiel.yaml) anlegen, gibt Teilnehmer-Links aus
	@$(CONSOLE) app:advent:import docs/advent/beispiel.yaml

sass: ## Sass einmal bauen
	@$(CONSOLE) sass:build

watch: ## Sass bei Änderungen neu bauen (läuft im Vordergrund)
	@$(CONSOLE) sass:build --watch

test: ## PHPUnit
	@$(PHP) vendor/bin/phpunit

# --skip-property-types: BetAI (PlacedBet, Bankroll) hat float-Properties auf decimal-Spalten – bekannt, unabhängig
# vom Rest. Voller Check: make console CMD=doctrine:schema:validate
lint: ## Container, Twig, YAML, Doctrine-Schema prüfen
	@$(CONSOLE) lint:container
	@$(CONSOLE) lint:twig templates
	@$(CONSOLE) lint:yaml config docs/advent
	@$(CONSOLE) doctrine:schema:validate --skip-property-types

qa: lint test ## Alle Prüfungen

# ---------- Generalprobe: Produktions-Stack lokal (mit imgproxy), siehe docs/deployment.md ----------
# Eigenes Projekt + VOLUME_PREFIX, damit die Dev-Daten (Volume hub_database_data) unberührt bleiben.
# Die Env-Datei wird einmalig mit Zufallswerten erzeugt (gitignored).
PROBE_ENV     ?= .env.probe.local
PROBE_PORT    ?= 18197
PROBE_DC       = docker compose -p hubprobe -f compose.prod.yaml --env-file $(PROBE_ENV)

$(PROBE_ENV):
	@mkdir -p var/probe-backups
	@umask 077; printf '%s\n' \
	  "DEFAULT_URI=https://wh-company.com" "APP_SECRET=$$(openssl rand -hex 32)" \
	  "MYSQL_PASSWORD=$$(openssl rand -hex 24)" "MYSQL_ROOT_PASSWORD=$$(openssl rand -hex 24)" \
	  "GEMINI_API_KEY=probe" "IMGPROXY_KEY=$$(openssl rand -hex 32)" "IMGPROXY_SALT=$$(openssl rand -hex 32)" \
	  "MAILER_DSN=null://null" "MYSQL_DATABASE=hub" "MYSQL_USER=hub" \
	  "HTTP_BIND=127.0.0.1" "HTTP_PORT=$(PROBE_PORT)" "REVERSE_PROXY_IPS=private_ranges" \
	  "BACKUP_DIR=$(CURDIR)/var/probe-backups" "BACKUP_KEEP=3" "VOLUME_PREFIX=hubprobe" > $@
	@echo "$@ erzeugt"

probe-up: $(PROBE_ENV) ## Produktions-Images bauen und als „hubprobe“ starten, Medien + Inhalte einspielen
	@$(PROBE_DC) up -d --build --wait --wait-timeout 300
	@if [ -d $(MEDIA_SRC) ]; then tar -C $(MEDIA_SRC) -cz . | docker run --rm -i -v hubprobe_media_data:/m alpine:3.22 \
	  sh -c 'tar -C /m -xz && chown -R 82:82 /m'; fi
	@$(PROBE_DC) exec -T app php bin/console app:projects:seed -q
	@-$(PROBE_DC) exec -T app php bin/console app:fotokalender:import --year=2022 -q
	@echo; echo "  Läuft auf 127.0.0.1:$(PROBE_PORT), antwortet aber nur auf wh-company.com. Im Browser z. B.:"; \
	  echo "  google-chrome --host-resolver-rules='MAP wh-company.com 127.0.0.1:$(PROBE_PORT)' http://wh-company.com/"; \
	  echo "  oder: curl -H 'Host: wh-company.com' http://127.0.0.1:$(PROBE_PORT)/"

probe-status: ## Generalprobe: Zustand, Healthcheck, imgproxy (signiert 200, verfälscht 403)
	@$(PROBE_DC) ps --format 'table {{.Service}}\t{{.Status}}'
	@echo; printf "healthz: "; curl -s http://127.0.0.1:$(PROBE_PORT)/healthz; echo
	@img=$$(curl -s -H 'Host: wh-company.com' http://127.0.0.1:$(PROBE_PORT)/ | grep -o '/img/[^" ]*' | head -1); \
	  if [ -z "$$img" ]; then echo "imgproxy: keine Bild-URL auf der Startseite (Medien/Seed fehlen?)"; else \
	  printf "imgproxy signiert:  "; curl -s -o /dev/null -w "%{http_code}\n" -H 'Host: wh-company.com' "http://127.0.0.1:$(PROBE_PORT)$$img"; \
	  printf "imgproxy verfälscht: "; curl -s -o /dev/null -w "%{http_code}\n" -H 'Host: wh-company.com' "http://127.0.0.1:$(PROBE_PORT)$$(echo $$img | sed 's#^/img/.#/img/X#')"; fi

probe-logs: ## Generalprobe: Logs verfolgen
	@$(PROBE_DC) logs -f --tail=100

probe-down: ## Generalprobe abbauen (löscht nur hubprobe_*-Volumes und die Probe-Images)
	@-$(PROBE_DC) down -v
	@-docker image rm hub-app:prod hub-web:prod 2>/dev/null
	@rm -f $(PROBE_ENV)

# Local Development Guide

This guide provides instructions for setting up and working with the local development environment.

## Environment Overview

- **PHP Version:** 8.4 (Apache)
- **Database:** MySQL 8.0
- **External Web Port:** [http://localhost:8088](http://localhost:8088)
- **External DB Port:** 9906 (for local DB clients)

## Getting Started

To start the environment, run:

```bash
docker compose up -d
```

To stop the environment:

```bash
docker compose down
```

To view logs:

```bash
docker compose logs -f
```

## Running Commands inside the PHP Container

Most development commands (like Symfony console commands or Composer) should be executed inside the `php` container.

### Database Migrations

To run database migrations inside the container:

```bash
docker compose exec php bin/console doctrine:migrations:migrate
```

To generate a new migration:

```bash
docker compose exec php bin/console make:migration
```

### Other Common Commands

- **Install Dependencies:**
  ```bash
  docker compose exec php composer install
  ```

- **Clear Cache:**
  ```bash
  docker compose exec php bin/console cache:clear
  ```

- **Run Tests:**
  ```bash
  docker compose exec php vendor/bin/phpunit
  ```

- **Compile Assets:**
  ```bash
  docker compose exec php bin/console asset-map:compile
  ```

- **Database Schema Validation:**
  ```bash
  docker compose exec php bin/console doctrine:schema:validate
  ```

- **Access Container Shell:**
  ```bash
  docker compose exec php bash
  ```

### Application Specific Commands

These commands are custom-built for this application.

- **Create Admin User:**
  ```bash
  docker compose exec php bin/console app:admin:create-user
  ```

- **Initialize ESC Project:**
  (Ensures project entity exists, active edition exists, and seeds countries if missing)
  ```bash
  docker compose exec php bin/console app:esc:init
  ```

- **Seed ESC Countries:**
  (Updates/Seeds the master list of countries)
  ```bash
  docker compose exec php bin/console app:esc:seed-countries
  ```

- **Seed Dummy Projects:**
  (Seeds projects for the landing page)
  ```bash
  docker compose exec php bin/console app:projects:seed
  ```

## Admin Features

### ESC Participant Import
Administrators can import participants for an ESC Edition via the Admin Dashboard.
1. Navigate to **ESC Voting** -> **Editionen**.
2. Click the **Teilnehmer importieren** (Import Participants) icon for the desired edition.
3. Paste a list of participants in the format: `CountryCode;Artist;Song;StartOrder` (one per line).
   - Example: `DE;Isaak;Always on the Run;3`

## Database Access

The database is accessible from your host machine on port `9906`.

- **Host:** `127.0.0.1`
- **Port:** `9906`
- **User:** `app`
- **Password:** `!ChangeMe!`
- **Database:** `app`

The internal connection string used within the Docker network (e.g., in `.env` or `compose.yaml`) uses the service name `database` and port `3306`.

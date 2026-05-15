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
  (Ensures project entity exists and seeds initial countries)
  ```bash
  docker compose exec php bin/console app:esc:init
  ```

- **Import ESC Countries:**
  (Imports a hardcoded list of countries)
  ```bash
  docker compose exec php bin/console app:esc:import-countries
  ```

- **Seed ESC Countries:**
  (Alternative seeding for ESC countries)
  ```bash
  docker compose exec php bin/console app:esc:seed-countries
  ```

- **Seed Dummy Projects:**
  (Seeds projects for the landing page)
  ```bash
  docker compose exec php bin/console app:projects:seed
  ```

## Database Access

The database is accessible from your host machine on port `9906`.

- **Host:** `127.0.0.1`
- **Port:** `9906`
- **User:** `app`
- **Password:** `!ChangeMe!`
- **Database:** `app`

The internal connection string used within the Docker network (e.g., in `.env` or `compose.yaml`) uses the service name `database` and port `3306`.

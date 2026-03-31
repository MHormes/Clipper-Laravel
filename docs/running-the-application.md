# Running the Application

This document covers the three ways to run the Clipper application: locally with Herd + Vite, locally with Docker Compose, and in production with the production Docker Compose.

---

## 1. Local Development — Herd + Vite

This is the recommended setup for day-to-day development. Laravel Herd serves the PHP application natively while Vite handles frontend hot-reload. The database runs on SQLite and file storage uses the local filesystem — no PostgreSQL or MinIO required.

**Prerequisites:**

- [Laravel Herd](https://herd.laravel.com/) installed
- Node.js + npm installed

**Steps:**

1. Copy the environment file and fill in your local values:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

2. Install dependencies:

    ```bash
    composer install
    npm install
    ```

3. Run migrations (and seed on first run):

    ```bash
    php artisan migrate
    php artisan db:seed --class=CsvDataSeeder
    ```

4. Start the Vite dev server:

    ```bash
    npm run dev
    ```

5. Open the site via Herd (e.g. `http://clipper-laravel.test`).

The Vite dev server runs on a separate port and proxies hot-module replacement through Herd's PHP server.

---

## 2. Local Docker Compose

This spins up the full stack (app, PostgreSQL, MinIO) in Docker containers using `docker-compose-local.yml`. Useful for testing the containerized environment without touching production.

**Prerequisites:**

- Docker Desktop (or Podman with Docker-compatible CLI)
- An `.env.local` file configured

**Steps:**

1. Create `.env.local` based on `.env.example` and set `APP_ENV=local`.

2. Create the required external Docker volumes (first time only):

    ```bash
    docker volume create clipper_db_data
    docker volume create clipper_minio_data
    ```

3. Run the setup script with the `local` profile:

    ```bash
    bash scripts/setup.sh local
    ```

    This script will:
    - Stop any running containers
    - Create volumes if missing
    - Build and start all containers (`clipper_app`, `clipper_postgres`, `clipper_storage`)
    - Fix file permissions inside the app container
    - Configure the MinIO `clipper-ms` bucket with public download access

4. The app is available at `http://localhost:8000`.
   The MinIO console is available at `http://localhost:9001` (user: `admin`, password: `password`).

**What runs:**

| Container          | Role                  | Port(s)        |
| ------------------ | --------------------- | -------------- |
| `clipper_app`      | Laravel + Apache      | `8000 → 80`    |
| `clipper_postgres` | PostgreSQL 17         | `5432`         |
| `clipper_storage`  | MinIO (S3-compatible) | `9000`, `9001` |

> Note: The local compose mounts the project directory into the container (`./:/var/www/html`), so code changes are reflected without a rebuild. The `vendor/` and `node_modules/` directories are excluded from the mount via anonymous volumes.

---

## 3. Production Docker Compose

The production setup (`docker-compose-production.yml`) runs on the server and adds a Cloudflare Tunnel container for public HTTPS access. There are no exposed ports for the database or storage — all traffic goes through the tunnel.

**Prerequisites:**

- An `.env.production` file configured on the server
- Valid `CLOUDFLARE_TUNNEL_TOKEN` in the production env file
- Docker installed on the server

**Steps:**

1. Pull the latest code (use the `clipper-pull` alias — see [Aliases](#aliases)):

    ```bash
    git pull origin main
    ```

2. Run the setup script with the `production` profile:

    ```bash
    bash scripts/setup.sh production
    ```

    This script will:
    - Copy `.env.production` → `.env` and `docker-compose-production.yml` → `docker-compose.yml` (for Dockge compatibility)
    - Enable a Cloudflare maintenance page while deploying
    - Stop existing containers
    - Create production volumes (`clipper_db_data_prod`, `clipper_minio_data_prod`) if missing
    - Build and start all containers
    - Fix file permissions
    - Configure the MinIO bucket
    - Disable the Cloudflare maintenance page when done

**What runs:**

| Container               | Role                      |
| ----------------------- | ------------------------- |
| `clipper_app_prod`      | Laravel + Apache          |
| `clipper_postgres_prod` | PostgreSQL 17             |
| `clipper_storage_prod`  | MinIO (S3-compatible)     |
| `clipper_tunnel_prod`   | Cloudflare Tunnel (HTTPS) |

> The database and MinIO are not exposed on any host port in production — they are only reachable from within the Docker network.

---

## Aliases

Shell aliases are configured on both the laptop and the server to speed up common operations.

### Laptop Aliases

| Alias             | Description                                                                                                                                                                            |
| ----------------- | -------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `clipper-connect` | Opens an SSH connection to server Ralph. **Requires VPN to be active.**                                                                                                                |
| `clipper-data`    | Copies the backup folder to the desktop. For a clean deployment, replace the `.csv` files in `database/data/` with the backed-up `.csv` files. MinIO photos must be restored manually. |
| `clipper-start`   | Runs a local copy of production in Podman. Only the credentials differ and the Cloudflare container is absent.                                                                         |

### Server Aliases

| Alias            | Description                                                                                                                                   |
| ---------------- | --------------------------------------------------------------------------------------------------------------------------------------------- |
| `clipper-pull`   | Pulls the latest changes from Git.                                                                                                            |
| `clipper-deploy` | Runs `setup.sh` and deploys the current pull.                                                                                                 |
| `clipper-backup` | Runs `backup.sh` — exports all database tables to CSV and mirrors the MinIO bucket, then removes the oldest backup keeping the 3 most recent. |

### Server Cron Job

The `backup.sh` script runs automatically every night at **02:00** via a server cron job.

The backup creates a timestamped folder under `backups/` containing:

- `csv/` — every database table exported as a CSV file
- `storage/` — a mirror of the MinIO `clipper-ms` bucket

A maximum of 3 backups are retained; older ones are deleted automatically.

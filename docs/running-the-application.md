# Running the Application

This document covers the three ways to run the Clipper application: locally with Herd + Vite, locally with Docker Compose, and in production with the production Docker Compose.

---

## Data & Storage Overview

The application has two data sources that need to be present for a functioning environment:

| Source | What | Where |
|--------|------|--------|
| **Database** | Users, clippers, series, collections, follows | PostgreSQL (Docker) or SQLite (Herd) |
| **Storage** | Clipper and series images | AIStor/S3 (Docker) or `storage/app/public/` (Herd) |

### How seeding works

Both the database and file storage are **seeded automatically on first boot** in Docker environments. The logic lives in `scripts/start.sh` and is safe to re-run.

#### Database seeding (`CsvDataSeeder`)

- Triggered if `Clipper::count() === 0` (empty table = first run)
- Reads CSV files from `database/data/`: `users.csv`, `series.csv`, `clippers.csv`, `collected_clippers.csv`, `user_follows.csv`
- Upserts by `id` in 250-row chunks — safe to re-run, no duplicates
- Skipped on subsequent restarts once data exists

#### Storage seeding (`php artisan storage:seed`)

- Triggered if the S3 bucket contains zero files
- Reads all files from `storage/app/public/` (subdirs `clippers/` and `series/`) and uploads to the AIStor `clipper-ms` bucket
- Only runs after AIStor is confirmed reachable (health check loop in `start.sh`)
- Skipped on subsequent restarts once files exist in the bucket

> **Seed source files must be present before running Docker.** For the database: `database/data/*.csv`. For storage: `storage/app/public/clippers/` and `storage/app/public/series/`. These files are not committed to git — they come from a backup (see [Moving to a New Server](#moving-to-a-new-server)).

---

## 1. Local Development — Herd + Vite

Recommended for day-to-day development. Laravel Herd serves PHP natively, Vite handles hot-reload. Database is SQLite, file storage is the local filesystem — no PostgreSQL or AIStor required.

`QUEUE_CONNECTION=sync` in this setup means queued jobs (e.g. verification emails) run immediately without a separate worker.

Local mail uses Mailtrap Email Sandbox through the dedicated `mailtrap` mailer. Add your Mailtrap Sandbox SMTP username and password to `.env`:

```env
MAIL_MAILER=mailtrap
MAILTRAP_HOST=sandbox.smtp.mailtrap.io
MAILTRAP_PORT=2525
MAILTRAP_USERNAME=your-mailtrap-username
MAILTRAP_PASSWORD=your-mailtrap-password
MAILTRAP_SCHEME=null
```

**Prerequisites:**

- [Laravel Herd](https://herd.laravel.com/) installed
- Node.js + pnpm installed

**Steps:**

1. Copy the environment file and generate a key:

    ```bash
    cp .env.example .env
    php artisan key:generate
    ```

2. Install dependencies:

    ```bash
    composer install
    pnpm install
    ```

3. Run migrations and seed data on first run:

    ```bash
    php artisan migrate
    php artisan db:seed --class=CsvDataSeeder
    ```

    CSV source files (`database/data/*.csv`) must be present. Images in `storage/app/public/` are served directly via Laravel's `public` disk — no AIStor upload needed.

4. Start the Vite dev server:

    ```bash
    pnpm run dev
    ```

5. Open the site via Herd (e.g. `http://clipper-laravel.test`).

---

## 2. Local Docker Compose

Spins up the full stack (app, queue worker, PostgreSQL, AIStor) using `docker-compose-local.yml`. Useful for testing the containerized environment without touching production.

Local Docker also uses Mailtrap Email Sandbox through `.env.local`. The mail variables are passed to both `clipper_app` and `clipper_queue`, so queued notifications are captured by the same Mailtrap inbox.

**Prerequisites:**

- Docker Desktop (or Podman with Docker-compatible CLI)
- `.env.local` configured
- CSV files present in `database/data/`
- Images present in `storage/app/public/clippers/` and `storage/app/public/series/`

**Steps:**

1. Create `.env.local` based on `.env.example` and set `APP_ENV=local`.

2. Run the setup script with the `local` profile:

    ```bash
    bash scripts/setup.sh local
    ```

    The script creates volumes if missing, builds and starts all containers, fixes permissions, and configures the AIStor bucket.

3. The app is available at `http://localhost:8000`.
   The AIStor console is available at `http://localhost:9001` (user: `admin`, password: `password`).

**On first boot, `start.sh` automatically:**

1. Waits for PostgreSQL to be ready
2. Runs `php artisan migrate --force`
3. Checks if `Clipper::count() === 0` → if yes, runs `CsvDataSeeder` (reads `database/data/*.csv`)
4. Waits for AIStor to be healthy
5. Checks if S3 bucket is empty → if yes, runs `php artisan storage:seed` (uploads `storage/app/public/`)
6. Starts the SSR Node server
7. Starts Apache

Subsequent restarts skip steps 3 and 5 automatically.

**What runs:**

| Container          | Role                   | Port(s)        |
| ------------------ | ---------------------- | -------------- |
| `clipper_app`      | Laravel + Apache       | `8000 -> 80`   |
| `clipper_queue`    | Laravel queue worker   | —              |
| `clipper_postgres` | PostgreSQL 17          | `5432`         |
| `clipper_storage`  | AIStor (S3-compatible) | `9000`, `9001` |

> The local compose mounts the project directory into the container (`./:/var/www/html`). The `vendor/`, `node_modules/`, `bootstrap/ssr/`, and `public/build/` directories are excluded from the mount via anonymous volumes so Docker-built artifacts are not overwritten by the host filesystem.

---

## 3. Production Docker Compose

The production setup (`docker-compose-production.yml`) runs on the server. Public HTTPS access is provided by Cloudflare Tunnel, which runs in a separate, shared LXC container on the same Proxmox host (not in this repo's Docker Compose) — that LXC proxies to this VM's published `80` port. No ports are exposed for the database or storage.

**Prerequisites:**

- `.env.production` configured on the server
- Docker installed on the server
- The shared cloudflared LXC configured with a route for this VM (managed outside this repo)
- CSV files present in `database/data/` (from backup)
- Images present in `storage/app/public/` (from backup)

**Steps:**

1. Pull the latest code (use the `clipper-pull` alias):

    ```bash
    git pull origin main
    ```

2. Run the setup script with the `production` profile:

    ```bash
    bash scripts/setup.sh production
    ```

    The script:
    - Copies `.env.production` → `.env` and `docker-compose-production.yml` → `docker-compose.yml` for Dockge compatibility
    - Enables a Cloudflare maintenance page during deploy (Worker-based, independent of tunnel location)
    - Stops existing containers
    - Creates production volumes (`clipper_db_data_prod`, `clipper_storage_data_prod`) if missing
    - Builds and starts all containers
    - Fixes file permissions
    - Configures the AIStor bucket
    - Disables the Cloudflare maintenance page when done

The same auto-seeding logic from `start.sh` applies — DB and storage are seeded on first boot if empty, skipped thereafter.

**What runs:**

| Container               | Role                    | Port(s)      |
| ------------------------ | ----------------------- | ------------ |
| `clipper_app_prod`      | Laravel + Apache        | `80 -> 80`   |
| `clipper_queue_prod`    | Laravel queue worker    | —            |
| `clipper_postgres_prod` | PostgreSQL 17           | —            |
| `clipper_storage_prod`  | AIStor (S3-compatible)  | —            |

> The database and AIStor are not exposed on any host port in production — they are only reachable from within the Docker network. HTTPS/routing is handled outside this compose file by the shared cloudflared LXC, which reaches the app over the published `80` port.

---

## Moving to a New Server

To migrate to a new server or do a clean production re-init after a server wipe:

1. **Get a backup** — use `clipper-data` alias on your laptop, or grab the latest timestamped folder from `backups/` on the old server.

2. **Restore CSV data** — copy the `.csv` files from `backup/csv/` into `database/data/`:

    ```
    database/data/users.csv
    database/data/series.csv
    database/data/clippers.csv
    database/data/collected_clippers.csv
    database/data/user_follows.csv
    ```

3. **Restore images** — copy the contents of `backup/storage/` into `storage/app/public/`:

    ```
    storage/app/public/clippers/   ← all clipper images
    storage/app/public/series/     ← all series images
    ```

4. **Deploy** — run `bash scripts/setup.sh production` (or `clipper-deploy` alias on the server).

On first boot, `start.sh` automatically seeds both the database from the CSV files and the AIStor bucket from the image files. No manual database import or AIStor upload needed.

---

## Aliases

Shell aliases are configured on both the laptop and the server to speed up common operations.

### Laptop Aliases

| Alias             | Description                                                                                                                                                                                                         |
| ----------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------- |
| `clipper-connect` | Opens an SSH connection to the Clipper VM. **Requires VPN to be active.**                                                                                                                                           |
| `clipper-data`    | Runs `scripts/copy_backup.sh` — SSHs in, finds the latest backup, confirms, downloads it to `~/Desktop/ClipperBackups/`. Optionally stages `csv/` into `database/data/` and `storage/` into `storage/app/public/` for a local Docker seed. |
| `clipper-up`      | Build and start the local Docker stack via `scripts/setup.sh local`.                                                                                                                                                |

### Server Aliases

| Alias            | Description                                                                                                                                    |
| ---------------- | ---------------------------------------------------------------------------------------------------------------------------------------------- |
| `clipper-pull`   | Pulls the latest changes from Git.                                                                                                             |
| `clipper-deploy` | Runs `setup.sh` and deploys the current pull.                                                                                                  |
| `clipper-backup` | Runs `backup.sh` — exports all database tables to CSV and mirrors the AIStor bucket, then removes the oldest backup keeping the 3 most recent. |

### VM OS Updates

`scripts/vm-update.sh` automates the monthly OS update cycle. Run it from the project root on the server:

```bash
sudo bash scripts/vm-update.sh
```

It runs `apt update && apt upgrade -y`. If a kernel update requires a reboot, it automatically enables the Cloudflare maintenance page via `scripts/maintenance-on.sh` before rebooting. If no reboot is needed, it exits cleanly with zero downtime.

After a reboot the Docker containers come back up automatically (`restart: unless-stopped`). The `@reboot` cron entry below re-disables maintenance mode once the system is back.

### Cloudflare Maintenance Scripts

Three helper scripts in `scripts/` manage the Cloudflare maintenance page independently of deployment:

| Script | Purpose |
| --- | --- |
| `scripts/maintenance-on.sh` | Creates a Cloudflare Worker route that serves the maintenance page |
| `scripts/maintenance-off.sh` | Deletes the Worker route, restoring normal traffic |
| `scripts/utils.sh` | Sourced by the above; loads `.env.production` and derives domain + worker name |

These are also called by `scripts/setup.sh production` around the build/restart cycle.

### Server Cron Jobs

```cron
# Nightly database and storage backup at 02:00
0 2 * * * /path/to/Clipper-Laravel/scripts/backup.sh

# Re-enable site after a VM reboot triggered by vm-update.sh
@reboot sleep 30 && /path/to/Clipper-Laravel/scripts/maintenance-off.sh
```

Each backup creates a timestamped folder under `backups/` containing:

- `csv/` — every database table exported as a CSV file
- `storage/` — a full mirror of the AIStor `clipper-ms` bucket

A maximum of 3 backups are retained; older ones are deleted automatically.

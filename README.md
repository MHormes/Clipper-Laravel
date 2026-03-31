# Clipper-MS

Clipper-MS is a Clipper lighter management system — a community databank of existing Clipper lighter designs. Users can browse the full catalog, mark lighters as part of their personal collection, add notes and purchase locations, and see what other collectors own.

## Features

- **Catalog** — Structured index of Clipper series and their individual lighter variants
- **Collection tracking** — Mark individual lighters or entire series as owned; add notes and purchase location per item
- **Map view** — Geographic view of collection or series data
- **Social** — Follow other collectors, browse public user profiles, and see what the community is collecting
- **Contributions** — Submit requests to add new series or lighters; reviewed through a moderated approval queue
- **Admin panel** — Manage series, users, and pending contribution requests
- **PWA** — Installable on mobile with standalone display and portrait lock
- **SEO** — Dynamic sitemap and robots.txt; key pages are publicly crawlable

## Tech Stack

| Layer    | Technology                         |
| -------- | ---------------------------------- |
| Backend  | PHP 8.x · Laravel                  |
| Frontend | Vue.js 3 · Inertia.js · TypeScript |
| Styling  | Tailwind CSS v4 · Bootstrap        |
| Build    | Vite                               |
| Database | PostgreSQL 17 (SQLite in dev)      |
| Storage  | MinIO (S3-compatible)              |
| Hosting  | Docker · Cloudflare Tunnel         |

## Running the App

There are three ways to run Clipper-MS:

| Mode                          | Description                                                                                                                              |
| ----------------------------- | ---------------------------------------------------------------------------------------------------------------------------------------- |
| **Herd + Vite**               | Recommended for day-to-day development. Runs fully in-memory with SQLite and local file storage — no external services needed.           |
| **Local Docker Compose**      | Full containerized stack (app + PostgreSQL + MinIO) via `docker-compose-local.yml`. Closest to production without the Cloudflare tunnel. |
| **Production Docker Compose** | Server deployment via `docker-compose-production.yml`. Adds a Cloudflare Tunnel container for public HTTPS access.                       |

For setup instructions, environment variables, Docker volumes, and server/laptop aliases see [docs/running-the-application.md](docs/running-the-application.md).

## Branch Strategy

| Branch        | Purpose                                               |
| ------------- | ----------------------------------------------------- |
| `development` | Active development — linter + tests run on every push |
| `main`        | Production-ready — tests must pass before merging     |

See [docs/branch-protection.md](docs/branch-protection.md) for how to configure branch protection rules on GitHub.

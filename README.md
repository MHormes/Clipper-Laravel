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

## License

This project is **Proprietary**. All rights are reserved by the author.
Unauthorized cloning or hosting of this project is strictly prohibited.
See the [LICENSE](LICENSE) file for more details.

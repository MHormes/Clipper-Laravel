# Clipper-Laravel — Monthly Update Guide

Run after server OS updates are done (see global `update_guide.md`). Do all steps locally first, then deploy once at the end.

---

## 1. Docker version bumps

**`Dockerfile`** — check for updates:

- PHP base image (`FROM php:x-apache`) in `Dockerfile` — check latest patch: https://hub.docker.com/_/php/tags
- For minor/major PHP bumps: also update the `"php"` version constraint in the `require` block of `composer.json`, run `composer update` locally, commit updated `composer.lock` (both files are in the project root)
- Node.js installed via apt — no version pinned, no action needed
- pnpm version pinned via `packageManager` in `package.json` — bump there when upgrading pnpm

**`docker-compose-production.yml`** — check for updates:

- `clipper_postgres_prod` — `postgres:x-alpine` — bump patch/minor freely; major needs migration check
- `clipper_storage_prod` — `quay.io/minio/aistor/minio:latest` — always pulls latest, nothing to pin
- `clipper_tunnel_prod` — `cloudflare/cloudflared:latest` — always pulls latest, nothing to pin

---

## 2. PHP packages (Composer)

Check what is outdated:

```bash
composer outdated
```

Update within allowed constraints:

```bash
composer update
```

> For major version bumps (e.g. Laravel 13): check https://laravel.com/docs/upgrade first.

Run tests after updating:

```bash
php artisan test
```

Fix any deprecation warnings before continuing.

---

## 3. Node packages (pnpm)

Check what is outdated:

```bash
pnpm outdated
```

Update within allowed constraints:

```bash
pnpm update
```

> For major version bumps of key packages, read changelogs first:
> - Vue: https://vuejs.org/guide/extras/release-notes
> - Inertia: https://inertiajs.com/upgrade-guide
> - Vite: https://github.com/vitejs/vite/blob/main/packages/vite/CHANGELOG.md
> - Tailwind: https://tailwindcss.com/docs

To update a single package past its semver constraint:

```bash
pnpm update <package-name> --latest
```

---

## 4. Local build & test

```bash
pnpm build
php artisan test
```

- [ ] Build succeeds with no errors
- [ ] App loads in browser, no JS console errors
- [ ] Login, core CRUD, and media upload work
- [ ] Dark mode and light mode render correctly
- [ ] `php artisan migrate:status` shows no pending migrations

---

## 5. Push & deploy

Push all changes to git, then:

```bash
clipper-pull
clipper-deploy
```

> Check container logs if anything seems off: `docker compose logs -f`

---

## Rollback

If something breaks after `composer update`:

```bash
git checkout composer.lock
composer install
```

If something breaks after `pnpm update`:

```bash
git checkout pnpm-lock.yaml
pnpm install
```

---

## Occasional: PHP version upgrade

PHP releases a new minor version roughly once a year. When upgrading (e.g. 8.4 → 8.5):

1. Update `FROM php:x-apache` in `Dockerfile`
2. Update the `"php"` version constraint in the `require` block of `composer.json`
3. Run `composer update` locally, commit updated `composer.lock`
4. Run `php artisan test`

---

## Occasional: pnpm version upgrade

```bash
pnpm self-update
```

Or update the `packageManager` field in `package.json` and reinstall.

---

## Resources

- [Laravel Upgrade Guide](https://laravel.com/docs/upgrade)
- [Vue 3 Changelog](https://github.com/vuejs/core/blob/main/CHANGELOG.md)
- [Inertia.js Changelog](https://github.com/inertiajs/inertia/blob/master/CHANGELOG.md)
- [Vite Changelog](https://github.com/vitejs/vite/blob/main/packages/vite/CHANGELOG.md)
- [Tailwind CSS v4 Docs](https://tailwindcss.com/docs)

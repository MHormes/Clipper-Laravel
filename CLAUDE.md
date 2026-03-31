# Clipper-Laravel Development Guidelines

This document is the **source of truth** for all development guidelines in this project. All other `.md` files either provide human-facing overviews or operational detail, and defer to this file on coding standards.

## 📚 Project Documentation

| File | Purpose |
|------|---------|
| `README.md` | Project overview, feature list, tech stack summary — human-facing entry point |
| `docs/running-the-application.md` | Full guide for running locally (Herd + Vite, local Docker Compose) and in production (production Docker Compose), including server/laptop aliases and the backup cron job |
| `docs/branch-protection.md` | How to configure GitHub branch protection rules for `main` and `development` |
| `CLAUDE.md` *(this file)* | Coding standards, styling rules, naming conventions, and AI agent instructions |

---

## 🧠 Local RAG Instructions

- **Context Limitation:** You are only seeing snippets of the codebase provided by a custom Gemini-powered RAG system.
- **Requesting More:** If you need to see a specific file that wasn't provided in the context, ask the user to run `npm run rag:search -- "show me [filename]"` or `npm run rag:query -- "show me [filename]"` specifically.
- **Index Reference:** All code citations in my responses must refer to the source numbers (e.g., [1], [2]) provided by the query script.

## 🔄 Re-indexing & Manifests

- **Index Updates:** If the code changes you suggest involve creating new files or deleting files, you MUST append the exact string `COMMAND_TRIGGER: REINDEX` to the end of your response.
- **The Manifest:** The system relies on `storage/gemini_rag/manifest.json`. If you detect structural drift, advise the user to run `npm run rag:index` for a fresh build.
- **Incremental Sync:** For minor changes, prefer suggesting `npm run rag:update` to save on API quota by leveraging the resume functionality.

## 🛠 Technology Stack

- **Backend:** PHP 8.x with Laravel Framework
- **Frontend:** Vue.js 3 with Inertia.js
- **Styling:** Tailwind CSS (v4) and Bootstrap
- **Build Tool:** Vite

## 🎨 Styling & Appearance

### No Hardcoded Colors

All colors **must** use the predefined theme variables defined in `resources/css/app.css`. Hardcoded hex, RGB, or HSL values are strictly prohibited in components and views.

#### Available Color Variables (Tailwind)

Use these via Tailwind classes (e.g., `text-primary`, `bg-primary-background`, `border-border-color`):

- **Brand:** `primary`
- **Backgrounds:** `primary-background`, `secondary-background`, `muted-background`, `component-background`
- **Content/Text:** `primary-content`, `secondary-content`, `muted-content`, `button-content`
- **Status:** `error`, `success`, `warning`, `info`
- **Other:** `border-color`

### Framework Usage

- **Tailwind CSS:** Primary utility for layout, spacing, and component-specific styling.
- **Bootstrap:** Used alongside Tailwind. Ensure there are no utility conflicts. Prefer Tailwind for new custom components.

## 💻 Coding Standards

### General Rules

- **Commenting:**
    - Always place comments **above** the code they describe.
    - **No inline comments** (e.g., `code; // comment` is forbidden).
    - Use simple comments for PHP and Vue files.
- **Naming:** Follow Laravel's naming conventions (PascalCase for Controllers/Models, camelCase for variables/methods in PHP, kebab-case for Vue components in templates).

### Laravel (Backend)

- Follow the **Action** pattern for complex logic (see `app/Actions`).
- Use **Services** for reusable business logic (see `app/Services`).
- Keep Controllers thin; delegate to Actions or Services.
- Ensure all new features have corresponding **Feature Tests** in `tests/Feature`.

### Vue.js (Frontend)

- Use the **Composition API** with `<script setup lang=ts>`.
- Organize components in `resources/js/components`.
- Use **TypeScript** for all new frontend code (`.ts`, `.vue`).
- Leverage **Composables** for reusable logic in `resources/js/composables`.

## 🧪 Testing & Validation

- Run tests before pushing changes: `php artisan test` or `vendor/bin/pest`.
- Use **Laravel Dusk** for browser-based testing when necessary.
- Ensure all UI changes are responsive and compatible with both Light and Dark modes (handled via `.dark` class).

# Git

- Never commit any files nor push anything to git. All these action must only be performed by humans!

---

_Note: This file takes precedence over general defaults. Always verify against these rules before finalizing changes._

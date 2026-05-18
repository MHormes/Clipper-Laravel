# Clipper-Laravel — Agent Instructions

This is the **Source of Truth** for all AI agent interactions. Consult this file before any major task.

## 🗺 Repository Intelligence Graph (RIG)
- **Navigation Rule**: Consult `RIG.json` first for architectural dependencies. Do not guess paths.
- **Map Maintenance**: If you change the project structure (adding/moving/deleting files), your final step must be to run:
  ```bash
  npx repomix --style json --no-files --output RIG.json
  ```

## 📜 Operational Protocols
- **Efficiency Protocol**: Plan before execution. Create a `.plan` for complex tasks. Read only the minimum files necessary.
- **Hierarchy Rule**: Local `AGENTS.md` files in sub-folders take precedence for domain-specific rules.
- **Direct References**: 
    - [README.md](README.md) - Project overview & tech stack.
    - [Ideation.md](Ideation.md) - Core intent and future goals.

## 🛠 Technology Stack
- **Backend**: PHP 8.x + Laravel
- **Frontend**: Vue.js 3 + Inertia.js (Composition API + TS)
- **Styling**: Tailwind CSS (v4) + Bootstrap
- **Build**: Vite

## 🎨 Styling & Appearance
### No Hardcoded Colors
All colors **must** use predefined theme variables in `resources/css/app.css`. 
Use Tailwind classes: `text-primary`, `bg-primary-background`, `border-border-color`, etc.

### No Tailwind `dark:` Prefix — Ever
**Never use `dark:` Tailwind prefix in any component.** The app uses a `.dark` class on `<html>` and CSS custom properties in `app.css` to handle theming. All color switching must happen via CSS variables.

- **Wrong**: `class="bg-white dark:bg-gray-900"` or `class="text-black dark:text-white"`
- **Right**: `class="bg-component-background"` or `class="text-primary-content"`

If a new color needs to differ between light/dark modes, add a new CSS variable to `resources/css/app.css` under both `:root` (light) and `.dark` (dark) sections, and register it in `@theme inline` with the `--color-` prefix. Available semantic variables: `--input-background`, `--alert-*-bg/border`, `--badge-success-bg`, `--primary-icon-bg`, `--hover-overlay`, `--hero-card-bg`, `--media-bg`, `--link-decoration`.

## 💻 General Coding Standards
- **Commenting**:
    - Always place comments **above** the code.
    - **No inline comments**.
    - Simple comments for PHP/Vue files.
- **Naming**: Laravel conventions (PascalCase Controllers/Models, camelCase PHP variables/methods, kebab-case Vue components in templates).

## 🧪 Testing & Validation
- Run tests: `php artisan test` or `vendor/bin/pest`.
- Ensure features have corresponding **Feature Tests** in `tests/Feature`.
- UI changes must be responsive and support Dark/Light modes.

## 🛑 Git
- Never commit or push to git. Humans only!

---
_Note: See nested `AGENTS.md` in `app/`, `resources/js/`, etc., for specific domain logic._

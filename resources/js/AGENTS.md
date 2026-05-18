# Frontend Logic — Agent Instructions

## Domain Constraints
- **Script Setup**: Use `<script setup lang="ts">` for all Vue components.
- **State Management**: Use Composables for shared logic (`resources/js/composables`).
- **Styling**: Prefer Tailwind CSS over custom CSS. Use theme variables from `resources/css/app.css`.
- **Dark Mode**: **Never use `dark:` Tailwind prefix.** All light/dark switching is handled by CSS custom properties in `app.css`. The `.dark` class on `<html>` activates dark values automatically. To use a color that must differ per mode, add a variable to `app.css` (`:root` for light, `.dark` for dark) and register it in `@theme inline`, then reference it as a Tailwind utility or `var(--name)` in the template.
- **Components**: Organize in `resources/js/components`. Maintain clear props/emits interfaces.
- **TypeScript**: Strictly typed; avoid `any`.

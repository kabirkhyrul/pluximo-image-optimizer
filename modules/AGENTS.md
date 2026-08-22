# Repository Guidelines

## Project Structure & Module Organization

This directory contains reusable modules for the Pluximo Support Tickets WordPress plugin. Each module keeps PHP classes in `src/` under its matching `Pluximo\...` namespace:

- `foundation/`: container, hooks, product registry, and shared contracts.
- `admin-shell/`: WordPress admin menu, page registration, and template rendering view engine.
- `ecosystem/`: product catalogue, API clients, showcase templates, CSS, and JavaScript.
- `support/`: support form, validation, diagnostics, transport, REST, and AJAX handling.

The parent plugin entry point is `../plugin.php`; `../autoload.php` maps module namespaces. Module directories are Git submodules, so commit module changes in their own repositories as well as updating pointers here.

## Build, Test, and Development Commands

There is no compiled build step or dependency manifest. From the plugin root, use:

```sh
git submodule update --init --recursive
find modules src -name '*.php' -print0 | xargs -0 -n1 php -l
wp plugin activate pluximo-plugin
```

The first command initializes modules, the second syntax-checks PHP, and the third activates the plugin in the configured local WordPress installation. After changes, exercise the relevant wp-admin page and REST/AJAX flow with `WP_DEBUG` enabled.

## Coding Style & Naming Conventions

Target PHP 7.4+ and WordPress 6.0+. Follow the existing WordPress PHP style: tabs for indentation, spaces inside control-structure parentheses, strict types, docblocks, and escaped/sanitized external data. Use PascalCase class names and files (`SupportManager.php`), snake_case WordPress callbacks, and lowercase hyphenated asset names. Keep namespaces aligned with `../autoload.php`. JavaScript uses tabs, single quotes, and semicolons; CSS selectors use the `pluximo-` prefix.

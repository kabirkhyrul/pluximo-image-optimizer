# Repository Guidelines for Pluximo Shared Modules

## Project Structure & Module Organization

This directory contains reusable, composable architecture modules for Pluximo WordPress plugins. Each module is maintained as an independent git repository/submodule and defines classes in `src/` under its matching `Pluximo\...` namespace:

- `foundation/`: Core runtime kernel, DI container, hook aggregator, product registry, and shared contracts (`Pluximo\Foundation`).
- `admin-shell/`: Shared WordPress admin menu, subpage registration, and path-safe template rendering (`Pluximo\AdminShell`).
- `ecosystem/`: Product catalogue, remote clients (WP.org, Pro API), showcase templates, and companion installer (`Pluximo\Ecosystem`).
- `support/`: Centralized support ticketing, system diagnostics collector, transport layer, and GDPR consent handling (`Pluximo\Support`).
- `asset-manager/`: Shared React component library, Tailwind CSS design system with `plx:` prefix, and Vite multi-entry build system.

The parent plugin entry point is `../pluximo-image-optimizer.php`; `../autoload.php` maps module namespaces. Module directories are Git submodules, so commit module changes in their own repositories as well as updating pointers here.

## Build, Test, and Development Commands

```sh
# Initialize all submodules
git submodule update --init --recursive

# Syntax-check PHP across modules and plugin source
find modules src -name '*.php' -print0 | xargs -0 -n1 php -l

# Activate plugin via WP-CLI
wp plugin activate pluximo-image-optimizer
```

## Architectural Guidelines & Anti-Patterns (Golden Rules)

1. **Never write custom singletons (`getInstance()`).** Always register services into the Foundation `Container` via `PluginServiceProvider`.
2. **Never call `add_action()` / `add_filter()` across random files.** Route all hooks through `HookManager`.
3. **Never call `add_menu_page()` directly.** Always register submenus under the shared `pluximo` menu using `AdminMenu::register_subpage()`.
4. **Never use raw `include` or `require` in page callbacks.** Use `View::output()` to enforce path-traversal protection and output-buffering safety.
5. **Never use unprefixed Tailwind classes in shared React components.** Always use the `plx:` prefix to prevent style pollution in `wp-admin`.
6. **Never access raw `$_GET` or `$_POST` directly.** Use Foundation's `Request` object which guarantees nonce verification.

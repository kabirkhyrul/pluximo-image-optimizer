# Pluximo Image Optimizer

An enterprise-grade, modular WordPress plugin that automatically converts PNG images to modern WebP or AVIF formats on upload and provides task-oriented batch conversion of existing Media Library assets.

---

## 🏗️ Architecture & Module Ecosystem

This plugin is built on top of the **Pluximo Reusable Architecture**, using shared Git submodules under `modules/`. Each module has a strictly defined single responsibility to prevent code duplication, singleton bloat, and architectural decay.

```
pluximo-image-optimizer/
├── autoload.php                        # Idempotent PSR-4 autoloader for plugin & modules
├── constants.php                       # Core directory & URL constants
├── pluximo-image-optimizer.php         # Plugin entry point & activation hooks
├── src/                                # Plugin-specific domain logic
│   ├── Admin/
│   │   ├── AdminSettings.php           # Settings page & asset enqueue controller
│   │   └── BulkConverter.php           # AJAX bulk conversion processor
│   ├── Drivers/
│   │   ├── DriverInterface.php         # Image conversion driver contract
│   │   ├── AvifDriver.php              # AVIF engine (GD, Imagick, WP_Image_Editor)
│   │   └── WebpDriver.php              # WebP engine (GD, Imagick, WP_Image_Editor)
│   ├── ConverterEngine.php             # Unified format detection & conversion coordinator
│   ├── ImageDrivers.php                # Driver resolver & hardware capability checker
│   ├── ImageOptimizerServiceProvider.php # DI service provider & hook registrar
│   ├── MediaHelper.php                 # Backup manager & attachment metadata sync
│   ├── Plugin.php                      # Product definition extending AbstractPluginDefinition
│   └── UploadHandler.php               # Hooks into upload & image editor pipelines
├── templates/                          # PHP view templates
│   └── admin/
│       └── settings-page.php           # Server-rendered admin settings & bulk scan UI
├── assets/                             # Compiled production assets (CSS, JS)
├── assets-src/                         # Source stylesheets and JavaScript for classic admin
│   ├── css/admin-style.css
│   └── js/bulk-converter.js
├── modules/                            # Shared Pluximo ecosystem submodules
│   ├── foundation/                     # Runtime kernel, DI container, hook aggregator [README](modules/foundation/README.md)
│   ├── admin-shell/                    # Shared 'Pluximo' admin menu & view renderer [README](modules/admin-shell/README.md)
│   ├── ecosystem/                      # Cross-product showcase & addon installer [README](modules/ecosystem/README.md)
│   ├── support/                        # Support desk dispatcher & diagnostics collector [README](modules/support/README.md)
│   ├── asset-manager/                  # React component library & multi-entry build [README](modules/asset-manager/README.md)
│   └── AGENTS.md                       # Guidelines for autonomous AI coding agents
└── README.md
```

---

## 🧭 Module Use Cases & Interaction Matrix

| Module | Core Use Case in this Plugin | Where It Is Consumed | Detailed Docs |
|---|---|---|---|
| **`foundation`** | Bootstraps plugin lifecycle, registers singletons into the shared DI [`Container`](modules/foundation/src/Container.php), and routes hooks through [`HookManager`](modules/foundation/src/HookManager.php). | [`Plugin.php`](src/Plugin.php), [`ImageOptimizerServiceProvider.php`](src/ImageOptimizerServiceProvider.php) | [Foundation README](modules/foundation/README.md) |
| **`admin-shell`** | Registers the "PNG Optimizer" subpage under the unified top-level **Pluximo** menu (`AdminMenu::register_subpage()`), and securely renders templates via [`View`](modules/admin-shell/src/View.php). | [`AdminSettings.php`](src/Admin/AdminSettings.php) | [Admin Shell README](modules/admin-shell/README.md) |
| **`ecosystem`** | Powers the main **Pluximo Home** showcase page, enabling users to discover, install, and activate companion tools directly within the dashboard. | Booted via `'ecosystem'` flag in [`Plugin.php`](src/Plugin.php); listens to `pluximo_admin_home_render`. | [Ecosystem README](modules/ecosystem/README.md) |
| **`support`** | Collects sanitized environment diagnostics and sends user inquiries to the central support API via REST or AJAX. | Booted via `'support'` flag in [`Plugin.php`](src/Plugin.php); listens to `pluximo_admin_support_form_render`. | [Support README](modules/support/README.md) |
| **`asset-manager`** | Provides modern React components and Vite build pipeline for Pluximo SPAs (`image-admin`). | `modules/asset-manager/src/image-admin/` | [Asset Manager README](modules/asset-manager/README.md) |

---

## 🚫 Guardrails: How to Avoid Misuse and Architectural Garbage

To keep this codebase clean, performant, and maintainable across teams and automated agents, strictly follow these rules:

1. **Do Not Create Custom Singletons:** Never write `public static function get_instance()`. Always register services as singletons in [`ImageOptimizerServiceProvider`](src/ImageOptimizerServiceProvider.php) using `$this->singleton( MyClass::class, ... )`.
2. **Do Not Call `add_action` or `add_filter` Directly in Core Logic:** Register all WordPress hooks inside [`ImageOptimizerServiceProvider`](src/ImageOptimizerServiceProvider.php) via `$this->action()` and `$this->filter()`. This guarantees deterministic boot ordering and unit testability.
3. **Do Not Call `add_menu_page()`:** Always register admin subpages via [`AdminMenu::register_subpage()`](modules/admin-shell/src/AdminMenu.php) so that all Pluximo products remain unified under the single `'pluximo'` menu.
4. **Do Not Use Raw `include` or `require` for Admin Views:** Always render admin screens through [`View::output()`](modules/admin-shell/src/View.php). This prevents directory traversal vulnerabilities (`../../`) and protects template variables from leaking into global scope.
5. **Do Not Unprefix Tailwind CSS Classes in React:** When authoring React interfaces inside `asset-manager`, all Tailwind classes must use the configured `plx:` prefix (e.g. `plx:flex`, `plx:gap-4`, `plx:bg-white`) to eliminate style collisions with WordPress core.
6. **Decouple Classic Admin from React SPA:** The classic settings screen uses `templates/admin/settings-page.php` with `assets/js/bulk-converter.js` and admin-ajax. The React SPA in `modules/asset-manager/src/image-admin/` interacts via REST API (`/wp-json/pluximo-image-optimizer/v1/`). Do not mix their states or create duplicate endpoints.

---

## 🛠️ Build and Development Workflow

### 1. Initialize Git Submodules
```sh
git submodule update --init --recursive
```

### 2. Classic Admin Assets (Root)
Build the classic admin stylesheet and bulk converter script:
```sh
bun install
bun run build       # Emits to assets/css/ and assets/js/
bun run dev         # Watches assets-src for local iteration
```

### 3. Modern React SPA Assets (`asset-manager`)
Build the standalone React SPA:
```sh
cd modules/asset-manager
bun install
bun run build:image-admin   # Emits to dist/image-admin/
```

### 4. Code Quality & Linting
```sh
# PHP Syntax validation
find modules src -name '*.php' -print0 | xargs -0 -n1 php -l

# JavaScript / React linting
cd modules/asset-manager && bun run lint
```

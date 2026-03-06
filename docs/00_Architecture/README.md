# Architecture

## What Is This Bundle?

The Admin Bundle provides the **backend UI** for OpenDXP. It renders the document tree,
asset manager, object editor, user management, and all other admin panels using the
[ExtJS](https://www.sencha.com/products/extjs/) framework on the frontend and Symfony
controllers on the backend.

## Relationship to opendxp Core

This bundle depends heavily on the **opendxp core** (`open-dxp/opendxp`). The core provides:

| From core                 | Used by admin-bundle                            |
|---------------------------|-------------------------------------------------|
| `OpenDxp\Model\*`         | Document, Asset, DataObject, Site models        |
| `OpenDxp\Event\*`         | Core events (DocumentEvents, AssetEvents, etc.) |
| `OpenDxp\Controller\*`    | Base controller classes                         |
| `OpenDxp\Tests\Support\*` | Base test classes (ModelTestCase)               |
| `OpenDxp\Config`          | Global configuration                            |

The admin-bundle adds its **own** event layer on top (`src/Event/AdminEvents.php`) for
events that are specific to the admin UI lifecycle.

## Source Layout

```
src/
├── Command/              # CLI commands (e.g. cache warm-up, admin user management)
├── Controller/
│   ├── Admin/            # Admin controllers, one subfolder per element type
│   │   ├── Asset/
│   │   ├── DataObject/
│   │   ├── Document/     # DocumentController — tree, site panel, site custom settings
│   │   └── ...
│   └── Traits/           # Shared controller traits
├── DependencyInjection/  # Bundle extension + compiler passes
├── Enum/                 # PHP enums for typed config values
├── Event/                # Admin event classes + AdminEvents constants
├── EventListener/        # Symfony event subscribers (internal)
├── Helper/               # Stateless service helpers (e.g. GridHelperService)
├── Model/                # Admin-only models (GridConfig, GridConfigShare, etc.)
├── Perspective/          # Perspective resolution and serialization
├── Security/             # Admin authentication, authenticators, security tokens
├── Service/              # Application services (grid data, workflow)
├── System/               # System-level services
├── Translation/          # Admin translation handling
└── Twig/                 # Twig extensions for admin templates
```

## Frontend Assets

JavaScript and CSS live in `public/js/` and `public/css/`. The bundle uses
[Webpack Encore](https://symfony.com/doc/current/frontend/encore/simple-example.html)
(`webpack.config.js`) for asset compilation.

Key JS entry points:
- `public/js/opendxp/events.js` — all frontend event constants
- `public/js/opendxp/document/tree.js` — document tree rendering

## Configuration Namespace

The bundle registers its configuration under `opendxp_admin`:

```yaml
opendxp_admin:
    custom_admin_path_identifier: ~
    custom_admin_route_name: ~
    user:
        default_key_bindings: ~
```
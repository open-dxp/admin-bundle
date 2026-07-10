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
├── Attribute/             # PHP attributes driving centralized listener behavior (e.g. AsHtmlContentTypeResponse)
├── Command/               # CLI commands (e.g. cache warm-up, admin user management)
├── Controller/
│   ├── Admin/             # Admin controllers, one subfolder per element type, thin pass-throughs to Handlers
│   │   ├── Asset/
│   │   ├── DataObject/
│   │   ├── Document/      # DocumentController: tree, site panel, site custom settings
│   │   └── ...
│   └── Traits/            # Shared controller traits
├── DependencyInjection/   # Bundle extension + compiler passes
├── Dto/                   # Data transfer objects for HTTP responses (e.g. ApiResponse)
├── Enricher/              # Adds admin-only data (permissions, admin styles) to tree/editor payloads
├── Enum/                  # PHP enums for typed config values
├── Event/                 # Admin event classes + AdminEvents constants
├── EventListener/         # Symfony event subscribers (internal)
├── Exception/             # Admin-specific exceptions (e.g. AdminOperationFailedException)
├── Factory/               # Factories for services that can't be constructed via plain DI
├── Handler/               # Business logic, one invokable class per action, see below
├── Helper/                # Stateless service helpers (e.g. GridHelperService)
├── Model/                 # Admin-only models (GridConfig, GridConfigShare, etc.)
├── Payload/               # Shared request DTOs consumed by Handlers; action-specific ones live next to their Handler
├── Perspective/           # Perspective resolution and serialization
├── Security/              # Admin authentication, authenticators, security tokens
├── Service/               # Application services shared across Handlers (grid data, workflow, element resolution)
├── System/                # System-level services
├── Translation/           # Admin translation handling
└── Twig/                  # Twig extensions for admin templates
```

## Handler / Payload / Service Pattern

Admin controller actions contain no business logic. Each action is a thin pass-through that
resolves a typed request Payload, invokes a Handler, and maps the result to JSON:

```php
#[Route('/save', name: 'save', methods: ['PUT'])]
public function saveAction(
    SaveClassDefinitionPayload $payload,
    SaveClassDefinitionHandler $handler,
): JsonResponse {
    $result = $handler($payload);

    return $this->adminJson(ApiResponse::ok(['class' => $result->class]));
}
```

| Layer    | Location                                          | Responsibility                                                                                                                                               |
|----------|---------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Payload  | `src/Payload/Common/` or colocated with a Handler | Typed request DTO. `fromRequest()` is resolved automatically by `ExtJsValueResolver` before the controller action runs, the only place request data is read. |
| Handler  | `src/Handler/**`                                  | One invokable (`__invoke`) class per action. Holds all business logic. Transport-agnostic, never receives the HTTP `Request`.                                |
| Result   | colocated with its Handler                        | Typed return value, mapped to a JSON response by the controller.                                                                                             |
| Service  | `src/Service/**`                                  | Logic shared across multiple Handlers (grid data assembly, workflow resolution, element resolution).                                                         |
| Enricher | `src/Enricher/**`                                 | Adds admin-only presentation data (permissions, admin styles) to tree/editor node data, shared across Handlers.                                              |

**Error handling:** 

Handlers always throw on failure, they never return a `success: false` or `errorMessage` field. 

`AdminExceptionListener` (pre-existing, unchanged) converts any exception raised during an 
admin XHR request into `{success: false, message, ...}` JSON at the exception's real HTTP status.

For an expected, recoverable business-rule failure that the ExtJS frontend handles locally as `success: false` at HTTP 200, not a real HTTP error, 
throw `OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException` instead of a generic exception; the listener maps it to 200 explicitly rather than a 4xx/5xx status.

## Frontend Assets

JavaScript and CSS live in `public/js/` and `public/css/`. The bundle uses
[Webpack Encore](https://symfony.com/doc/current/frontend/encore/simple-example.html)
(`webpack.config.js`) for asset compilation.

Key JS entry points:
- `public/js/opendxp/events.js`: all frontend event constants
- `public/js/opendxp/document/tree.js`: document tree rendering

## Configuration Namespace

The bundle registers its configuration under `opendxp_admin`:

```yaml
opendxp_admin:
    custom_admin_path_identifier: ~
    custom_admin_route_name: ~
    user:
        default_key_bindings: ~
```

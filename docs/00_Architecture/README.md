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
├── CustomView/            # Custom document/asset/object tree view configuration
├── DataObject/            # Grid column config element definitions shared by DataObject grid Handlers
├── DependencyInjection/   # Bundle extension + compiler passes
├── Dto/                   # Data transfer objects (grid configs, site custom settings, admin bootstrap settings)
├── Enricher/              # Adds admin-only data (permissions, admin styles) to tree/editor payloads
├── Enum/                  # PHP enums for typed config values
├── Event/                 # Admin event classes + AdminEvents constants
├── EventListener/         # Symfony event subscribers (internal)
├── Exception/             # Admin-specific exceptions (e.g. AdminOperationFailedException)
├── Factory/               # Factories for services that can't be constructed via plain DI
├── GDPR/                  # GDPR data provider integrations (export/anonymize document, asset, object, mail data)
├── Handler/               # Business logic, one invokable class per action, see below
├── Helper/                # Stateless service helpers (e.g. GridHelperService)
├── Http/                  # ExtJsValueResolver: resolves Payload::fromRequest() as a controller argument
├── Model/                 # Admin-only models (GridConfig, GridConfigShare, etc.)
├── Payload/               # Shared request DTOs consumed by Handlers; action-specific ones live next to their Handler
├── Perspective/           # Perspective resolution and serialization
├── Repository/            # Persistence for admin-only data (e.g. per-user dashboard config), keyed by explicit params, no bound state
├── Security/              # Admin authentication, authenticators, security tokens
├── Service/               # Application services shared across Handlers (grid data, workflow, element resolution)
├── Session/               # Session access boundary: SessionGatewayInterface/SessionIdentityInterface and their Gateway/ implementations, see below
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
    return $this->apiJson($handler($payload));
}
```

`AdminAbstractController::apiJson()` serializes a Handler's Result into the wire envelope
`{success: true, ...resultProperties}`. Pass `rootProperty: 'nodes'` when the established
wire contract for an endpoint is the bare value of a single Result property instead of the
enveloped shape. For a void Handler with nothing to report, use `apiOk()` (`{success: true}`).
Never build the response body by hand with `adminJson()` in a controller action, if a
Result's shape does not match what an endpoint needs to return, change the Result and the
Handler, not the controller.

| Layer      | Location                                          | Responsibility                                                                                                                                               |
|------------|---------------------------------------------------|--------------------------------------------------------------------------------------------------------------------------------------------------------------|
| Payload    | `src/Payload/Common/` or colocated with a Handler | Typed request DTO. `fromRequest()` is resolved automatically by `ExtJsValueResolver` before the controller action runs, the only place request data is read. |
| Handler    | `src/Handler/**`                                  | One invokable (`__invoke`) class per action. Holds all business logic. Transport-agnostic, never receives the HTTP `Request`.                                |
| Result     | colocated with its Handler                        | Typed return value, mapped to a JSON response by the controller.                                                                                             |
| Service    | `src/Service/**`                                  | Logic shared across multiple Handlers (grid data assembly, workflow resolution, element resolution).                                                         |
| Repository | `src/Repository/**`                               | Persistence only.                                                                                                                                            |
| Enricher   | `src/Enricher/**`                                 | Adds admin-only presentation data (permissions, admin styles) to tree/editor node data, shared across Handlers.                                              |

**Error handling:** 

Handlers always throw on failure, they never return a `success: false` or `errorMessage` field. 

`AdminExceptionListener` converts any exception raised during an admin XHR request into `{success: false, message, ...}` JSON at the exception's real HTTP status.

### AdminOperationFailedException
For an expected, recoverable business-rule failure that the admin UI handles locally as `success: false` at HTTP 200, not a real HTTP error, 
throw `OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException` instead of a generic exception; the listener maps it to 200 explicitly rather than a 4xx/5xx status.

### ElementLockedException
Some exceptions carry their own dedicated response shape instead of the generic `{success: false, message}` body: `OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException` is mapped by the listener to a 200 response with an `editlock` payload,
which the admin UI uses to show the lock dialog. 

## Session Access

Controllers and Payloads never touch Symfony's HTTP session (`SessionInterface`, `AttributeBagInterface`, `Request::getSession()`), not even to read it. 
A Payload only carries values taken from the current `Request`; anything session-derived is looked up by the Handler itself, at the point it is actually needed. Only two kinds of classes may reference session storage:

1. **A Gateway** implementing `OpenDxp\Bundle\AdminBundle\Session\SessionGatewayInterface`, living in `src/Session/Gateway/`. Each Gateway wraps exactly one session bag (see the `BAG_*` constants on `SessionGatewayInterface` for the full list of bags in use) behind a small, domain-named API, e.g. `CopySessionGateway::rememberParentId()`, not a raw `get()`/`set()`. Handlers and Services inject the Gateway they need.
2. **`OpenDxp\Bundle\AdminBundle\Session\SessionIdentityInterface`**, for the narrower case of needing only the current session id as a correlation token (lock ownership, bootstrap settings), not a session value. Carries no mutable state, so it may be injected anywhere.

Controller actions never contain session code, not even a call into a Gateway. Since that means an endpoint's session footprint is otherwise invisible without opening its Handler, mark the action with an attribute instead:

```php
#[SessionGatewayAware(CopySessionGateway::class)]
#[Route('/copy', name: 'opendxp_admin_asset_copy', methods: ['POST'])]
public function copyAction(CopyAssetPayload $payload, CopyAssetHandler $handler): JsonResponse
{
    $handler($payload);

    return $this->apiOk();
}
```

Use `#[SessionIdentityAware]` (no argument) when only `SessionIdentityInterface` is involved.

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

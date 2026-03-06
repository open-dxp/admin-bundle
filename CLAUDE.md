# Admin Bundle — Claude Instructions

## Project Overview

OpenDXP Admin Bundle provides the backend UI for OpenDXP. It is built on [ExtJS](https://www.sencha.com/products/extjs/) and depends heavily on the **opendxp core** (`../opendxp`).

**Dependency on opendxp core:** This bundle depends heavily on opendxp core (models, base controllers, test infrastructure). The core location depends on the developer's setup — when you need to read core source files, check these paths in order and use the first one that exists:

1. `../opendxp/` — sibling directory (monorepo / symlinked workspace)
2. `../../vendor/open-dxp/opendxp/` — project root one level up
3. `../../../vendor/open-dxp/opendxp/` — project root two levels up

When Claude is started from **opendxp core**, admin-specific topics (UI extension, admin events, perspectives, permissions) are documented in **this** bundle — see `docs/`.

## Source Structure

```
src/
├── Command/              # CLI commands
├── Controller/Admin/     # Admin controllers (Document, Asset, DataObject, GDPR)
│   └── Document/         # Incl. DocumentController (site panel, document tree)
├── Controller/Traits/    # Shared controller traits
├── DependencyInjection/  # Bundle configuration + compiler passes
├── Enum/                 # PHP enums (e.g. SiteCustomConfigNodeType)
├── Event/                # Event classes + AdminEvents constants
├── EventListener/        # Symfony event subscribers
├── Helper/               # Service helpers (e.g. GridHelperService)
├── Model/                # Admin-specific models (GridConfig, etc.)
├── Perspective/          # Perspective service logic
├── Security/             # Auth, authenticators, security tokens
├── Service/              # Grid data, workflow, etc.
├── System/               # System-level services
├── Translation/          # Translation handling
└── Twig/                 # Twig extensions
```

**Key reference files:**
- `src/Event/AdminEvents.php` — all event name constants exposed by this bundle
- `src/Event/SiteCustomSettingsEvent.php` — example of an admin event class
- `src/Enum/SiteCustomConfigNodeType.php` — example enum for config nodes
- `src/Controller/Admin/Document/DocumentController.php` — main document/site controller

## Documentation

All documentation lives in `docs/`. See `docs/README.md` for the full index.

### Structure

```
docs/
├── README.md                               ← master index (update when adding sections)
├── 00_Architecture/
│   └── README.md                           ← bundle overview, core dependency explained
├── 10_Extension_Points/
│   ├── README.md                           ← what other bundles can extend
│   ├── 01_Events.md                        ← AdminEvents reference + subscription examples
│   ├── 02_Admin_UI_Assets.md               ← loading JS/CSS into the admin UI
│   ├── 03_Admin_UI_JavaScript.md           ← ExtJS event system, menus, key bindings
│   ├── 04_Perspectives.md                  ← backend UI perspectives configuration
│   ├── 05_Permissions.md                   ← adding custom permissions
│   ├── 06_Deeplinks.md                     ← deeplinks into admin interface
│   └── 07_Custom_Admin_Login.md            ← custom login entry point
├── 20_Documents/
│   ├── README.md
│   └── 01_Site_Custom_Settings.md          ← site-specific custom settings via events
└── 90_Testing/
    └── README.md                           ← test conventions and how to run tests
```

### Naming conventions
- Folders: `NN_FunctionalArea/` — 10-step numbering leaves room for inserts
- Files: `NN_TopicName.md` — numbered within folder (01, 02, …)
- Each folder has a `README.md` as section index

### When to document

Document every **extension point** exposed to other bundles or applications:
- New event constant in `AdminEvents` → update `docs/10_Extension_Points/01_Events.md`
- New event class → add a doc in the relevant feature section with a full subscriber example
- New enum for config nodes → document available values and their meaning
- New controller endpoint used externally → document in the relevant feature section

### opendxp/doc/ vs. admin-bundle/docs/

Content belongs in **admin-bundle/docs/** when it is about:
- Admin UI extension (JS events, menus, assets)
- AdminEvents and event subscribers
- Backend-only features (perspectives, custom login, deeplinks, permissions)
- Site/document admin panel customization

Content belongs in **opendxp/doc/** when it is about:
- Core models and PHP API (Documents, Assets, DataObjects)
- Core events (non-admin: DocumentEvents, AssetEvents, etc.)
- Routing, MVC, deployment, infrastructure

When opendxp/doc/ contained content that belongs here, the opendxp file becomes a redirect stub pointing to admin-bundle docs.

### Document template

# Feature Name

One-line description: what it does and why it exists.

## Overview

Brief explanation of the feature, when to use it.

## API Reference

| Class / Constant             | Description           |
|------------------------------|-----------------------|
| `AdminEvents::CONSTANT_NAME` | When this event fires |
| `SomeEvent::addNode()`       | What the method does  |

## Example

```php
<?php

namespace App\EventListener;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\SomeEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AdminEvents::CONSTANT_NAME => 'onEvent',
        ];
    }

    public function onEvent(SomeEvent $event): void
    {
        // ...
    }
}
```

## Stored Data / Result

Where and how the data is persisted or used downstream.

## See Also

- [Related opendxp/doc page](https://github.com/open-dxp/opendxp/blob/1.x/doc/...)
- [AdminEvents source](https://github.com/open-dxp/admin-bundle/blob/1.x/src/Event/AdminEvents.php)


## Tests
See `tests/CLAUDE.md` for full test conventions, base classes, and examples.
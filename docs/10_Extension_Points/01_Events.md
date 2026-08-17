# Admin Events

All event constants for the admin-bundle are defined in
[`src/Event/AdminEvents.php`](https://github.com/open-dxp/admin-bundle/blob/1.x/src/Event/AdminEvents.php).

## How to Subscribe

Implement `EventSubscriberInterface` in your bundle and return the event constant(s) you want to handle:

```php
<?php

namespace MyBundle\EventListener;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyAdminListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AdminEvents::DOCUMENT_GET_PRE_SEND_DATA => 'onPreSendData',
        ];
    }

    public function onPreSendData(ElementAdminStyleEvent $event): void
    {
        // ...
    }
}
```

Symfony autoconfiguration registers the subscriber automatically when `EventSubscriberInterface` is implemented.

## Event Categories

### Document Events

| Constant                         | Event Class | Description                                    |
|----------------------------------|-------------|------------------------------------------------|
| `DOCUMENT_GET_PRE_SEND_DATA`     | —           | Before document data is sent to the frontend   |
| `DOCUMENT_LIST_BEFORE_LIST_LOAD` | —           | Before document listing is loaded              |
| `DOCUMENT_LIST_AFTER_LIST_LOAD`  | —           | After document listing is loaded               |
| `DOCUMENT_GET_IS_LOCKED`         | —           | Before the edit lock is handled for a document |

### Asset Events

| Constant                      | Event Class | Description                                  |
|-------------------------------|-------------|----------------------------------------------|
| `ASSET_GET_PRE_SEND_DATA`     | —           | Before asset data is sent to the frontend    |
| `ASSET_LIST_BEFORE_LIST_LOAD` | —           | Before asset listing is loaded               |
| `ASSET_LIST_AFTER_LIST_LOAD`  | —           | After asset listing is loaded                |
| `ASSET_GET_IS_LOCKED`         | —           | Before the edit lock is handled for an asset |

### Object Events

| Constant                       | Event Class | Description                                       |
|--------------------------------|-------------|---------------------------------------------------|
| `OBJECT_GET_PRE_SEND_DATA`     | —           | Before data object data is sent to the frontend   |
| `OBJECT_LIST_BEFORE_LIST_LOAD` | —           | Before object listing is loaded                   |
| `OBJECT_LIST_AFTER_LIST_LOAD`  | —           | After object listing is loaded                    |
| `OBJECT_GET_IS_LOCKED`         | —           | Before the edit lock is handled for a data object |

### Element Style Events

| Constant                               | Event Class              | Description                                            |
|----------------------------------------|--------------------------|--------------------------------------------------------|
| `ELEMENT_ADMIN_STYLE_GET_FOR_DOCUMENT` | `ElementAdminStyleEvent` | Customize admin style (icon, CSS class) for a document |
| `ELEMENT_ADMIN_STYLE_GET_FOR_ASSET`    | `ElementAdminStyleEvent` | Customize admin style for an asset                     |
| `ELEMENT_ADMIN_STYLE_GET_FOR_OBJECT`   | `ElementAdminStyleEvent` | Customize admin style for a data object                |

### Login Events

| Constant            | Event Class                   | Description                           |
|---------------------|-------------------------------|---------------------------------------|
| `LOGIN_CREDENTIALS` | `Login\LoginCredentialsEvent` | After login credentials are submitted |
| `LOGIN_FAILED`      | `Login\LoginFailedEvent`      | After a failed login attempt          |

### Perspective Events

| Constant                       | Event Class | Description                                  |
|--------------------------------|-------------|----------------------------------------------|
| `PERSPECTIVE_PRE_GET_RUNTIME`  | —           | Before perspective runtime data is assembled |
| `PERSPECTIVE_POST_GET_RUNTIME` | —           | After perspective runtime data is assembled  |

### Site Events

| Constant               | Event Class               | Description                                                                       |
|------------------------|---------------------------|-----------------------------------------------------------------------------------|
| `SITE_CUSTOM_SETTINGS` | `SiteCustomSettingsEvent` | Fired when the site panel renders or saves, allows injecting custom config fields |

See [Site Custom Settings](../20_Documents/01_Site_Custom_Settings.md) for a full example.

## See Also

- [AdminEvents source](https://github.com/open-dxp/admin-bundle/blob/1.x/src/Event/AdminEvents.php)
- [Core events (non-admin)](https://github.com/open-dxp/opendxp/blob/1.x/doc/20_Extending_OpenDxp/11_Event_API_and_Event_Manager.md)
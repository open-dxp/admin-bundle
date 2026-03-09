# Extension Points

This section documents how other bundles and applications can extend or customize
the admin UI provided by this bundle.

## Overview

The admin-bundle exposes extension points at two levels:

**PHP (server-side)**
- Event system via `AdminEvents` constants and typed event classes
- Custom permissions registered in the database

**JavaScript (client-side)**
- JS/CSS injection into the admin UI via bundle interface or event listeners
- ExtJS UI events for adding menus, panels, key bindings

## Topics

| #                               | Topic               | Summary                                                          |
|---------------------------------|---------------------|------------------------------------------------------------------|
| [01](01_Events.md)              | Events              | All `AdminEvents` constants, when they fire, subscriber examples |
| [02](02_Admin_UI_Assets.md)     | Admin UI Assets     | How to load custom JS and CSS into the admin backend             |
| [03](03_Admin_UI_JavaScript.md) | Admin UI JavaScript | ExtJS events, adding navigation items, key bindings              |
| [04](04_Perspectives.md)        | Perspectives        | Configuring different backend UI layouts                         |
| [05](05_Permissions.md)         | Permissions         | Adding custom permission keys                                    |
| [06](06_Deeplinks.md)           | Deeplinks           | Linking directly into admin from external apps                   |
| [07](07_Custom_Admin_Login.md)  | Custom Admin Login  | Changing the `/admin` entry point                                |

## How Events Work

The admin-bundle follows the standard Symfony event dispatcher pattern.
Event constants are defined as `public const string` on `AdminEvents`.
Event classes live in `src/Event/` and extend `Symfony\Contracts\EventDispatcher\Event`.

Other bundles subscribe to admin events by implementing `EventSubscriberInterface`:

```php
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class MyListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AdminEvents::SOME_EVENT => 'onSomeEvent',
        ];
    }

    public function onSomeEvent(SomeAdminEvent $event): void
    {
        // ...
    }
}
```

Register the listener in your bundle's `services.yaml` — Symfony autoconfiguration
picks up `EventSubscriberInterface` automatically.
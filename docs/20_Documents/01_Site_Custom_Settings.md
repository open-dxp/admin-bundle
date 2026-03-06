# Site Custom Settings

The site configuration panel in the admin UI can be extended with custom fields
by subscribing to the `AdminEvents::SITE_CUSTOM_SETTINGS` event.

This allows other bundles to add their own configuration fields (inputs, dropdowns, checkboxes)
that are stored per-site in `Site::getCustomSettings()` / `Site::setCustomSettings()`.

## When the Event Fires

The event fires in two situations:
1. **GET** — when the site panel renders (`getSiteCustomSettingsAction`), to know which fields to display
2. **PUT** — when the site is saved (`updateSiteAction`), to know which request values to persist

## API Reference

### `AdminEvents::SITE_CUSTOM_SETTINGS`

```php
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;

AdminEvents::SITE_CUSTOM_SETTINGS // 'opendxp.admin.site.customSettings'
```

### `SiteCustomSettingsEvent`

| Method                                                  | Description                             |
|---------------------------------------------------------|-----------------------------------------|
| `getSite(): Site`                                       | The site being configured               |
| `addConfigNode(type, scope, name, label, config): void` | Register a custom field                 |
| `getConfigNodes(): array`                               | All registered fields, grouped by scope |

### `SiteCustomConfigNodeType` (enum)

| Case       | ExtJS field type | Use for                       |
|------------|------------------|-------------------------------|
| `INPUT`    | `textfield`      | Single-line text              |
| `TEXT`     | `textarea`       | Multi-line text               |
| `CHECKBOX` | `checkbox`       | Boolean toggle                |
| `DROPDOWN` | `combobox`       | Select from a list of options |

### `addConfigNode()` Parameters

| Parameter | Type                       | Description                                                                                           |
|-----------|----------------------------|-------------------------------------------------------------------------------------------------------|
| `$type`   | `SiteCustomConfigNodeType` | The field type                                                                                        |
| `$scope`  | `string`                   | Groups fields in the panel (e.g. `'app'`, `'seo'`); also used as key prefix when reading back values |
| `$name`   | `string`                   | Field identifier within the scope                                                                     |
| `$label`  | `string`                   | Display label in the UI                                                                               |
| `$config` | `array`                    | Additional ExtJS field config (e.g. `store` for dropdowns, `required`)                                |

## Stored Data

Values are persisted via `Site::setCustomSettings()` after save, grouped by scope.

### Reading Values

`getCustomSettings()` accepts an optional scope argument:

```php
$site = Site::getById($id);

// read a single scope — returns [] if the scope doesn't exist
$appSettings = $site->getCustomSettings('app');
$zone = $appSettings['zone'] ?? null;

// read all scopes at once
$all = $site->getCustomSettings();
// $all === ['app' => ['zone' => 'store'], 'seo' => ['tracking_id' => 'UA-123']]
```

## Example: Adding a Zone Dropdown

This example adds a required dropdown:

```php
<?php

namespace MyBundle\EventListener\Admin;

use OpenDxp\Bundle\AdminBundle\Enum\SiteCustomConfigNodeType;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\SiteCustomSettingsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SiteCustomConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AdminEvents::SITE_CUSTOM_SETTINGS => 'addPropertyToCustomSettings',
        ];
    }

    public function addPropertyToCustomSettings(SiteCustomSettingsEvent $event): void
    {
        $event->addConfigNode(
            type: SiteCustomConfigNodeType::DROPDOWN,
            scope: 'app',
            name: 'my_property',
            label: 'My Property',
            config: [
                'required' => true,
                'store'    => [
                    ['label' => 'Property 1', 'value' => 'property_1'],
                    ['label' => 'Property 2', 'value' => 'property_2'],
                ],
            ]
        );
    }
}
```

Register in `services.yaml` (autoconfiguration handles `EventSubscriberInterface`):

```yaml
services:
    MyBundle\EventListener\Admin\SiteCustomConfigListener:
        tags:
            - { name: kernel.event_subscriber }
```

## Multiple Scopes

Multiple bundles can each add their own scoped fields independently:

```php
// Bundle A
$event->addConfigNode(SiteCustomConfigNodeType::INPUT, 'seo', 'tracking_id', 'GA Tracking ID', []);

// Bundle B
$event->addConfigNode(SiteCustomConfigNodeType::CHECKBOX, 'myapp', 'feature_enabled', 'Enable Feature', []);
```

Results in:
```php
$site->getCustomSettings() === [
    'seo'   => ['tracking_id' => 'UA-123456'],
    'myapp' => ['feature_enabled' => '1'],
]
```

## See Also

- [`AdminEvents::SITE_CUSTOM_SETTINGS` source](https://github.com/open-dxp/admin-bundle/blob/1.x/src/Event/AdminEvents.php)
- [`SiteCustomSettingsEvent` source](https://github.com/open-dxp/admin-bundle/blob/1.x/src/Event/SiteCustomSettingsEvent.php)
- [`SiteCustomConfigNodeType` source](https://github.com/open-dxp/admin-bundle/blob/1.x/src/Enum/SiteCustomConfigNodeType.php)
- [Admin Events overview](../10_Extension_Points/01_Events.md)
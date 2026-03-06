# Site Custom Settings

The site configuration panel in the admin UI can be extended with custom fields
by subscribing to the `AdminEvents::SITE_CUSTOM_SETTINGS` event.

This allows other bundles to add their own configuration fields (inputs, dropdowns, checkboxes)
that are stored per-site in `Site::getCustomSettings()`.

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

| Method                                            | Description                             |
|---------------------------------------------------|-----------------------------------------|
| `getSite(): Site`                                 | The site being configured               |
| `addConfigNode(config, scope, name, label): void` | Register a custom field                 |
| `getConfigNodes(): array`                         | All registered fields, grouped by scope |

### `addConfigNode()` Parameters

| Parameter | Type                  | Description                                                                              |
|-----------|-----------------------|------------------------------------------------------------------------------------------|
| `$config` | `NodeConfigInterface` | Typed DTO — determines field type and available options                                  |
| `$scope`  | `string`              | Groups fields in the panel (e.g. `'app'`, `'seo'`); used as key when reading back values |
| `$name`   | `string`              | Field identifier within the scope                                                        |
| `$label`  | `string`              | Display label in the UI                                                                  |

### Config DTOs (`src/Dto/SiteCustomSettings/`)

Each DTO corresponds to one ExtJS field type and exposes only the options that are valid for it:

| DTO                  | ExtJS type  | Options                                           |
|----------------------|-------------|---------------------------------------------------|
| `InputNodeConfig`    | `textfield` | `required`                                        |
| `TextNodeConfig`     | `textarea`  | `required`                                        |
| `CheckboxNodeConfig` | `checkbox`  | `checkedValue`, `uncheckedValue`                  |
| `DropdownNodeConfig` | `combobox`  | `store`, `required`, `displayField`, `valueField` |

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

```php
<?php

namespace MyBundle\EventListener\Admin;

use OpenDxp\Bundle\AdminBundle\Dto\SiteCustomSettings\DropdownNodeConfig;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\SiteCustomSettingsEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class SiteCustomConfigListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            AdminEvents::SITE_CUSTOM_SETTINGS => 'addZoneConfig',
        ];
    }

    public function addZoneConfig(SiteCustomSettingsEvent $event): void
    {
        $event->addConfigNode(
            config: new DropdownNodeConfig(
                store: [
                    ['label' => 'Zone 1', 'value' => 'zone_1'],
                    ['label' => 'Zone 2', 'value' => 'zone_2'],
                ],
                required: true,
            ),
            scope: 'app',
            name: 'zone',
            label: 'Zone',
        );
    }
}
```

## Multiple Scopes

Multiple bundles can each add their own scoped fields independently:

```php
// Bundle A
$event->addConfigNode(new InputNodeConfig(), 'seo', 'tracking_id', 'GA Tracking ID');

// Bundle B
$event->addConfigNode(new CheckboxNodeConfig(), 'myapp', 'feature_enabled', 'Enable Feature');
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
- [Config DTOs source](https://github.com/open-dxp/admin-bundle/blob/1.x/src/Dto/SiteCustomSettings/)
- [Admin Events overview](../10_Extension_Points/01_Events.md)
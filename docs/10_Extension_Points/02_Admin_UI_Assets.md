# Loading Assets in the Admin UI

If you need to load custom JS or CSS into the admin or editmode UI, you have two options.

## Option 1: Via OpenDXP Bundle Interface (recommended)

Add [`OpenDxpBundleAdminClassicInterface`](https://github.com/open-dxp/opendxp/blob/1.x/lib/Extension/Bundle/OpenDxpBundleAdminClassicInterface.php)
to your bundle class. Use [`BundleAdminClassicTrait`](https://github.com/open-dxp/opendxp/blob/1.x/lib/Extension/Bundle/Traits/BundleAdminClassicTrait.php)
to implement the four required methods:

```php
use OpenDxp\Extension\Bundle\AbstractOpenDxpBundle;
use OpenDxp\Extension\Bundle\OpenDxpBundleAdminClassicInterface;
use OpenDxp\Extension\Bundle\Traits\BundleAdminClassicTrait;

class MyBundle extends AbstractOpenDxpBundle implements OpenDxpBundleAdminClassicInterface
{
    use BundleAdminClassicTrait;

    public function getJsPaths(): array
    {
        return ['/bundles/mybundle/js/admin.js'];
    }

    public function getCssPaths(): array
    {
        return ['/bundles/mybundle/css/admin.css'];
    }
}
```

### With Webpack Encore

Use [`EncoreHelper`](https://github.com/open-dxp/opendxp/blob/1.x/lib/Helper/EncoreHelper.php)
to resolve built file paths from `entrypoints.json`:

```php
use OpenDxp\Helper\EncoreHelper;

public function getJsPaths(): array
{
    return EncoreHelper::getBuildPathsFromEntrypoints(
        $this->getPath() . '/public/build/mybundle/entrypoints.json'
    );
}

public function getCssPaths(): array
{
    return EncoreHelper::getBuildPathsFromEntrypoints(
        $this->getPath() . '/public/build/mybundle/entrypoints.json',
        'css'
    );
}
```

## Option 2: Via Event Listener

Subscribe to events from [`BundleManagerEvents`](https://github.com/open-dxp/opendxp/blob/1.x/lib/Event/BundleManagerEvents.php):

```php
<?php

namespace App\EventListener;

use OpenDxp\Event\BundleManager\PathsEvent;
use OpenDxp\Event\BundleManagerEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class AdminAssetsListener implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            BundleManagerEvents::JS_PATHS  => 'onJsPaths',
            BundleManagerEvents::CSS_PATHS => 'onCssPaths',
        ];
    }

    public function onJsPaths(PathsEvent $event): void
    {
        $event->addPaths(['/bundles/app/js/admin.js']);
    }

    public function onCssPaths(PathsEvent $event): void
    {
        $event->addPaths(['/bundles/app/css/admin.css']);
    }
}
```

Assets registered via either method are loaded last on OpenDXP startup, in registration order.

## See Also

- [Admin UI JavaScript](03_Admin_UI_JavaScript.md) — how to use the loaded JS to extend the UI
- [BundleManagerEvents source](https://github.com/open-dxp/opendxp/blob/1.x/lib/Event/BundleManagerEvents.php)
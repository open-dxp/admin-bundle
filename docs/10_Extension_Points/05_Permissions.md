# Custom Permissions

You can add custom permission keys to OpenDXP that appear in the user/role settings panel
and can be checked both server-side and client-side.

## Step 1: Register the Permission

Add your permission key to the `users_permission_definitions` table:

```sql
INSERT INTO users_permission_definitions (key) VALUES ('my_custom_permission');
```

After this, the permission appears in the Users/Roles admin panel and can be assigned.

## Step 2: Check the Permission Server-Side

Inside an admin controller that extends `UserAwareController`:

```php
<?php

namespace App\Controller;

use OpenDxp\Controller\UserAwareController;
use OpenDxp\Controller\Traits\JsonHelperTrait;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class MyAdminController extends UserAwareController
{
    use JsonHelperTrait;

    #[Route('/admin/my-action', methods: ['GET'])]
    public function myAdminAction(Request $request): Response
    {
        $openDxpUser = $this->getOpenDxpUser();

        if ($openDxpUser?->isAllowed('my_custom_permission')) {
            // authorized
        }

        return $this->jsonResponse(['success' => true]);
    }
}
```

## Step 3: Check the Permission Client-Side

In your bundle's JavaScript (loaded via [Admin UI Assets](02_Admin_UI_Assets.md)):

```javascript
document.addEventListener(opendxp.events.opendxpReady, (e) => {
    if (opendxp.currentuser.permissions.indexOf('my_custom_permission') >= 0) {
        // user has the permission — show/enable UI element
    }
});
```

## See Also

- [Admin UI JavaScript](03_Admin_UI_JavaScript.md)
- [Users & Roles](https://github.com/open-dxp/opendxp/blob/1.x/doc/22_Administration_of_OpenDxp/07_Users_and_Roles.md)
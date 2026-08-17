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
if ($this->getOpenDxpUser()?->isAllowed('my_custom_permission')) {
    // authorized
}
```

## Step 3: Check the Permission Client-Side

In your bundle's JavaScript (loaded via [Admin UI Assets](02_Admin_UI_Assets.md)):

```javascript
const user = opendxp.globalmanager.get('user');

if (user.isAllowed('my_custom_permission')) {
    // user has the permission, show/enable UI element
}
```

## Using Symfony's Authorization Layer

For `#[IsGranted]` and `Security::isGranted()` instead of manual `isAllowed()` calls, 
see [Permission Voters](https://github.com/open-dxp/opendxp/blob/1.x/doc/19_Development_Tools_and_Details/10_Security_Authentication/10_Permission_Voters.md) in opendxp core.

## See Also

- [Admin UI JavaScript](03_Admin_UI_JavaScript.md)
- [Users & Roles](https://github.com/open-dxp/opendxp/blob/1.x/doc/22_Administration_of_OpenDxp/07_Users_and_Roles.md)

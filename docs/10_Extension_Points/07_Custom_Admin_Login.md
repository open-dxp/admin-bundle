# Custom Admin Login Entry Point

The default admin login is served at `/admin`. You can change this to a custom path
to reduce exposure of the admin panel.

## Configuration

Add the custom identifier to `config/config.yaml`:

```yaml
opendxp_admin:
    custom_admin_path_identifier: min20CharCustomToken
```

> `custom_admin_path_identifier` must be at least 20 characters long.

## Custom Route

Add a custom route entry in `config/routes.yaml`:

```yaml
my_custom_admin_entry_point:
    path: /my-custom-login-page
    controller: OpenDxp\Bundle\CoreBundle\Controller\PublicServicesController::customAdminEntryPointAction
```

When this route is called, an admin cookie is set in the browser which is then validated
for all subsequent `/admin` calls.

## Custom Route Name

If you want to use a different route name (e.g. for the "login as user" link in user administration):

```yaml
opendxp_admin:
    custom_admin_route_name: myCustomAdminRoute
```
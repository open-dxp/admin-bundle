# Perspectives

Perspectives allow creating different views in the backend UI and customizing the standard layout.
They can be combined with [Custom Views](https://github.com/open-dxp/opendxp/blob/1.x/doc/05_Objects/01_Object_Classes/05_Class_Settings/20_Custom_Views.md).

> **Security Note**
> Perspectives and Custom Views are not intended to restrict access to data — use permissions for that.

You can define per-perspective:
- Which trees are visible and where (left/right)
- Which toolbar menus and items are shown
- Which portlets are available on the dashboard
- Navigation and welcome screen elements

Access to individual perspectives can be restricted via user/role settings.

## Configuration File

The configuration lives in `var/config/perspectives/` (YAML format).
Format follows the environment configuration — see
[Configuration Environments](https://github.com/open-dxp/opendxp/blob/1.x/doc/21_Deployment/03_Configuration_Environments.md).

## Configuration Reference

| Key                                              | Type    | Description                                       |
|--------------------------------------------------|---------|---------------------------------------------------|
| `[name]["icon"]`                                 | string  | Path to perspective icon                          |
| `[name]["iconCls"]`                              | string  | CSS class for the icon                            |
| `[name]["elementTree"]`                          | array   | Tree definitions (type, position, expanded, etc.) |
| `[name]["elementTree"][i]["type"]`               | string  | `documents`, `objects`, `assets`, `customview`    |
| `[name]["elementTree"][i]["position"]`           | string  | `left` or `right`                                 |
| `[name]["elementTree"][i]["id"]`                 | integer | Custom view ID (only for type `customview`)       |
| `[name]["toolbar"]`                              | array   | Per-menu visibility and item configuration        |
| `[name]["toolbar"][menuName]["hidden"]`          | boolean | Hide the entire menu                              |
| `[name]["toolbar"][menuName]["items"][itemName]` | boolean | Show/hide individual items                        |

## Example

A catalog-admin perspective that shows only a product custom view and assets:

**Custom view** (`var/config/perspectives/perspective.yaml`):
```yaml
4e9f892c-7734-f5fa-d6f0-31e7f9787ffc:
    name: Cars
    treetype: object
    position: left
    rootfolder: '/Product Data/Cars'
    showroot: false
    sort: 3
    icon: /bundles/opendxpadmin/img/flat-white-icons/automotive.svg
    classes: CAR
```

**Perspective** (`var/config/perspectives/example.yaml`):
```yaml
demo:
    elementTree:
        -
            type: customview
            position: left
            sort: 0
            expanded: false
            hidden: false
            id: 4e9f892c-7734-f5fa-d6f0-31e7f9787ffc
        -
            type: assets
            position: right
            sort: 0
            expanded: false
            hidden: false
    iconCls: opendxp_nav_icon_perspective
    toolbar:
        file:
            hidden: true
        marketing:
            hidden: true
        extras:
            hidden: true
        settings:
            hidden: true
        search:
            hidden: false
            items:
                quickSearch: false
                documents: true
                assets: false
                objects: false
```

## Perspective Events

Subscribe to `AdminEvents::PERSPECTIVE_PRE_GET_RUNTIME` or `AdminEvents::PERSPECTIVE_POST_GET_RUNTIME`
to modify perspective data programmatically.

See [01_Events.md](01_Events.md) for subscription examples.

## See Also

- [AdminEvents](01_Events.md)
- [Custom Views](https://github.com/open-dxp/opendxp/blob/1.x/doc/05_Objects/01_Object_Classes/05_Class_Settings/20_Custom_Views.md)
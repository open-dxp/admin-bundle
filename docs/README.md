# Admin Bundle Documentation

This is the documentation root for the **OpenDXP Admin Bundle**.

The Admin Bundle provides the backend UI for OpenDXP (based on ExtJS) and defines the
extension points that other bundles use to customize the admin interface.

---

## Sections

### [00 Architecture](00_Architecture/README.md)
Bundle overview, relationship to opendxp core, and source structure.

### [10 Extension Points](10_Extension_Points/README.md)
How other bundles and applications can extend the admin UI.

| Topic                                                                | Description                                                       |
|----------------------------------------------------------------------|-------------------------------------------------------------------|
| [Events](10_Extension_Points/01_Events.md)                           | All `AdminEvents` constants — when they fire and how to subscribe |
| [Admin UI Assets](10_Extension_Points/02_Admin_UI_Assets.md)         | Loading custom JS and CSS into the admin UI                       |
| [Admin UI JavaScript](10_Extension_Points/03_Admin_UI_JavaScript.md) | ExtJS event system, adding menus, key bindings                    |
| [Perspectives](10_Extension_Points/04_Perspectives.md)               | Configuring backend UI perspectives                               |
| [Permissions](10_Extension_Points/05_Permissions.md)                 | Adding custom permissions                                         |
| [Deeplinks](10_Extension_Points/06_Deeplinks.md)                     | Deeplinks into the admin interface                                |
| [Custom Admin Login](10_Extension_Points/07_Custom_Admin_Login.md)   | Custom admin login entry point                                    |

### [20 Documents](20_Documents/README.md)
Admin UI features specific to document and site management.

| Topic                                                           | Description                                          |
|-----------------------------------------------------------------|------------------------------------------------------|
| [Site Custom Settings](20_Documents/01_Site_Custom_Settings.md) | Adding custom fields to the site configuration panel |

### [90 Testing](90_Testing/README.md)
How to write and run tests for this bundle.

---

## Quick Reference

- **Event constants:** `src/Event/AdminEvents.php`
- **opendxp core docs:** MVC, models, routing, deployment → see opendxp `doc/` (location depends on setup, see `CLAUDE.md`)
# Admin UI JavaScript

The OpenDXP backend UI is built with [ExtJS](https://www.sencha.com/products/extjs/).
Custom JS loaded via [Admin UI Assets](02_Admin_UI_Assets.md) runs in the same context
and can hook into the admin lifecycle using the event system defined in
[`public/js/opendxp/events.js`](https://github.com/open-dxp/admin-bundle/blob/1.x/public/js/opendxp/events.js).

## Listening to the Ready Event

The entry point for any UI extension is `opendxp.events.opendxpReady`:

```javascript
document.addEventListener(opendxp.events.opendxpReady, (e) => {
    console.log('OpenDXP is ready', e.detail);
});
```

## Validating Object Data Before Save

Use `preventDefault()` and `stopPropagation()` to cancel a save:

```javascript
document.addEventListener(opendxp.events.preSaveObject, (e) => {
    const confirmed = confirm(`Save ${e.detail.object.data.general.className}?`);
    if (!confirmed) {
        e.preventDefault();
        e.stopPropagation();
        opendxp.helpers.showNotification(t('Info'), t('saving_failed'), 'info');
    }
});
```

## Adding a Main Navigation Item

Hook into `opendxp.events.preMenuBuild` to add top-level navigation:

```javascript
opendxp.plugin.mybundle = Class.create({
    initialize: function () {
        document.addEventListener(opendxp.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;

        menu.mybundle = {
            label: t('myBundleLabel'),
            iconCls: 'opendxp_main_nav_icon_myIcon',
            priority: 42,
            items: [],
            shadow: false,
            handler: this.openMyBundle,
            noSubmenus: true,
            cls: 'opendxp_navigation_flyout',
        };
    },

    openMyBundle: function (e) {
        try {
            opendxp.globalmanager.get('plugin_opendxp_mybundle').activate();
        } catch (e) {
            opendxp.globalmanager.add('plugin_opendxp_mybundle', new opendxp.plugin.mybundle());
        }
    }
});

var myBundle = new opendxp.plugin.mybundle();
```

## Adding a Submenu to an Existing Menu

Push into an existing menu's `items` array:

```javascript
opendxp.registerNS('opendxp.bundle.glossary.startup');

opendxp.bundle.glossary.startup = Class.create({
    initialize: function () {
        document.addEventListener(opendxp.events.preMenuBuild, this.preMenuBuild.bind(this));
    },

    preMenuBuild: function (e) {
        let menu = e.detail.menu;
        const user = opendxp.globalmanager.get('user');
        const perspectiveCfg = opendxp.globalmanager.get('perspective');

        if (menu.extras && user.isAllowed('glossary') && perspectiveCfg.inToolbar('extras.glossary')) {
            menu.extras.items.push({
                text: t('glossary'),
                iconCls: 'opendxp_nav_icon_glossary',
                priority: 5,
                itemId: 'opendxp_menu_extras_glossary',
                handler: this.editGlossary,
            });
        }
    },

    editGlossary: function () {
        try {
            opendxp.globalmanager.get('bundle_glossary').activate();
        } catch (e) {
            opendxp.globalmanager.add('bundle_glossary', new opendxp.bundle.glossary.settings());
        }
    }
});

const opendxpBundleGlossary = new opendxp.bundle.glossary.startup();
```

## Adding Custom Key Bindings

Define the key binding in `config.yaml`:

```yaml
opendxp_admin:
    user:
        default_key_bindings:
            glossary:
                key: 'G'
                action: glossary   # must match the function name added to keyBindingMapping
                alt: true
                shift: true
```

Then register the binding in JS using `opendxp.events.preRegisterKeyBindings`:

```javascript
opendxp.bundle.glossary.startup = Class.create({
    initialize: function () {
        document.addEventListener(opendxp.events.preRegisterKeyBindings, this.registerKeyBinding.bind(this));
    },

    registerKeyBinding: function (e) {
        const user = opendxp.globalmanager.get('user');
        if (user.isAllowed('glossary')) {
            opendxp.helpers.keyBindingMapping.glossary = function () {
                opendxpBundleGlossary.editGlossary();
            };
        }
    }
});
```

## I18n in JavaScript

Translations registered server-side are available via `t()`:

```javascript
t('my_translation_key')
```

See [opendxp/doc — i18n for bundles](https://github.com/open-dxp/opendxp/blob/1.x/doc/06_Multi_Language_i18n/07_Admin_Translations.md)
for how to register translation keys server-side.

## See Also

- [Admin UI Assets](02_Admin_UI_Assets.md) — how to load your JS files
- [events.js source](https://github.com/open-dxp/admin-bundle/blob/1.x/public/js/opendxp/events.js)
/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

/**
 * @private
 */

if (typeof console === 'undefined') {
    console = {
        log: function (v) {},
        dir: function (v) {},
        debug: function (v) {},
        info: function (v) {},
        warn: function (v) {},
        error: function (v) {},
        trace: function (v) {},
        group: function (v) {},
        groupEnd: function (v) {},
        time: function (v) {},
        timeEnd: function (v) {},
        profile: function (v) {},
        profileEnd: function (v) {}
    };
}

let xhrActive = 0;

Ext.Loader.setConfig({
    enabled: true
});
Ext.enableAriaButtons = false;

Ext.Loader.setPath('Ext.ux', '/bundles/opendxpadmin/extjs/ext-ux/src/classic/src');

Ext.require([
    'Ext.ux.colorpick.Field',
    'Ext.ux.colorpick.SliderAlpha',
    'Ext.ux.form.MultiSelect',
    'Ext.ux.TabCloseMenu',
    'Ext.ux.TabReorderer',
    'Ext.ux.grid.SubTable',
    'Ext.window.Toast',
    'Ext.slider.Single',
    'Ext.form.field.Tag',
    'Ext.ux.TabMiddleButtonClose'
]);

Ext.ariaWarn = Ext.emptyFn;

Ext.onReady(function () {

    opendxp.helpers.colorpicker.initOverrides();

    const StateFullProvider = Ext.extend(Ext.state.Provider, {
        namespace: 'default',

        constructor: function (config) {
            StateFullProvider.superclass.constructor.call(this);
            Ext.apply(this, config);

            const data = localStorage.getItem(this.namespace);
            if (!data) {
                this.state = {};
            } else {
                const parsed = JSON.parse(data);
                if (parsed.state && parsed.user === opendxp.currentuser.id) {
                    this.state = parsed.state;
                } else {
                    this.state = {};
                }
            }
        },

        get: function (name, defaultValue) {
            try {
                if (typeof this.state[name] === 'undefined') {
                    return defaultValue;
                } else {
                    return this.decodeValue(this.state[name]);
                }
            } catch (e) {
                this.clear(name);
                return defaultValue;
            }
        },

        set: function (name, value) {
            try {
                if (typeof value === 'undefined' || value === null) {
                    this.clear(name);
                    return;
                }
                this.state[name] = this.encodeValue(value);

                const json = JSON.stringify({
                    state: this.state,
                    user: opendxp.currentuser.id
                });

                localStorage.setItem(this.namespace, json);
            } catch (e) {
                this.clear(name);
            }

            this.fireEvent('statechange', this, name, value);
        }
    });

    const provider = new StateFullProvider({
        namespace: 'opendxp_ui_states_6'
    });

    Ext.state.Manager.setProvider(provider);

    // confirmation to close opendxp
    window.addEventListener('beforeunload', function () {
        // set this here as a global so that eg. the editmode can access this (edit::iframeOnbeforeunload()),
        // to prevent multiple warning messages to be shown
        opendxp.globalmanager.add('opendxp_reload_in_progress', true);

        if (!opendxp.settings.devmode) {
            const tabPanel = Ext.getCmp('opendxp_panel_tabs');
            const currentUser = opendxp.globalmanager.get('user');
            if (opendxp.settings.showCloseConfirmation && tabPanel.items.getCount() > 0 && currentUser['closeWarning']) {
                return t('do_you_really_want_to_close_opendxp');
            }
        }

        const openTabs = opendxp.helpers.getOpenTab();
        if (openTabs.length > 0) {
            const elementsToBeUnlocked = [];
            for (let i = 0; i < openTabs.length; i++) {
                const elementIdentifier = openTabs[i].split('_');
                if (['object', 'asset', 'document'].indexOf(elementIdentifier[0]) > -1) {
                    elementsToBeUnlocked.push({id: elementIdentifier[1], type: elementIdentifier[0]});
                }
            }

            if (elementsToBeUnlocked.length > 0) {
                navigator.sendBeacon(
                    Routing.generate('opendxp_admin_element_unlockelements') + '?csrfToken=' + opendxp.settings['csrfToken'],
                    JSON.stringify({elements: elementsToBeUnlocked})
                );
            }
        }
    });

    Ext.QuickTips.init();
    Ext.MessageBox.minPromptWidth = 500;

    Ext.Ajax.setDisableCaching(true);
    Ext.Ajax.setTimeout(900000);
    Ext.Ajax.setMethod('GET');
    Ext.Ajax.setDefaultHeaders({
        'X-opendxp-csrf-token': opendxp.settings['csrfToken'],
        'X-opendxp-extjs-version-major': Ext.getVersion().getMajor(),
        'X-opendxp-extjs-version-minor': Ext.getVersion().getMinor()
    });

    Ext.Ajax.on('requestexception', function (conn, response, options) {
        if (response.aborted) {
            console.log('xhr request to ' + options.url + ' aborted');
        } else {
            console.error('xhr request to ' + options.url + ' failed');
        }

        let jsonData = response.responseJson;
        if (!jsonData) {
            try {
                jsonData = JSON.parse(response.responseText);
            } catch (e) {
                // ignore parse errors
            }
        }

        const date = new Date();
        let errorMessage = 'Timestamp: ' + date.toString() + '\n';
        let errorDetailMessage = '\n' + response.responseText;

        try {
            errorMessage += 'Status: ' + response.status + ' | ' + response.statusText + '\n';
            errorMessage += 'URL: ' + options.url + '\n';

            if (options['params'] && options['params'].length > 0) {
                errorMessage += 'Params:\n';
                Ext.iterate(options.params, function (key, value) {
                    errorMessage += ('-> ' + key + ': ' + value.substr(0, 500) + '\n');
                });
            }

            if (options['method']) {
                errorMessage += 'Method: ' + options.method + '\n';
            }

            if (jsonData) {
                if (jsonData['message']) {
                    errorDetailMessage = jsonData['message'];
                }

                if (jsonData['traceString']) {
                    errorDetailMessage += '\nTrace: \n' + jsonData['traceString'];
                }
            }

            errorMessage += 'Message: ' + errorDetailMessage;
        } catch (e) {
            errorMessage += '\n\n';
            errorMessage += response.responseText;
        }

        if (!response.aborted && options['ignoreErrors'] !== true) {
            if (response.status === 503) {
                if (!opendxp.maintenanceWindow) {
                    opendxp.maintenanceWindow = new Ext.Window({
                        closable: false,
                        title: t('please_wait'),
                        bodyStyle: 'padding: 20px;',
                        html: t('the_system_is_in_maintenance_mode_please_wait'),
                        closeAction: 'close',
                        modal: true,
                        listeners: {
                            show: function () {
                                window.setInterval(function () {
                                    Ext.Ajax.request({
                                        url: Routing.generate('opendxp_admin_misc_ping'),
                                        success: function () {
                                            if (opendxp.maintenanceWindow) {
                                                opendxp.maintenanceWindow.close();
                                                window.setTimeout(function () {
                                                    delete opendxp.maintenanceWindow;
                                                }, 2000);
                                                opendxp.viewport.updateLayout();
                                            }
                                        }
                                    });
                                }, 30000);
                            }
                        }
                    });
                    opendxp.viewport.add(opendxp.maintenanceWindow);
                    opendxp.maintenanceWindow.show();
                }
            } else if (jsonData && jsonData['type'] === 'ValidationException') {
                opendxp.helpers.showNotification(t('validation_failed'), jsonData['message'], 'error', errorMessage);
            } else if (jsonData && jsonData['type'] === 'ConfigWriteException') {
                opendxp.helpers.showNotification(t('error'), t('config_not_writeable'), 'error', errorMessage);
            } else if (response.status === 403) {
                opendxp.helpers.showNotification(t('access_denied'), t('access_denied_description'), 'error');
            } else if (response.status === 500) {
                opendxp.helpers.showNotification(t('error'), t('error_general'), 'error', errorMessage);
            } else {
                let message = t('error');
                if (jsonData && jsonData['message']) {
                    message = jsonData['message'];
                }
                opendxp.helpers.showNotification(t('error'), message, 'error', errorMessage);
            }
        }

        xhrActive--;
        if (xhrActive < 1) {
            Ext.get('opendxp_loading').hide();
        }
    });

    Ext.Ajax.on('beforerequest', function () {
        if (xhrActive < 1) {
            Ext.get('opendxp_loading').show();
        }
        xhrActive++;
    });

    Ext.Ajax.on('requestcomplete', function () {
        xhrActive--;
        if (xhrActive < 1) {
            Ext.get('opendxp_loading').hide();
        }
    });

    const user = new opendxp.user(opendxp.currentuser);
    opendxp.globalmanager.add('user', user);

    // set the default date time format according to user locale settings
    const localeDateTime = opendxp.localeDateTime;
    opendxp.globalmanager.add('localeDateTime', localeDateTime);
    localeDateTime.setDefaultDateTime(user.datetimeLocale);

    // removes empty properties from payload before executing request
    Ext.define('opendxp.data.proxy.ajax', {
        extend: 'Ext.data.proxy.Ajax',
        buildRequest: function (operation) {
            const request = this.callParent(arguments);
            const params = request.getParams();

            Ext.Object.each(params, function (key, value) {
                if (value === null || value === undefined || value === '') {
                    delete params[key];
                }
            });

            request.setParams(params);
            return request;
        }
    });

    // document types
    Ext.define('opendxp.model.doctypes', {
        extend: 'Ext.data.Model',
        fields: [
            'id',
            {name: 'name', allowBlank: false},
            {
                name: 'translatedName',
                convert: function (v, rec) {
                    return t(rec.data.name);
                },
                depends: ['name']
            },
            'group',
            {
                name: 'translatedGroup',
                convert: function (v, rec) {
                    if (rec.data.group) {
                        return t(rec.data.group);
                    }
                    return '';
                },
                depends: ['group']
            },
            'controller',
            'template',
            {name: 'type', allowBlank: false},
            'priority',
            'creationDate',
            'modificationDate'
        ],
        autoSync: false,
        proxy: {
            type: 'ajax',
            reader: {
                type: 'json',
                totalProperty: 'total',
                successProperty: 'success',
                rootProperty: 'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true',
                // DocumentController expects single items, ExtJs may batch them without this
                batchActions: false
            },
            api: {
                create: Routing.generate('opendxp_admin_document_document_doctypesget', {xaction: 'create'}),
                read: Routing.generate('opendxp_admin_document_document_doctypesget', {xaction: 'read'}),
                update: Routing.generate('opendxp_admin_document_document_doctypesget', {xaction: 'update'}),
                destroy: Routing.generate('opendxp_admin_document_document_doctypesget', {xaction: 'destroy'})
            }
        }
    });

    if (user.isAllowed('documents') || user.isAllowed('users')) {
        const doctypesStore = new Ext.data.Store({
            id: 'doctypes',
            model: 'opendxp.model.doctypes',
            remoteSort: false,
            autoSync: true,
            autoLoad: true
        });

        opendxp.globalmanager.add('document_types_store', doctypesStore);
        opendxp.globalmanager.add('document_valid_types', opendxp.settings.document_valid_types);
    }

    // search element types
    opendxp.globalmanager.add('document_search_types', opendxp.settings.document_search_types);
    opendxp.globalmanager.add('asset_search_types', opendxp.settings.asset_search_types);
    opendxp.globalmanager.add('object_search_types', ['object', 'folder', 'variant']);

    // translation admin keys
    opendxp.globalmanager.add('translations_admin_missing', []);
    opendxp.globalmanager.add('translations_admin_added', []);
    opendxp.globalmanager.add('translations_admin_translated_values', []);

    const objectClassFields = [
        {name: 'id'},
        {name: 'text', allowBlank: false},
        {
            name: 'translatedText',
            convert: function (v, rec) {
                return t(rec.data.text);
            },
            depends: ['text']
        },
        {name: 'icon'},
        {name: 'group'},
        {
            name: 'translatedGroup',
            convert: function (v, rec) {
                if (rec.data.group) {
                    return t(rec.data.group);
                }
                return '';
            },
            depends: ['group']
        },
        {name: 'propertyVisibility'}
    ];

    Ext.define('opendxp.model.objecttypes', {
        extend: 'Ext.data.Model',
        fields: objectClassFields,
        proxy: {
            type: 'ajax',
            url: Routing.generate('opendxp_admin_dataobject_class_gettree'),
            reader: {
                type: 'json'
            }
        }
    });

    const objectTypesStore = new Ext.data.Store({
        model: 'opendxp.model.objecttypes',
        id: 'object_types'
    });
    objectTypesStore.load();
    opendxp.globalmanager.add('object_types_store', objectTypesStore);

    // a store for filtered classes that can be created by the user
    Ext.define('opendxp.model.objecttypes.create', {
        extend: 'Ext.data.Model',
        fields: objectClassFields,
        proxy: {
            type: 'ajax',
            url: Routing.generate('opendxp_admin_dataobject_class_gettree', {createAllowed: true}),
            reader: {
                type: 'json'
            }
        }
    });

    const objectTypesCreateStore = new Ext.data.Store({
        model: 'opendxp.model.objecttypes.create',
        id: 'object_types'
    });
    objectTypesCreateStore.load();
    opendxp.globalmanager.add('object_types_store_create', objectTypesCreateStore);

    opendxp.globalmanager.add('perspective', new opendxp.perspective(opendxp.settings.perspective));

    // opendxp languages
    Ext.define('opendxp.model.languages', {
        extend: 'Ext.data.Model',
        fields: [
            {name: 'language'},
            {name: 'display'}
        ],
        proxy: {
            type: 'ajax',
            url: Routing.generate('opendxp_admin_settings_getavailableadminlanguages'),
            reader: {
                type: 'json'
            }
        }
    });

    const languageStore = new Ext.data.Store({
        model: 'opendxp.model.languages'
    });
    languageStore.load();
    opendxp.globalmanager.add('opendxplanguages', languageStore);

    Ext.define('opendxp.model.sites', {
        extend: 'Ext.data.Model',
        fields: ['id', 'domains', 'rootId', 'rootPath', 'domain'],
        proxy: {
            type: 'ajax',
            url: Routing.generate('opendxp_admin_settings_getavailablesites'),
            reader: {
                type: 'json'
            }
        }
    });

    const sitesStore = new Ext.data.Store({
        model: 'opendxp.model.sites'
    });
    sitesStore.load();
    opendxp.globalmanager.add('sites', sitesStore);

    // check for updates
    window.setTimeout(function () {
        const domains = [];
        opendxp.globalmanager.get('sites').each(function (rec) {
            if (rec.get('rootId') !== 1) {
                if (!empty(rec.get('domain'))) {
                    domains.push(rec.get('domain'));
                }
                if (!empty(rec.get('domains'))) {
                    domains.push(rec.get('domains'));
                }
            }
        });

        const data = {
            instance_id: opendxp.settings.instanceId,
            revision: opendxp.settings.build,
            version: opendxp.settings.version,
            debug: opendxp.settings.debug,
            dev_mode: opendxp.settings.devmode,
            environment: opendxp.settings.environment,
            language: opendxp.settings.language,
            main_domain: opendxp.settings.main_domain,
            domains: domains.join(','),
            timezone: opendxp.settings.timezone,
            website_languages: opendxp.settings.websiteLanguages.join(',')
        };

        fetch('https://metrics.opendxp.io/check', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify(data)
        })
            .then(response => {
                if (response.ok) {
                    return response.json();
                }
                return null;
            })
            .then(data => {
                if (!data) return;

                if (data.latestVersion !== null && opendxp.currentuser.admin) {
                    const toolbar = opendxp.globalmanager.get('layout_toolbar');

                    opendxp.notification.helper.incrementCount();

                    toolbar.notificationMenu.add({
                        text: t('update_available'),
                        iconCls: 'opendxp_icon_reload',
                        handler: function () {
                            const tpl = new Ext.XTemplate(
                                '<div class="opendxp_about_window">',
                                    '<h2 style="text-decoration: underline">New Version Available!</h2>',
                                    '<br><b>Your Version: {currentVersion}</b>',
                                    '<br><b style="color: darkgreen;">New Version: {latestVersion}</b>',
                                '</div>'
                            );

                            const win = new Ext.Window({
                                title: 'New Version Available!',
                                width: 500,
                                height: 220,
                                bodyStyle: 'padding: 10px;',
                                modal: true,
                                html: tpl.apply({
                                    currentVersion: opendxp.settings.version,
                                    latestVersion: data.latestVersion
                                })
                            });

                            win.show();
                        }
                    });
                }

                if (data.pushStatistics && opendxp.currentuser.admin) {
                    fetch(Routing.generate('opendxp_admin_index_statistics'), {
                        headers: {
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => {
                            if (response.ok) {
                                return response.json();
                            }
                            return null;
                        })
                        .catch(error => {
                            console.error('Metrics fetch error', error);
                        });
                }
            })
            .catch(error => {
                console.error('Metrics fetch error', error);
            });
    }, 5000);

    Ext.get('opendxp_logout')?.on('click', function () {
        document.getElementById('opendxp_logout_form').submit();
    });

    // remove loading
    Ext.get('opendxp_loading').addCls('loaded');
    Ext.get('opendxp_loading').hide();
    Ext.get('opendxp_signet').show();

    // init general layout
    try {
        opendxp.viewport = Ext.create('Ext.container.Viewport', {
            id: 'opendxp_viewport',
            layout: 'border',
            items: [
                {
                    xtype: 'panel',
                    id: 'opendxp_body',
                    region: 'center',
                    cls: 'opendxp_body',
                    layout: 'border',
                    border: false,
                    items: [
                        Ext.create('Ext.panel.Panel', {
                            region: 'west',
                            id: 'opendxp_panel_tree_left',
                            cls: 'opendxp_main_accordion',
                            split: {
                                cls: 'opendxp_main_splitter'
                            },
                            width: 300,
                            minSize: 175,
                            collapsible: true,
                            collapseMode: 'header',
                            defaults: {
                                margin: '0'
                            },
                            layout: {
                                type: 'accordion',
                                hideCollapseTool: true,
                                animate: false
                            },
                            header: false,
                            hidden: true,
                            forceLayout: true,
                            hideMode: 'offsets',
                            items: []
                        }),
                        Ext.create('Ext.tab.Panel', {
                            region: 'center',
                            deferredRender: false,
                            id: 'opendxp_panel_tabs',
                            enableTabScroll: true,
                            hideMode: 'offsets',
                            cls: 'tab_panel',
                            plugins: [
                                Ext.create('Ext.ux.TabCloseMenu', {
                                    pluginId: 'tabclosemenu',
                                    showCloseAll: false,
                                    closeTabText: t('close_tab'),
                                    showCloseOthers: false,
                                    extraItemsTail: opendxp.helpers.getMainTabMenuItems()
                                }),
                                Ext.create('Ext.ux.TabReorderer', {}),
                                Ext.create('Ext.ux.TabMiddleButtonClose', {})
                            ]
                        }),
                        {
                            region: 'east',
                            id: 'opendxp_panel_tree_right',
                            cls: 'opendxp_main_accordion',
                            split: {
                                cls: 'opendxp_main_splitter'
                            },
                            width: 300,
                            minSize: 175,
                            collapsible: true,
                            collapseMode: 'header',
                            defaults: {
                                margin: '0'
                            },
                            layout: {
                                type: 'accordion',
                                hideCollapseTool: true,
                                animate: false
                            },
                            header: false,
                            hidden: true,
                            forceLayout: true,
                            hideMode: 'offsets',
                            items: []
                        }
                    ]
                }
            ],
            listeners: {
                afterrender: function (el) {
                    Ext.get('opendxp_navigation').show();
                    Ext.get('opendxp_avatar')?.show();
                    Ext.get('opendxp_logout')?.show();

                    opendxp.helpers.initMenuTooltips();

                    const loadMask = new Ext.LoadMask({
                        target: Ext.getCmp('opendxp_viewport'),
                        msg: t('please_wait')
                    });
                    loadMask.enable();
                    opendxp.globalmanager.add('loadingmask', loadMask);

                    // prevent dropping files/folders outside the asset tree
                    const preventDrop = function (e) {
                        e.preventDefault();
                        e.dataTransfer.dropEffect = 'none';
                    };

                    el.getEl().dom.addEventListener('dragenter', preventDrop, true);
                    el.getEl().dom.addEventListener('dragover', preventDrop, true);

                    // open "My Profile" when clicking on avatar
                    Ext.get('opendxp_avatar')?.on('click', function () {
                        opendxp.helpers.openProfile();
                    });
                }
            }
        });

        // add sidebar panels
        if (user.memorizeTabs || opendxp.helpers.forceOpenMemorizedTabsOnce()) {
            opendxp.layout.treepanelmanager.addOnReadyCallback(function () {
                window.setTimeout(function () {
                    opendxp.helpers.openMemorizedTabs();
                }, 500);
            });
        }

        const perspective = opendxp.globalmanager.get('perspective');
        const elementTree = perspective.getElementTree();
        const locateConfigs = {
            document: [],
            asset: [],
            object: []
        };

        const customPerspectiveElementTrees = [];
        for (let i = 0; i < elementTree.length; i++) {
            const treeConfig = elementTree[i];
            const type = treeConfig['type'];
            const side = treeConfig['position'] ? treeConfig['position'] : 'left';
            let treepanel = null;
            let tree = null;
            let treetype = null;
            let locateKey = 'layout_' + type + '_locateintree_tree';

            switch (type) {
                case 'documents':
                    if (user.isAllowed('documents') && !treeConfig.hidden) {
                        treetype = 'document';
                        tree = new opendxp.document.tree(null, treeConfig);
                        opendxp.globalmanager.add('layout_document_tree', tree);
                        treepanel = Ext.getCmp('opendxp_panel_tree_' + side);
                        treepanel.setHidden(false);
                    }
                    break;
                case 'assets':
                    if (user.isAllowed('assets') && !treeConfig.hidden) {
                        treetype = 'asset';
                        tree = new opendxp.asset.tree(null, treeConfig);
                        opendxp.globalmanager.add('layout_asset_tree', tree);
                        treepanel = Ext.getCmp('opendxp_panel_tree_' + side);
                        treepanel.setHidden(false);
                    }
                    break;
                case 'objects':
                    if (user.isAllowed('objects')) {
                        treetype = 'object';
                        if (!treeConfig.hidden) {
                            treepanel = Ext.getCmp('opendxp_panel_tree_' + side);
                            tree = new opendxp.object.tree(null, treeConfig);
                            opendxp.globalmanager.add('layout_object_tree', tree);
                            treepanel.setHidden(false);
                        }
                    }
                    break;
                case 'customview':
                    if (!treeConfig.hidden) {
                        treetype = treeConfig.treetype ? treeConfig.treetype : 'object';
                        locateKey = 'layout_' + treetype + 's_locateintree_tree';

                        if (user.isAllowed(treetype + 's')) {
                            treepanel = Ext.getCmp('opendxp_panel_tree_' + side);

                            // Do not add opendxp_icon_material class to non-material icons
                            let iconTypeClass = '';
                            if (treeConfig.icon && treeConfig.icon.match('flat-white')) {
                                iconTypeClass += 'opendxp_icon_material';
                            }

                            const treeCls = window.opendxp[treetype].customviews.tree;

                            tree = new treeCls({
                                isCustomView: true,
                                customViewId: treeConfig.id,
                                allowedClasses: treeConfig.allowedClasses,
                                rootId: treeConfig.rootId,
                                rootVisible: treeConfig.showroot,
                                treeId: 'opendxp_panel_tree_' + treetype + '_' + treeConfig.id,
                                treeIconCls: 'opendxp_' + treetype + '_customview_icon_' + treeConfig.id + ' ' + iconTypeClass,
                                treeTitle: t(treeConfig.name),
                                parentPanel: treepanel,
                                loaderBaseParams: {}
                            }, treeConfig);
                            opendxp.globalmanager.add('layout_' + treetype + '_tree_' + treeConfig.id, tree);

                            treepanel.setHidden(false);
                        }
                    }
                    break;
                default:
                    if (!treeConfig.hidden) {
                        customPerspectiveElementTrees.push(treeConfig);
                    }
            }

            if (tree && treetype) {
                locateConfigs[treetype].push({
                    key: locateKey,
                    side: side,
                    tree: tree
                });
            }
        }
        opendxp.globalmanager.add('tree_locate_configs', locateConfigs);

        const postBuildPerspectiveElementTree = new CustomEvent(opendxp.events.postBuildPerspectiveElementTree, {
            detail: {
                customPerspectiveElementTrees: customPerspectiveElementTrees
            }
        });
        document.dispatchEvent(postBuildPerspectiveElementTree);
    } catch (e) {
        console.log(e);
    }

    // deprecated: implicitly declare it with 2.0
    layoutToolbar = new opendxp.layout.toolbar();
    opendxp.globalmanager.add('layout_toolbar', layoutToolbar);

    // check for activated maintenance-mode with this session-id
    if (opendxp.settings.maintenance_mode) {
        opendxp.helpers.showMaintenanceDisableButton();
    }

    if (user.isAllowed('dashboards') && opendxp.globalmanager.get('user').welcomescreen) {
        window.setTimeout(function () {
            const layoutPortal = new opendxp.layout.portal();
            opendxp.globalmanager.add('layout_portal_welcome', layoutPortal);
        }, 1000);
    }

    opendxp.viewport.updateLayout();

    // NOTE: the event opendxpReady is fired in opendxp.layout.treepanelmanager
    opendxp.layout.treepanelmanager.startup();

    opendxp.helpers.registerKeyBindings(document);

    if (opendxp.currentuser.isPasswordReset) {
        opendxp.helpers.openProfile();
    }
});

opendxp['intervals'] = {};

// add missing translation keys
opendxp['intervals']['translations_admin_missing'] = window.setInterval(function () {
    const missingTranslations = opendxp.globalmanager.get('translations_admin_missing');
    const addedTranslations = opendxp.globalmanager.get('translations_admin_added');
    if (missingTranslations.length > 0) {
        const thresholdIndex = 500;
        const arraySurpassing = missingTranslations.length > thresholdIndex;
        const sentTranslations = arraySurpassing ? missingTranslations.slice(0, thresholdIndex) : missingTranslations;
        const params = Ext.encode(sentTranslations);
        for (let i = 0; i < sentTranslations.length; i++) {
            addedTranslations.push(sentTranslations[i]);
        }
        const restMissingTranslations = missingTranslations.slice(thresholdIndex);
        opendxp.globalmanager.add('translations_admin_missing', restMissingTranslations);
        Ext.Ajax.request({
            method: 'POST',
            url: Routing.generate('opendxp_admin_translation_addadmintranslationkeys'),
            params: {keys: params}
        });
    }
}, 30000);

// session renew
opendxp['intervals']['ping'] = window.setInterval(function () {
    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_misc_ping'),
        success: function (response) {
            let data;

            try {
                data = Ext.decode(response.responseText);

                if (data.success !== true) {
                    throw 'session seems to be expired';
                }
            } catch (e) {
                data = false;
                opendxp.settings.showCloseConfirmation = false;
                window.location.href = Routing.generate('opendxp_admin_login', {session_expired: true});
            }

            if (opendxp.maintenanceWindow) {
                opendxp.maintenanceWindow.close();
                window.setTimeout(function () {
                    delete opendxp.maintenanceWindow;
                }, 2000);
                opendxp.viewport.updateLayout();
            }

            if (data) {
                // here comes the check for maintenance mode, ...
            }
        },
        failure: function (response) {
            if (response.status !== 503) {
                opendxp.settings.showCloseConfirmation = false;
                window.location.href = Routing.generate('opendxp_admin_login', {session_expired: true, server_error: true});
            }
        }
    });
}, (opendxp.settings.session_gc_maxlifetime - 60) * 1000);

if (opendxp.settings.checknewnotification_enabled) {
    opendxp['intervals']['checkNewNotification'] = window.setInterval(function () {
        opendxp.notification.helper.updateFromServer();
    }, opendxp.settings.checknewnotification_interval);
}

// refreshes the layout
opendxp.registerNS('opendxp.layout.refresh');
opendxp.layout.refresh = function () {
    try {
        opendxp.viewport.updateLayout();
    } catch (e) {
        // ignore layout refresh errors
    }
};

// garbage collector
opendxp.helpers.unload = function () {

};

if (!opendxp.wysiwyg) {
    opendxp.wysiwyg = {};
    opendxp.wysiwyg.editors = [];
}
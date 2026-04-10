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

opendxp.registerNS('opendxp.document.link');
/**
 * @private
 */
opendxp.document.link = Class.create(opendxp.document.document, {

    initialize: function (id, options) {

        this.options = options;
        this.id = intval(id);
        this.setType('link');
        this.addLoadingPanel();

        const preOpenDocumentLink = new CustomEvent(opendxp.events.preOpenDocument, {
            detail: {
                document: this,
                type: 'link'
            },
            cancelable: true
        });

        const isAllowed = document.dispatchEvent(preOpenDocumentLink);
        if (!isAllowed) {
            this.removeLoadingPanel();
            return;
        }

        this.getData();
    },

    init: function () {

        var user = opendxp.globalmanager.get('user');

        if (this.isAllowed('properties')) {
            this.properties = new opendxp.document.properties(this, 'document', true);
        }

        if (this.isAllowed('settings')) {
            this.scheduler = new opendxp.element.scheduler(this, 'document', {
                supportsVersions: false
            });
        }

        if (user.isAllowed('notes_events')) {
            this.notes = new opendxp.element.notes(this, 'document');
        }

        if (opendxp.settings.dependency) {
            this.dependencies = new opendxp.element.dependencies(this, 'document');
        }

        this.tagAssignment = new opendxp.element.tag.assignment(this, 'document');
        this.workflows = new opendxp.element.workflows(this, 'document');
    },

    getSaveData: function (only) {
        var parameters = {};
        parameters.id = this.id;

        // get only scheduled tasks
        if (only === 'scheduler') {
            try {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
                return parameters;
            }
            catch (e) {
                console.log('scheduler not available');
                return;
            }
        }

        var values = this.panel.getForm().getFieldValues();
        values.published = this.data.published;
        parameters.data = Ext.encode(values);

        if (this.isAllowed('properties')) {
            // properties
            try {
                parameters.properties = Ext.encode(this.properties.getValues());
            }
            catch (e) {
                //console.log(e);
            }
        }

        if (this.isAllowed('settings')) {
            // scheduler
            try {
                parameters.scheduler = Ext.encode(this.scheduler.getValues());
            }
            catch (e5) {
                //console.log(e5);
            }
        }

        return parameters;
    },

    addTab: function () {

        var tabTitle = this.data.key,
            tabId = 'document_' + this.id;

        this.tabPanel = Ext.getCmp('opendxp_panel_tabs');

        const tabbarContainer = new Ext.Container({
            flex: 2
        });

        const tabPanel = this.getTabPanel();
        const toolbar = this.getLayoutToolbar();

        if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
            this.tab = new Ext.Panel({
                id: tabId,
                cls: 'opendxp_panel_toolbar_horizontal_border_layout',
                title: htmlspecialchars(tabTitle),
                closable:true,
                hideMode: 'offsets',
                layout: 'border',
                items: [
                    {
                        xtype: 'panel',
                        width: '100%',
                        region: 'north',
                        layout: 'hbox',
                        items: [
                            toolbar,
                            tabbarContainer,
                        ]
                    },

                    tabPanel
                ],
                iconCls: this.getIconClass(),
                document: this
            });

            this.toolbarSubmenu.menu.addCls('opendxp_headbar_submenu_menu');
            
            opendxp.helpers.headbar.prepareTabPanel(tabPanel, tabbarContainer, this.tab);
        } else {
            this.tab = new Ext.Panel({
                id: tabId,
                title: htmlspecialchars(tabTitle),
                closable: true,
                layout: 'border',
                items: [
                    toolbar,
                    tabPanel
                ],
                iconCls: this.getIconClass(),
                document: this
            });
        }

        this.tab.on('beforedestroy', function () {
            Ext.Ajax.request({
                url: Routing.generate('opendxp_admin_element_unlockelement'),
                method: 'PUT',
                params: {
                    id: this.data.id,
                    type: 'document'
                }
            });
        }.bind(this));

        // remove this instance when the panel is closed
        this.tab.on('destroy', function () {
            opendxp.globalmanager.remove('document_' + this.id);
            opendxp.helpers.forgetOpenTab('document_' + this.id + '_link');
        }.bind(this));

        this.tab.on('activate', function () {
            this.tab.updateLayout();
            opendxp.layout.refresh();
        }.bind(this));

        this.tab.on('afterrender', function (tabId) {
            this.tabPanel.setActiveItem(tabId);

            const postOpenDocumentLink = new CustomEvent(opendxp.events.postOpenDocument, {
                detail: {
                    document: this,
                    type: 'link'
                }
            });

            document.dispatchEvent(postOpenDocumentLink);
        }.bind(this, tabId));

        this.removeLoadingPanel();

        this.addToMainTabPanel();

        // recalculate the layout
        opendxp.layout.refresh();
    },

    getLayoutToolbar: function () {

        if (!this.toolbar) {
            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.SplitButton({
                text: t('save_and_publish'),
                iconCls: 'opendxp_icon_save_white',
                cls: 'opendxp_save_button',
                scale: 'medium',
                handler: this.publish.bind(this),
                menu: [
                    {
                        text: t('save_pubish_close'),
                        iconCls: 'opendxp_icon_save',
                        handler: this.publishClose.bind(this)
                    },
                    {
                        text: t('save_only_scheduled_tasks'),
                        iconCls: 'opendxp_icon_save',
                        handler: this.save.bind(this, 'scheduler', 'scheduler')
                    }
                ]
            });

            if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                this.toolbarSubmenu = new Ext.Button({
                    ...opendxp.helpers.headbar.getSubmenuConfig()
                });
            }

            this.toolbarButtons.unpublish = new Ext.Button({
                text: t('unpublish'),
                iconCls: 'opendxp_material_icon_unpublish opendxp_material_icon',
                scale: 'medium',
                handler: this.unpublish.bind(this)
            });

            if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                this.toolbarButtons.unpublish = Ext.create('Ext.menu.Item', {
                    text: t('unpublish'),
                    iconCls: 'opendxp_material_icon_unpublish opendxp_material_icon',
                    scale: 'medium',
                    handler: this.unpublish.bind(this)
                })
            }

            this.toolbarButtons.remove = new Ext.Button({
                tooltip: t('delete'),
                iconCls: 'opendxp_material_icon_delete opendxp_material_icon',
                scale: 'medium',
                handler: this.remove.bind(this)
            });

            this.toolbarButtons.rename = new Ext.Button({
                tooltip: t('rename'),
                iconCls: 'opendxp_material_icon_rename opendxp_material_icon',
                scale: 'medium',
                handler: this.rename.bind(this)
            });

            var buttons = [];

            if (this.isAllowed('publish')) {
                buttons.push(this.toolbarButtons.publish);
            }
            if (this.isAllowed('unpublish') && !this.data.locked) {
                if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                    this.toolbarSubmenu.menu.add(
                        this.toolbarButtons.unpublish
                    )
                } else {
                    buttons.push(this.toolbarButtons.unpublish);
                }
            }

            buttons.push('-');

            if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                buttons.push(this.toolbarSubmenu);
            }

            if (this.isAllowed('delete') && !this.data.locked) {
                if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                    this.toolbarSubmenu.menu.add({
                        text: t('delete'),
                        iconCls: 'opendxp_material_icon_delete opendxp_material_icon',
                        scale: 'medium',
                        handler: this.remove.bind(this)
                    });
                } else {
                    buttons.push(this.toolbarButtons.remove);
                }
            }
            if (this.isAllowed('rename') && !this.data.locked) {
                if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                    this.toolbarSubmenu.menu.add({
                        text: t('rename'),
                        iconCls: 'opendxp_material_icon_rename opendxp_material_icon',
                        scale: 'medium',
                        handler: this.rename.bind(this)
                    });
                } else {
                    buttons.push(this.toolbarButtons.rename);
                }
            }

            buttons.push({
                tooltip: t('reload'),
                iconCls: 'opendxp_material_icon_reload opendxp_material_icon',
                scale: 'medium',
                handler: this.reload.bind(this)
            });

            if (opendxp.elementservice.showLocateInTreeButton('document')) {
                buttons.push({
                    tooltip: t('show_in_tree'),
                    iconCls: 'opendxp_material_icon_locate opendxp_material_icon',
                    scale: 'medium',
                    handler: this.selectInTree.bind(this)
                });
            }

            if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                this.toolbarSubmenu.menu.add(this.getTranslationButtons(true));
            } else {
                buttons.push(this.getTranslationButtons());
            }

            if (!opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                buttons.push('-');
                buttons.push({
                    xtype: 'tbtext',
                    text: t('id') + ' ' + this.data.id,
                    scale: 'medium'
                });
            }

            this.toolbar = new Ext.Toolbar({
                id: 'document_toolbar_' + this.id,
                region: 'north',
                border: false,
                ...(() => opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled() ? { flex: 3 } : { })(),
                cls: 'opendxp_main_toolbar',
                items: buttons,
                overflowHandler: 'scroller'
            });

            this.toolbar.on('afterrender', function () {
                window.setTimeout(function () {
                    // it's not possible to delete the root-node
                    if (this.id == 1) {
                        this.toolbarButtons.remove.hide();
                    }

                    if (!this.data.published) {
                        this.toolbarButtons.unpublish.hide();
                    }
                }.bind(this), 500);
            }.bind(this));
        }

        return this.toolbar;
    },

    getTabPanel: function () {

        var items = [],
            user = opendxp.globalmanager.get('user');

        items.push(this.getLayoutForm());

        if (this.isAllowed('properties')) {
            items.push(this.properties.getLayout());
        }

        if (typeof this.dependencies !== 'undefined') {
            items.push(this.dependencies.getLayout());
        }

        if (this.isAllowed('settings')) {
            items.push(this.scheduler.getLayout());
        }

        if (user.isAllowed('notes_events')) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed('tags_assignment')) {
            items.push(this.tagAssignment.getLayout());
        }

        if (user.isAllowed('workflow_details') && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
        }

        this.tabbar = opendxp.helpers.getTabBar({items: items});
        return this.tabbar;
    },

    getLayoutForm: function () {

        if (!this.panel) {
            var internalTypeField = new Ext.form.Hidden({
                fieldLabel: 'internalType',
                value: this.data.internalType,
                name: 'internalType',
                readOnly: true,
                width: 520
            });

            var linkTypeField = new Ext.form.Hidden({
                fieldLabel: 'linktype',
                value: this.data.linktype,
                name: 'linktype',
                readOnly: true,
                width: 520
            });


            var path = '';
            if (this.data.rawHref) {
                path = this.data.rawHref;
            }

            let isChangeAllowed = this.data.userPermissions.publish;

            var pathField = new Ext.form.TextField({
                name: 'path',
                fieldLabel: t('path'),
                value: path,
                fieldCls: 'input_drop_target',
                width: 500,
                disabled: !isChangeAllowed,
            });

            pathField.on('render', function (el) {
                let currentId = this.data.id;
                let dd = new Ext.dd.DropZone(el.getEl().dom.parentNode.parentNode, {
                    ddGroup: 'element',

                    getTargetFromEvent: function (e) {
                        return this.getEl();
                    },

                    onNodeOver: function (target, dd, e, data) {

                        const record = data.records[0];

                        if (data.records.length !== 1) {
                            return false;
                        }

                        if (record.data.elementType === 'document' && record.data.id === currentId) {
                            return false;
                        }

                        if (
                            record.data.type !== 'folder' &&
                            ['document', 'asset', 'object'].includes(record.data.elementType)
                        ) {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        }

                        return Ext.dd.DropZone.prototype.dropNotAllowed;
                    },

                    onNodeDrop: function (target, dd, e, data) {

                        const record = data.records[0];

                        if (record.data.elementType === 'document' && record.data.id === currentId) {
                            return false;
                        }

                        if (!opendxp.helpers.dragAndDropValidateSingleItem(data) || !isChangeAllowed) {
                            return false;
                        }

                        if (
                            record.data.type !== 'folder' &&
                            ['document', 'asset', 'object'].includes(record.data.elementType)
                        ) {
                            internalTypeField.setValue(record.data.elementType);
                            linkTypeField.setValue('internal');
                            pathField.setValue(record.data.path);

                            return true;
                        }

                        return false;

                    }.bind(this)
                });

                el.getEl().on('contextmenu', function(e) {
                    var menu = new Ext.menu.Menu();
                    menu.add(new Ext.menu.Item({
                        text: t('empty'),
                        iconCls: 'opendxp_icon_delete',
                        hidden: !isChangeAllowed,
                        handler: function (item) {
                            item.parentMenu.destroy();
                            pathField.setValue('');
                            internalTypeField.setValue('');
                            linkTypeField.setValue('');
                        }.bind(this)
                    }));

                    menu.add(new Ext.menu.Item({
                        text: t('open'),
                        iconCls: 'opendxp_icon_open',
                        handler: function (item) {
                            item.parentMenu.destroy();
                            if(linkTypeField.getValue() === 'internal') {
                                opendxp.helpers.openElement(pathField.getValue(), internalTypeField.getValue());
                            } else {
                                window.open(pathField.getValue(), '_blank');
                            }
                        }.bind(this)
                    }));

                    if(opendxp.helpers.hasSearchImplementation()) {
                        menu.add(new Ext.menu.Item({
                            text: t('search'),
                            iconCls: 'opendxp_icon_search',
                            hidden: !isChangeAllowed,
                            handler: function (item) {
                                item.parentMenu.destroy();
                                opendxp.helpers.itemselector(false, function (data) {
                                    pathField.setValue(data.fullpath);
                                    linkTypeField.setValue('internal');
                                    internalTypeField.setValue(data.type);
                                }.bind(this), {type: ['document', 'asset', 'object']})

                            }.bind(this)
                        }));
                    }

                    menu.showAt(e.getXY());

                    e.stopEvent();
                }.bind(this));

            }.bind(this));

            var items = [
                pathField,
                {
                    xtype: 'button',
                    iconCls: 'opendxp_icon_open',
                    style: 'margin-left: 5px',
                    handler: function() {
                        if (pathField.getValue()) {
                            if(linkTypeField.getValue() === 'internal') {
                                opendxp.helpers.openElement(pathField.getValue(), internalTypeField.getValue());
                            } else {
                                window.open(pathField.getValue(), '_blank');
                            }
                        }
                    }.bind(this)
                },
                {
                    xtype: 'button',
                    iconCls: 'opendxp_icon_delete',
                    style: 'margin-left: 5px',
                    hidden: !isChangeAllowed,
                    handler: function () {
                        pathField.setValue('');
                        internalTypeField.setValue('');
                        linkTypeField.setValue('');
                    }.bind(this)
                }
            ];

            if(opendxp.helpers.hasSearchImplementation()) {
                items.push({
                    xtype: 'button',
                    iconCls: 'opendxp_icon_search',
                    style: 'margin-left: 5px',
                    hidden: !isChangeAllowed,
                    handler: function () {
                        opendxp.helpers.itemselector(false, function (data) {

                            if (data.type === 'document' && this.data.id === data.id) {
                                Ext.Msg.alert(t('error'), t('link_recursion_error'));

                                return false;
                            }

                            pathField.setValue(data.fullpath);
                            linkTypeField.setValue('internal');
                            internalTypeField.setValue(data.type);
                        }.bind(this), {type: ['document', 'asset', 'object']})
                    }.bind(this)
                });
            }

            this.panel = new Ext.form.FormPanel({
                title: t('settings'),
                iconCls: 'opendxp_material_icon_settings opendxp_material_icon',
                autoHeight: true,
                labelWidth: 200,
                defaultType: 'textfield',
                bodyStyle: 'padding:10px;',
                region: 'center',
                items: [
                    internalTypeField,
                    linkTypeField,
                    {
                        xtype: 'fieldcontainer',
                        layout: 'hbox',
                        items: items

                    },
                    new Ext.toolbar.Spacer({
                        height: 50
                    })
                ]
            });
        }

        return this.panel;
    },

    rename: function () {
        if (this.isAllowed('rename') && !this.data.locked) {
            var options = {
                elementType: 'document',
                elementSubType: this.getType(),
                id: this.id,
                default: this.data.key
            }
            opendxp.elementservice.editElementKey(options);
        }
    }
});


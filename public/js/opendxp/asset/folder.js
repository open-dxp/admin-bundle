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

opendxp.registerNS("opendxp.asset.folder");
/**
 * @private
 */
opendxp.asset.folder = Class.create(opendxp.asset.asset, {

    initialize: function(id, options) {

        this.options = options;
        this.id = intval(id);
        this.setType("folder");
        this.addLoadingPanel();

        const preOpenAssetFolder = new CustomEvent(opendxp.events.preOpenAsset, {
            detail: {
                object: this,
                type: "folder"
            },
            cancelable: true
        });

        const isAllowed = document.dispatchEvent(preOpenAssetFolder);
        if (!isAllowed) {
            this.removeLoadingPanel();
            return;
        }

        var user = opendxp.globalmanager.get("user");

        this.properties = new opendxp.element.properties(this, "asset");

        if (opendxp.settings.dependency) {
            this.dependencies = new opendxp.element.dependencies(this, "asset");
        }

        if (user.isAllowed("notes_events")) {
            this.notes = new opendxp.element.notes(this, "asset");
        }

        this.tagAssignment = new opendxp.element.tag.assignment(this, "asset");
        this.listfolder = new opendxp.asset.listfolder(this, "folder");
        this.workflows = new opendxp.element.workflows(this, "asset");

        this.getData();
    },

    getTabPanel: function () {


        var items = [];
        var user = opendxp.globalmanager.get("user");

        var proxy = {
            type: 'ajax',
            url: Routing.generate('opendxp_admin_asset_getfoldercontentpreview'),
            reader: {
                type: 'json',
                rootProperty: 'assets'
            },
            extraParams: {
                id: this.id
            }
        };

        this.store = new Ext.data.Store({
            proxy: proxy,
            fields: ['url', "filename", "filenameDisplay", "type", "id", "idPath"],
            listeners: {
                "load": function () {
                    if(this.store.getCount() === 0) {
                        this.tabbar.setActiveItem(this.listfolder.getLayout());
                        this.tabbar.remove(this.dataview);
                    }

                    try {
                        this.dataview.reload();
                    }
                    catch (e) {
                    }
                }.bind(this),
                "datachanged": function () {
                    try {
                        this.dataview.reload();
                    }
                    catch (e) {
                    }
                }.bind(this)
            }
        });
        this.store.load();

        var tpl = new Ext.XTemplate(
            '<tpl for=".">',
            '<div class="thumb-wrap" id="{type}_{id}" data-idpath="{idPath}">',
            '<img class="thumb" src="{url}" loading="lazy" draggable="false">',
            '<span class="filename" title="{filename}">{filenameDisplay}</span></div>',
            '</tpl>',
            '<div class="x-clear"></div>'
        );

        var pageSize = opendxp.helpers.grid.getDefaultPageSize(-1);

        this.dataview = new Ext.Panel({
            layout:'fit',
            bodyCls: "asset_folder_preview",
            title: t("preview"),
            iconCls: "opendxp_material_icon_devices opendxp_material_icon",
            items: new Ext.DataView({
                store: this.store,
                autoScroll: true,
                tpl: tpl,
                itemSelector: '.thumb-wrap',
                emptyText: ' ',
                listeners: {
                    "itemclick": function (view, record, item, index, e, eOpts ) {
                        var data = item.getAttribute("id").split("_");
                        opendxp.helpers.openAsset(data[1], data[0]);
                    },
                    "afterrender": function(el) {
                        el.on("itemcontextmenu",
                            function(view, record, item, index, e, eOpts ) {
                                e.stopEvent();
                                this.showContextMenu(item, e, record);
                            }.bind(this),
                        null, {preventDefault: true});
                    }.bind(this)
                }
            }),
            bbar: opendxp.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: pageSize})
        });

        items.push(this.dataview);

        items.push(this.listfolder.getLayout());

        if (this.isAllowed("properties")) {
            items.push(this.properties.getLayout());
        }

        if (typeof this.dependencies !== "undefined") {
            items.push(this.dependencies.getLayout());
        }


        if (user.isAllowed("notes_events")) {
            items.push(this.notes.getLayout());
        }

        if (user.isAllowed("tags_assignment")) {
            items.push(this.tagAssignment.getLayout());
        }

        if (user.isAllowed("workflow_details") && this.data.workflowManagement && this.data.workflowManagement.hasWorkflowManagement === true) {
            items.push(this.workflows.getLayout());
        }

        this.tabbar = opendxp.helpers.getTabBar({items: items});
        return this.tabbar;
    },

    showContextMenu: function(domEl, event, node) {
        var data = domEl.getAttribute("id");
        var splitted = data.split("_");
        var type = splitted[0];
        var id = splitted[1];

        var menu = new Ext.menu.Menu();
        menu.add(new Ext.menu.Item({
            text: t('open'),
            iconCls: "opendxp_icon_open",
            handler: function (id, type) {
                opendxp.helpers.openAsset(id, type);
            }.bind(this, id, type)
        }));

        if (opendxp.elementservice.showLocateInTreeButton("asset")) {
            menu.add(new Ext.menu.Item({
                text: t('show_in_tree'),
                iconCls: "opendxp_icon_show_in_tree",
                handler: function () {
                    try {
                        try {
                            opendxp.treenodelocator.showInTree(node.id, "asset", this);
                        } catch (e) {
                            console.log(e);
                        }

                    } catch (e2) {
                        console.log(e2);
                    }
                }
            }));
        }

        if (this.isAllowed("delete")) {
            menu.add(new Ext.menu.Item({
                text: t('delete'),
                iconCls: "opendxp_icon_delete",
                handler: function () {

                    var options = {
                        "elementType": "asset",
                        "id": id,
                        "success": function () {
                            this.store.reload();
                        }.bind(this)
                    };

                    opendxp.elementservice.deleteElement(options);
                }.bind(this, id)
            }));
        }
        menu.showAt(event.pageX, event.pageY);
    },

    getLayoutToolbar : function () {

        if (!this.toolbar) {

            var buttons = [];

            this.toolbarButtons = {};

            this.toolbarButtons.publish = new Ext.Button({
                text: t("save"),
                iconCls: "opendxp_icon_save_white",
                cls: "opendxp_save_button",
                scale: "medium",
                handler: this.save.bind(this)
            });

            if(this.isAllowed("publish")) {
                buttons.push(this.toolbarButtons.publish);
            }

            this.toolbarButtons.remove = new Ext.Button({
                tooltip: t('delete'),
                iconCls: "opendxp_material_icon_delete opendxp_material_icon",
                scale: "medium",
                handler: function () {
                    var options = this.listfolder.onRawDeleteSelectedRows();
                    if (!options) {
                        options = {
                            "elementType" : "asset",
                            "id": this.id
                        };
                    }
                    opendxp.elementservice.deleteElement(options);
                }.bind(this)
            });

            this.toolbarButtons.rename = new Ext.Button({
                tooltip: t('rename'),
                iconCls: "opendxp_material_icon_rename opendxp_material_icon",
                scale: "medium",
                handler: this.rename.bind(this)
            });

            buttons.push("-");

            if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                this.toolbarSubmenu = new Ext.Button({
                    ...opendxp.helpers.headbar.getSubmenuConfig()
                });

                buttons.push(this.toolbarSubmenu);
            }

            if (this.isAllowed("delete") && !this.data.locked && this.data.id != 1) {
                if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                    this.toolbarSubmenu.menu.add({
                        text: t('delete'),
                        iconCls: "opendxp_material_icon_delete opendxp_material_icon",
                        scale: "medium",
                        handler: function () {
                            var options = this.listfolder.onRawDeleteSelectedRows();
                            if (!options) {
                                options = {
                                    "elementType" : "asset",
                                    "id": this.id
                                };
                            }
                            opendxp.elementservice.deleteElement(options);
                        }.bind(this)
                    });
                } else {
                    buttons.push(this.toolbarButtons.remove);
                }
            }
            if (this.isAllowed("rename") && !this.data.locked && this.data.id != 1) {
                if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                    this.toolbarSubmenu.menu.add({
                        text: t('rename'),
                        iconCls: "opendxp_material_icon_rename opendxp_material_icon",
                        scale: "medium",
                        handler: this.rename.bind(this)
                    });
                } else {
                    buttons.push(this.toolbarButtons.rename);
                }
            }

            const downloadAsZipConfig = {
                ...(() => opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled() ? { text: t('download_as_zip') } : { tooltip: t('download_as_zip') })(),
                iconCls: "opendxp_material_icon_download_zip opendxp_material_icon",
                scale: "medium",
                menu: opendxp.elementservice.getAssetZipDownloadMenu(this.id)
            }

            if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                this.toolbarSubmenu.menu.add(downloadAsZipConfig);
            } else {
                buttons.push(downloadAsZipConfig);
            }

            buttons.push({
                tooltip: t('reload'),
                iconCls: "opendxp_material_icon_reload opendxp_material_icon",
                scale: "medium",
                handler: this.reload.bind(this)
            });

            if (opendxp.elementservice.showLocateInTreeButton("asset")) {
                buttons.push({
                    tooltip: t('show_in_tree'),
                    iconCls: "opendxp_material_icon_locate opendxp_material_icon",
                    scale: "medium",
                    handler: this.selectInTree.bind(this)
                });
            }

            buttons.push({
                xtype: "splitbutton",
                tooltip: t("show_metainfo"),
                iconCls: "opendxp_material_icon_info opendxp_material_icon",
                scale: "medium",
                handler: this.showMetaInfo.bind(this),
                menu: this.getMetaInfoMenuItems()
            });

            if (!opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
                buttons.push("-");
                buttons.push({
                    xtype: 'tbtext',
                    text: t("id") + " " + this.data.id,
                    scale: "medium"
                });
            }

            this.toolbar = new Ext.Toolbar({
                id: "asset_toolbar_" + this.id,
                region: "north",
                border: false,
                ...(() => opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled() ? { flex: 3 } : { })(),
                cls: "opendxp_main_toolbar",
                items: buttons,
                overflowHandler: 'scroller'
            });
        }

        return this.toolbar;
    },

    getMetaInfo: function() {
        return {
            id: this.data.id,
            path: this.data.path + this.data.filename,
            type: this.data.type,
            modificationdate: this.data.modificationDate,
            creationdate: this.data.creationDate,
            usermodification: this.data.userModification,
            usermodification_name: this.data.userModificationFullname,
            userowner: this.data.userOwner,
            userowner_name: this.data.userOwnerFullname,
            deeplink: opendxp.helpers.getDeeplink("asset", this.data.id, this.data.type)
        };
    },

    showMetaInfo: function() {
        var metainfo = this.getMetaInfo();

        new opendxp.element.metainfo([
            {
                name: "id",
                value: metainfo.id
            },
            {
                name: "path",
                value: metainfo.path
            }, {
                name: "type",
                value: metainfo.type
            }, {
                name: "modificationdate",
                type: "date",
                value: metainfo.modificationdate
            }, {
                name: "creationdate",
                type: "date",
                value: metainfo.creationdate
            }, {
                name: "usermodification",
                type: "user",
                value: '<span data-uid="' + metainfo.usermodification + '">' + metainfo.usermodification_name + '</span>'
            }, {
                name: "userowner",
                type: "user",
                value: '<span data-uid="' + metainfo.userowner + '">' + metainfo.userowner_name + '</span>'
            }, {
                name: "deeplink",
                value: metainfo.deeplink
            }
        ], "folder");
    },

    rename: function () {
        if (this.isAllowed("rename") && !this.data.locked && this.data.id != 1) {
            var options = {
                elementType: "asset",
                elementSubType: this.getType(),
                id: this.id,
                default: this.data.filename
            }
            opendxp.elementservice.editElementKey(options);
        }
    }
});

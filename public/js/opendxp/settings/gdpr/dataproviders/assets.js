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

opendxp.registerNS("opendxp.settings.gdpr.dataproviders.assets");
/**
 * @private
 */
opendxp.settings.gdpr.dataproviders.assets = Class.create({

    title: t("gdpr_dataSource_assets"),
    iconCls: "opendxp_icon_asset",

    searchParams: [],

    initialize: function (searchParams) {
        this.searchParams = searchParams;
        this.getPanel();
    },

    getPanel: function () {

        if(!this.panel) {

            this.panel = new Ext.Panel({
                title: this.title,
                layout: "border",
                iconCls: this.iconCls,
                closable: false
            });

            this.initGrid();
            this.store.load();
        }

        return this.panel;
    },

    initGrid: function () {
        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: opendxp.helpers.grid.getDefaultPageSize(),
            proxy : {
                type: 'ajax',
                url: Routing.generate('opendxp_admin_gdpr_asset_searchasset'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                },
                extraParams: this.searchParams
            },
            fields: ["id","fullpath","type","filename"]
        });

        var columns = [
            {text: t("type"), width: 40, sortable: true, dataIndex: 'subtype',
                renderer: function (value, metaData, record, rowIndex, colIndex, store) {
                    return '<div style="height: 16px;" class="opendxp_icon_asset  opendxp_icon_' + value + '" name="'
                        + t(record.data.subtype) + '">&nbsp;</div>';
                }
            },
            {text: 'ID', width: 60, sortable: true, dataIndex: 'id', hidden: false},
            {text: t("published"), width: 40, sortable: true, dataIndex: 'published', hidden: true},
            {text: t("path"), flex: 200, sortable: true, dataIndex: 'fullpath'},
            {text: t("filename"), width: 200, sortable: true, dataIndex: 'filename', hidden: true},
            {text: t("subtype"), width: 200, sortable: true, dataIndex: 'subtype'},
            {
                xtype: 'actioncolumn',
                menuText: t('gdpr_dataSource_export'),
                width: 40,
                items: [
                    {
                        tooltip: t('gdpr_dataSource_export'),
                        icon: "/bundles/opendxpadmin/img/flat-color-icons/export.svg",
                        handler: function (grid, rowIndex) {
                            var data = grid.getStore().getAt(rowIndex);
                            if (!data.get("permissions").view) {
                                opendxp.helpers.showPermissionError("view");
                                return;
                            }
                            opendxp.helpers.download(Routing.generate('opendxp_admin_gdpr_asset_exportassets', {id: data.data.id}));
                        }.bind(this),
                        getClass: function (v, meta, rec) {
                            if (!rec.get("permissions").view) {
                                return "inactive_actioncolumn";
                            }
                        }
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                menuText: t('open'),
                width: 40,
                items: [
                    {
                        tooltip: t('open'),
                        icon: "/bundles/opendxpadmin/img/flat-color-icons/open_file.svg",
                        handler: function (grid, rowIndex) {
                            var data = grid.getStore().getAt(rowIndex);
                            if (!data.get("permissions").view) {
                                opendxp.helpers.showPermissionError("view");
                                return;
                            }
                            opendxp.helpers.openAsset(data.data.id, data.data.subtype);
                        }.bind(this),
                        getClass: function (v, meta, rec) {
                            if (!rec.get("permissions").view) {
                                return "inactive_actioncolumn";
                            }
                        }
                    }
                ]
            },
            {
                xtype: 'actioncolumn',
                menuText: t('remove'),
                width: 40,
                items: [
                    {
                        tooltip: t('remove'),
                        icon: "/bundles/opendxpadmin/img/flat-color-icons/delete.svg",
                        handler: function (grid, rowIndex) {

                            var data = grid.getStore().getAt(rowIndex);
                            if (!data.get("permissions").delete) {
                                opendxp.helpers.showPermissionError("delete");
                                return;
                            }

                            var options = {
                                "elementType": "asset",
                                "id": data.data.id,
                                "success": function () {
                                    this.store.reload();
                                }.bind(this)
                            };
                            opendxp.elementservice.deleteElement(options);

                        }.bind(this),
                        isDisabled: function(view, rowIndex, colIndex, item, record) {
                            return record.data["__gdprIsDeletable"] == false;
                        },
                        getClass: function (v, meta, rec) {
                            if (!rec.get("permissions").delete) {
                                return "inactive_actioncolumn";
                            }
                        }
                    }
                ]
            }
        ];


        this.pagingtoolbar = opendxp.helpers.grid.buildDefaultPagingToolbar(this.store);
        this.gridPanel = Ext.create('Ext.grid.Panel', {
            region: "center",
            store: this.store,
            border: false,
            columns: columns,
            loadMask: true,
            columnLines: true,
            stripeRows: true,
            plugins: ['opendxp.gridfilters'],
            viewConfig: {
                forceFit: false
            },
            cls: 'opendxp_asset_grid_panel',
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.pagingtoolbar,
            listeners: {
                rowdblclick: function (grid, record, tr, rowIndex, e, eOpts ) {
                    var data = grid.getStore().getAt(rowIndex);
                    opendxp.helpers.openAsset(data.data.id, data.data.subtype);
                }.bind(this)
            }
        });

        this.panel.add(this.gridPanel);

    }

});

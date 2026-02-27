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

opendxp.registerNS("opendxp.object.versions");
/**
 * @private
 */
opendxp.object.versions = Class.create({

    initialize: function (object) {
        this.object = object;
    },

    getLayout: function () {

        if (this.layout == null) {

            var modelName = 'opendxp.model.objectversions';
            if (!Ext.ClassManager.get(modelName)) {
                Ext.define(modelName, {
                    extend: 'Ext.data.Model',
                    fields: ['id', { name: "date", type: 'date', dateFormat: 'timestamp' }, 'scheduled', 'note', {
                        name: 'name', convert: function (v, rec) {
                            if (rec.data) {
                                if (rec.data.user) {
                                    if (rec.data.user.name) {
                                        return rec.data.user.name;
                                    }
                                }
                            }
                            return null;
                        }
                    }, 'versionCount']
                });
            }

            this.store = new Ext.data.Store({
                model: modelName,
                sorters: [
                    {
                        property: 'versionCount',
                        direction: 'DESC'
                    },
                    {
                        property: 'id',
                        direction: 'DESC'
                    }],
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('opendxp_admin_element_getversions'),
                    extraParams: {
                        id: this.object.id,
                        elementType: "object"
                    },
                    // Reader is now on the proxy, as the message was explaining
                    reader: {
                        type: 'json',
                        rootProperty: 'versions'
                    }

                }
            });

            this.store.on("update", this.dataUpdate.bind(this));

            this.cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
                clicksToEdit: 2
            });

            var grid = Ext.create('Ext.grid.Panel', {
                store: this.store,
                plugins: [this.cellEditing, 'gridfilters'],
                columns: [
                    {
                        text: t("published"),
                        width: 50,
                        sortable: false,
                        dataIndex: 'id',
                        renderer: function (d, metaData, cellValues) {
                            var d = Ext.Date.format(cellValues.get('date'), "timestamp");
                            var versionCount = cellValues.get('versionCount');
                            var index = cellValues.get('index');
                            if (index === 0 && d == this.object.data.general.versionDate && versionCount == this.object.data.general.versionCount) {
                                if(this.object.data.general.published) {
                                    metaData.tdCls = "opendxp_icon_publish";
                                } else {
                                    metaData.tdCls = "opendxp_icon_sql";
                                    metaData.tdAttr = 'data-qtip="' + t('version_currently_saved_in_database') + '"';
                                }
                            }
                            return "";
                        }.bind(this),
                        editable: false
                    },
                    {
                        text: t("date"), width: 150, sortable: true, dataIndex: 'date', filter: 'date', renderer: function (d) {
                            return Ext.Date.format(d, opendxp.globalmanager.get('localeDateTime').getDateTimeFormat());
                        }
                    },
                    {text: "ID", sortable: true, dataIndex: 'id', editable: false, width: 60},
                    {text: t("user"), sortable: true, dataIndex: 'name', filter: 'list'},
                    {
                        text: t("scheduled"),
                        width: 130,
                        sortable: true,
                        dataIndex: 'scheduled',
                        renderer: function (d) {
                            if (d != null) {
                                var date = new Date(d * 1000);
                                return Ext.Date.format(date, opendxp.globalmanager.get('localeDateTime').getDateTimeFormat());
                            }
                        },
                        editable: false
                    },
                    {text: t("note"), sortable: true, dataIndex: 'note', filter: 'string', editor: new Ext.form.TextField(), renderer: Ext.util.Format.htmlEncode},
                    {
                        xtype: "checkcolumn",
                        text: t("auto_save"),
                        disabled : true,
                        dataIndex: "autoSave",
                        width: 50
                    }
                ],
                stripeRows: true,
                width: 450,
                title: t("press_crtl_and_select_to_compare"),
                region: "west",
                split: true,
                selModel: new Ext.selection.RowModel({
                    mode: 'MULTI'
                }),
                viewConfig: {
                    xtype: 'patchedgridview',
                    enableTextSelection: true
                }
            });

            grid.on("rowclick", this.onRowClick.bind(this));
            grid.on("rowcontextmenu", this.onRowContextmenu.bind(this));
            grid.on("beforerender", function () {
                this.store.load();
            }.bind(this));

            grid.reference = this;

            this.iframeId = 'object_version_iframe_' + this.object.id;

            var preview = new Ext.Panel({
                title: t("preview"),
                region: "center",
                bodyCls: "opendxp_overflow_scrolling",
                html: '<iframe src="about:blank" frameborder="0" style="width:100%;" id="' + this.iframeId + '"></iframe>'
            });

            this.layout = new Ext.Panel({
                title: t('versions'),
                bodyStyle: 'padding:20px 5px 20px 5px;',
                border: false,
                layout: "border",
                iconCls: "opendxp_material_icon_versions opendxp_material_icon",
                items: [grid, preview]
            });

            preview.on("resize", this.setLayoutFrameDimensions.bind(this));
        }

        return this.layout;
    },

    setLayoutFrameDimensions: function (el, width, height, rWidth, rHeight) {
        Ext.get(this.iframeId).setStyle({
            height: (height - 38) + "px"
        });
    },

    onRowClick: function (grid, record, tr, rowIndex, e, eOpts) {
        var selModel = grid.getSelectionModel();
        if (selModel.getCount() > 2) {
            selModel.select(record);
        }

        if (selModel.getCount() > 1) {
            this.compareVersions(grid, rowIndex, e);
        } else {
            this.showVersionPreview(grid, rowIndex, e);
        }
    },

    compareVersions: function (grid, rowIndex, event) {
        if (grid.getSelectionModel().getCount() < 3) {

            var selections = grid.getSelectionModel().getSelection();

            var url = Routing.generate('opendxp_admin_dataobject_dataobject_diffversions', {
                from: selections[0].data.id,
                to: selections[1].data.id,
                userTimezone: getUserTimezone()
            });
            Ext.get(this.iframeId).dom.src = url;
        }
    },

    showVersionPreview: function (grid, rowIndex, event) {

        var store = grid.getStore();
        var data = store.getAt(rowIndex).data;
        var versionId = data.id;
        var url = Routing.generate('opendxp_admin_dataobject_dataobject_previewversion', {
            id: versionId,
            userTimezone: getUserTimezone()
        });
        Ext.get(this.iframeId).dom.src = url;
    },

    onRowContextmenu: function (grid, record, tr, rowIndex, e, eOpts) {

        var menu = new Ext.menu.Menu();

        if (this.object.isAllowed("publish")) {
            menu.add(new Ext.menu.Item({
                text: t('publish'),
                iconCls: "opendxp_icon_publish",
                handler: this.publishVersion.bind(this, rowIndex, grid)
            }));
        }

        menu.add(new Ext.menu.Item({
            text: t('delete'),
            iconCls: "opendxp_icon_delete",
            handler: this.removeVersion.bind(this, rowIndex, grid)
        }));

        menu.add(new Ext.menu.Item({
            text: t('clear_all'),
            iconCls: "opendxp_icon_delete",
            handler: this.removeAllVersion.bind(this, rowIndex, grid)
        }));

        e.stopEvent();
        menu.showAt(e.pageX, e.pageY);
    },

    removeVersion: function (index, grid) {

        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_element_deleteversion'),
            method: 'DELETE',
            params: {id: versionId}
        });

        grid.getStore().removeAt(index);
    },

    removeAllVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var elememntId = data.cid;

        if (elememntId > 0) {
            Ext.Msg.confirm(t('clear_all'), t('clear_version_message'), function (btn) {
                if (btn === 'yes') {
                    var modificationDate = this.object.data.general.modificationDate;

                    Ext.Ajax.request({
                        url: Routing.generate('opendxp_admin_element_deleteallversion'),
                        method: 'DELETE',
                        params: {id: elememntId, date: modificationDate, type: 'object'}
                    });

                    //get sub collection of versions for removel. Keep current version
                    var removeCollection = grid.getStore().getData().createFiltered(function (item) {
                        return item.get('date') != modificationDate;
                    });

                    grid.getStore().remove(removeCollection.getRange());
                }
            }.bind(this));
        }
    },

    publishVersion: function (index, grid) {
        var data = grid.getStore().getAt(index).data;
        var versionId = data.id;

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_dataobject_dataobject_publishversion'),
            method: "POST",
            params: {id: versionId},
            success: function (response) {
                var rdata = Ext.decode(response.responseText);

                if (rdata.success) {
                    this.object.reload();

                    opendxp.helpers.updateTreeElementStyle('object', this.object.id, rdata.treeData);
                } else {
                    Ext.MessageBox.alert(t("error"), rdata.message);
                }

            }.bind(this)
        });
    },

    reload: function () {
        this.store.reload();
    },

    dataUpdate: function (store, record, operation, columns) {

        if (operation == "edit") {
            if (in_array("public", columns) || in_array("note", columns)) {
                Ext.Ajax.request({
                    method: "post",
                    url: Routing.generate('opendxp_admin_element_versionupdate'),
                    method: 'PUT',
                    params: {
                        data: Ext.encode(record.data)
                    }
                });
            }
        }

        store.commitChanges();
    }
});

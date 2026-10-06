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

opendxp.registerNS("opendxp.object.classificationstore.propertiespanel");
/**
 * @private
 */
opendxp.object.classificationstore.propertiespanel = Class.create({

    initialize: function (storeConfig, container) {
        this.container = container;
        this.storeConfig = storeConfig;
    },

    getPanel: function () {
        if (this.layout == null) {
            this.layout = new Ext.Panel({
                title: t("classificationstore_properties"),
                iconCls: "opendxp_icon_key",
                border: false,
                layout: "fit",
                region: "center"
            });

            this.createGrid();
        }

        this.layout.on("activate", this.panelActivated.bind(this));

        return this.layout;
    },

    panelActivated: function() {
        if (this.store) {
            this.store.reload();
        }
    },

    createGrid: function(response) {
        this.fields = ['storeId','id', 'name', 'description', 'type',
            'creationDate', 'modificationDate', 'definition', 'title', 'sorter'];

        var readerFields = [];
        for (var i = 0; i < this.fields.length; i++) {
            readerFields.push({name: this.fields[i]});
        }

        var dataComps = Object.keys(opendxp.object.classes.data);
        var allowedDataTypes = [];

        for (var i = 0; i < dataComps.length; i++) {
            var dataComp = opendxp.object.classes.data[dataComps[i]];

            var allowed = false;

            if('object' !== typeof dataComp) {
                if (dataComp.prototype.allowIn['classificationstore']) {
                    allowed = true;
                }
            }

            if (allowed) {
                allowedDataTypes.push([dataComps[i], t(dataComps[i])]);
            }
        }

        this.allowedTypesStore = new Ext.data.SimpleStore({
            fields: ['key', 'name'],
            data: allowedDataTypes
        });

        var proxy = {
            url: Routing.generate('opendxp_admin_dataobject_classificationstore_propertiesget'),
            batchActions: false,
            type: 'ajax',
            reader: {
                type: 'json',
                rootProperty: 'data'
            },
            writer: {
                type: 'json',
                writeAllFields: true,
                rootProperty: 'data',
                encode: 'true'
            },
            extraParams: {
                storeId: this.storeConfig.id
            },
            listeners: {
                exception: function (proxy, response, operation) {
                    opendxp.helpers.rejectFailedStoreUpdate(this.store, response, operation);
                }.bind(this)
            }
        };

        this.store = new Ext.data.Store({
            autoSync: true,
            proxy: proxy,
            fields: readerFields,
            remoteFilter: true,
            remoteSort: true
        });

        var gridColumns = [];

        //gridColumns.push({text: t("store"), width: 40, sortable: true, dataIndex: 'storeId'});
        gridColumns.push({text: "ID", width: 100, sortable: true, dataIndex: 'id'});
        gridColumns.push({
                text: t("name"),
                width: 200,
                sortable: true,
                dataIndex: 'name',
                filter: 'string',
                editor: new Ext.form.TextField({}),
                renderer: Ext.util.Format.htmlEncode
            }

        );

        gridColumns.push({text: t("title"), width: 200, sortable: false, dataIndex: 'title',editor: new Ext.form.TextField({}), filter: 'string',
            renderer: Ext.util.Format.htmlEncode});
        gridColumns.push({text: t("description"), width: 300, sortable: true, dataIndex: 'description',editor: new Ext.form.TextField({}), filter: 'string',
            renderer: Ext.util.Format.htmlEncode});
        gridColumns.push({text: t("definition"), width: 300, sortable: true, hidden: true, dataIndex: 'definition',editor: new Ext.form.TextField({})});
        gridColumns.push({text: t("type"), width: 150, sortable: true, dataIndex: 'type', filter: 'string',
            editor: new Ext.form.ComboBox({
                triggerAction: 'all',
                editable: false,
                store: this.allowedTypesStore,
                displayField:'name',
                valueField: "key"
            }),
            renderer: function(value, metaData, record, rowIndex, colIndex, store, view) {
                return t(value);
            }});

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            menuText: t("classificationstore_detailed_configuration"),
            width: 30,
            items: [
                {
                    tooltip: t("classificationstore_detailed_configuration"),
                    icon: "/bundles/opendxpadmin/img/flat-color-icons/department.svg",
                    handler: this.showDetailedConfig.bind(this)
                }
            ]
        });

        var dateRenderer =  function(d) {
            if (d !== undefined) {
                var date = new Date(d * 1000);
                return Ext.Date.format(date, opendxp.globalmanager.get('localeDateTime').getDateTimeFormat());
            } else {
                return "";
            }
        };


        gridColumns.push(
            {text: t("creationDate"), sortable: true, dataIndex: 'creationDate', editable: false, width: 130,
                hidden: true,
                renderer: dateRenderer
            }
        );

        gridColumns.push(
            {text: t("modificationDate"), sortable: true, dataIndex: 'modificationDate', editable: false, width: 130,
                hidden: true,
                renderer: dateRenderer            }
        );

        gridColumns.push({
            hideable: false,
            xtype: 'actioncolumn',
            menuText: t('remove'),
            width: 30,
            items: [
                {
                    tooltip: t('remove'),
                    icon: "/bundles/opendxpadmin/img/flat-color-icons/delete.svg",
                    handler: function (grid, rowIndex) {
                        var data = grid.getStore().getAt(rowIndex);
                        var id = data.data.id;

                        Ext.Msg.confirm(t('delete'), sprintf(t('delete_message_advanced') + `</br>${t('delete_data_from_it')}`, t('classificationstore_property'), Ext.util.Format.htmlEncode(data.data.name)), function(btn) {
                            if (btn == 'yes') {
                                Ext.Ajax.request({
                                    url: Routing.generate('opendxp_admin_dataobject_classificationstore_deleteproperty'),
                                    method: 'DELETE',
                                    params: {
                                        id: id
                                    },
                                    success: function (response) {
                                        this.store.reload();
                                    }.bind(this)});
                            }
                        }.bind(this));
                    }.bind(this)
                }
            ]
        });


        var pageSize = opendxp.helpers.grid.getDefaultPageSize(-1);
        this.pagingtoolbar = opendxp.helpers.grid.buildDefaultPagingToolbar(this.store, {pageSize: pageSize});

        var cellEditing = Ext.create('Ext.grid.plugin.CellEditing', {
            listeners: {
                // The definition changes silently, so the edit of the cell saves it in the same request.
                validateedit: function (editor, e) {
                    var definitionKey = {name: "name", type: "fieldtype", title: "title"}[e.field];
                    if (!definitionKey) {
                        return;
                    }

                    var definition = Ext.util.JSON.decode(e.record.get("definition"));
                    definition[definitionKey] = e.value;
                    e.record.set("definition", Ext.util.JSON.encode(definition), {silent: true});
                },
                edit: function (editor, e) {
                    if (e.value != e.originalValue) {
                        this.showDetailedConfig(e.grid, e.rowIdx);
                    }
                }.bind(this)
            }
        });

        var plugins = ['gridfilters', cellEditing];

        var gridConfig = {
            frame: false,
            store: this.store,
            border: false,
            columns: gridColumns,
            loadMask: false,
            columnLines: true,
            plugins: plugins,
            bodyCls: "opendxp_editable_grid",
            stripeRows: true,
            trackMouseOver: true,
            viewConfig: {
                forceFit: false
            },
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.pagingtoolbar,
            tbar: [

                {
                    text: t('add'),
                    handler: this.onAdd.bind(this),
                    iconCls: "opendxp_icon_add"
                }
            ]
        } ;

        this.grid = Ext.create('Ext.grid.Panel', gridConfig);

        this.layout.removeAll();
        this.layout.add(this.grid);
        this.layout.updateLayout();
    },

    showDetailedConfig: function (grid, rowIndex) {
        var data = grid.getStore().getAt(rowIndex);
        var type = data.data.type;
        var definition = data.data.definition;
        if (definition) {
            definition = Ext.util.JSON.decode(definition);
            definition.name = Ext.util.Format.htmlEncode(data.data.name);
        } else {
            definition = {
                name: data.data.name
            };
        }

        definition.fieldtype = type;

        var keyDefinitionWindow = new opendxp.object.classificationstore.keyDefinitionWindow(
            definition, data.id, this);
        keyDefinitionWindow.show();
    },

    applyTranslatorConfig: function(keyid, value) {
        var data = this.store.getById(keyid);
        data.set("translator", value);
    },

    applyDetailedConfig: function(keyid, definition) {

        var name = definition.name;
        definition = Ext.util.JSON.encode(definition);

        var record = this.store.getById(keyid);
        record.set({name: name, definition: definition});
    },

    onAdd: function () {
        Ext.MessageBox.prompt(t('classificationstore_mbx_enterkey_title'), t('classificationstore_mbx_enterkey_prompt'),
            this.addFieldComplete.bind(this), null, null, "");
    },

    addFieldComplete: function (button, value, object) {
        value = Ext.util.Format.htmlEncode(value).trim();
        if (button == "ok" && value.length > 1) {
            Ext.Ajax.request({
                url: Routing.generate('opendxp_admin_dataobject_classificationstore_addproperty'),
                method: 'POST',
                params: {
                    name: value,
                    storeId: this.storeConfig.id
                },
                success: function (response) {
                    var data = Ext.decode(response.responseText);

                    if(!data || !data.success) {
                        Ext.Msg.alert(t("classificationstore_error_addkey_title"), t(data && data.message ? data.message : "classificationstore_error_addkey_msg"));
                    } else {

                        this.store.reload({
                                callback: function() {
                                    var rowIndex = this.store.find('name', value);
                                    if (rowIndex != -1) {
                                        var sm = this.grid.getSelectionModel();
                                        sm.select(rowIndex);
                                    }

                                    var lastOptions = this.store.lastOptions;
                                    Ext.apply(lastOptions.params, {
                                        overrideSort: false
                                    });

                                }.bind(this),
                                params: {
                                    overrideSort: true
                                }
                            }

                        );
                    }
                }.bind(this)
            });
        }
        else if (button == "cancel") {
            return;
        }
        else {
            Ext.Msg.alert(t("classificationstore_configuration"), t("classificationstore_invalidname"));
        }
    },


    getData: function () {
        var selected = this.groupGridPanel.getSelectionModel().getSelected();
        if(selected) {
            return selected.data;
        }
        return null;
    },

   openConfig: function(id) {

       var pageSize = opendxp.helpers.grid.getDefaultPageSize(-1);

       var params = {
           storeId: this.storeConfig.id,
           id: id,
           pageSize: pageSize,
           table: "keys"
       };

       var sorters = this.store.getSorters();
       if (sorters.length > 0) {
           var sorter = sorters.getAt(0);
           params.sortKey = sorter.getProperty();
           params.sortDir = sorter.getDirection();
       }

       var noreload = function() {
           return false;
       }
       this.store.addListener("beforeload", noreload);

       this.container.setActiveTab(this.layout);
       this.store.clearFilter(true);

       Ext.Ajax.request({
           url: Routing.generate('opendxp_admin_dataobject_classificationstore_getpage'),
           params: params,
           success: function(response) {
               try {
                   this.store.removeListener("beforeload", noreload);

                   var data = Ext.decode(response.responseText);
                   if (data.success) {
                       this.store.removeListener("beforeload", noreload);
                       this.store.loadPage(data.page, {
                           callback: function() {
                               var selModel = this.grid.getSelectionModel();
                               var record = this.store.getById(id);
                               if (record) {
                                   selModel.select(record);
                                   this.showDetailedConfig(this.grid, this.store.indexOf(record));
                               }
                           }.bind(this)
                       });
                   } else {
                       this.store.reload();
                   }
               } catch (e) {
                   console.log(e);
               }
           }.bind(this),
           failure: function(response) {
               this.store.removeListener("beforeload", noreload);
               this.store.reload();
           }.bind(this)
       });


    }

});

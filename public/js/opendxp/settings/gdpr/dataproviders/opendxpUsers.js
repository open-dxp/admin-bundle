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

opendxp.registerNS('opendxp.settings.gdpr.dataproviders.openDxpUsers');
/**
 * @private
 */
opendxp.settings.gdpr.dataproviders.openDxpUsers = Class.create({

    searchParams: [],

    initialize: function (searchParams) {
        this.searchParams = searchParams;
        this.getPanel();
    },

    getPanel: function () {

        if(!this.panel) {

            this.panel = new Ext.Panel({
                title: t('users') + ' (OpenDXP)',
                layout: 'border',
                iconCls: 'opendxp_icon_user',
                closable: false
            });

            this.initGrid();
        }

        return this.panel;
    },

    initGrid: function () {

        var user = opendxp.globalmanager.get('user');

        this.store = new Ext.data.Store({
            autoDestroy: true,
            remoteSort: true,
            pageSize: opendxp.helpers.grid.getDefaultPageSize(),
            proxy : {
                type: 'ajax',
                url: Routing.generate('opendxp_admin_gdpr_opendxpusers_searchusers'),
                reader: {
                    type: 'json',
                    rootProperty: 'data'
                },
                extraParams: this.searchParams
            },
            autoLoad: true,
            fields: ['id','name','firstname','lastname','email']
        });

        var columns = [
            {text: 'ID', width: 60, sortable: true, dataIndex: 'id', hidden: false},
            {text: t('username'), flex: 100, sortable: true, dataIndex: 'name'},
            {text: t('firstname'), flex: 200, sortable: true, dataIndex: 'firstname'},
            {text: t('lastname'), flex: 200, sortable: true, dataIndex: 'lastname'},
            {text: t('email'), flex: 200, sortable: true, dataIndex: 'email'},
            {
                xtype: 'actioncolumn',
                menuText: t('gdpr_dataSource_export'),
                width: 40,
                items: [
                    {
                        tooltip: t('gdpr_dataSource_export'),
                        icon: '/bundles/opendxpadmin/img/flat-color-icons/export.svg',
                        handler: function (grid, rowIndex) {
                            if (!user.isAllowed('users')) {
                                opendxp.helpers.showPermissionError('users');
                                return;
                            }

                            var data = grid.getStore().getAt(rowIndex);
                            opendxp.helpers.download(Routing.generate('opendxp_admin_gdpr_opendxpusers_exportuserdata', {id: data.data.id}));
                        }.bind(this),
                        getClass: function(v, meta, rec) {
                            if(!user.isAllowed('users')){
                                return 'inactive_actioncolumn';
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
                        icon: '/bundles/opendxpadmin/img/flat-color-icons/delete.svg',
                        handler: function (grid, rowIndex) {
                            if (!user.isAllowed('users')) {
                                opendxp.helpers.showPermissionError('users');
                                return;
                            }

                            var data = grid.getStore().getAt(rowIndex);

                            Ext.MessageBox.show({
                                title: t('delete'),
                                msg: sprintf(t('delete_message_advanced'), t('user'), data.data.text),
                                buttons: Ext.Msg.YESNO ,
                                icon: Ext.MessageBox.QUESTION,
                                fn: function (button) {
                                    if (button === 'yes') {
                                        Ext.Ajax.request({
                                            url: Routing.generate('opendxp_admin_user_delete'),
                                            method: 'DELETE',
                                            params: {
                                                id: data.data.id
                                            },
                                            success: function() {
                                                this.store.reload();
                                            }.bind(this, data)
                                        });
                                    }
                                }.bind(this)
                            });

                        }.bind(this),
                        isDisabled: function(view, rowIndex, colIndex, item, record) {
                            return record.data['__gdprIsDeletable'] == false;
                        },
                        getClass: function(v, meta, rec) {
                            if(!user.isAllowed('users')){
                                return 'inactive_actioncolumn';
                            }
                        }
                    }
                ]
            }
        ];


        this.pagingtoolbar = opendxp.helpers.grid.buildDefaultPagingToolbar(this.store);
        this.gridPanel = Ext.create('Ext.grid.Panel', {
            region: 'center',
            store: this.store,
            border: false,
            columns: columns,
            loadMask: true,
            columnLines: true,
            stripeRows: true,
            plugins: ['opendxp.gridfilters'],
            viewConfig: {
                forceFit: false,
                xtype: 'patchedgridview'
            },
            cls: 'opendxp_object_grid_panel',
            selModel: Ext.create('Ext.selection.RowModel', {}),
            bbar: this.pagingtoolbar
        });

        this.panel.add(this.gridPanel);
    }

});

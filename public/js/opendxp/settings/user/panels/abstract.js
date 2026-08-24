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


opendxp.registerNS("opendxp.settings.user.panels.abstract");
/**
 * @private
 */
opendxp.settings.user.panels.abstract = Class.create({

    initialize: function () {
        this.panels = {};
        this.getTabPanel();
    },

    getEditPanel: function () {
        if (!this.editPanel) {
            this.editPanel = new Ext.TabPanel({
                activeTab: 0,
                items: [],
                region: 'center'
            });
        }

        return this.editPanel;
    },

    getTreeNodeListeners: function () {
        var treeNodeListeners = {
            'itemclick' : this.onTreeNodeClick.bind(this),
            'itemcontextmenu': this.onTreeNodeContextmenu.bind(this),
            'beforeitemappend': function (thisNode, newChildNode, index, eOpts) {
                newChildNode.data.qtip = t('id') +  ": " + newChildNode.data.id;
            }
        };

        return treeNodeListeners;
    },


    remove: function (tree, record) {

        Ext.MessageBox.show({
            title:t('delete'),
            msg: record.hasChildNodes() ? t("are_you_sure_recursive") : sprintf(t("delete_message_advanced"), t('user'), record.data.text),
            buttons: Ext.Msg.OKCANCEL ,
            icon: record.hasChildNodes() ? Ext.MessageBox.WARNING : Ext.MessageBox.QUESTION,
            fn: function (button) {
                if (button == "ok") {
                    Ext.Ajax.request({
                        url: Routing.generate('opendxp_admin_user_delete'),
                        method: 'DELETE',
                        params: {
                            id: record.data.id
                        },
                        success: function() {
                            var userPanelKey = "user_" + record.data.id;
                            if (this.panels[userPanelKey]) {
                                this.panels[userPanelKey].panel.close();
                                delete this.panels[userPanelKey];
                            }
                            record.remove();
                        }.bind(this, tree, record)
                    });
                }
            }.bind(this)
        });
    },


    add: function (type, cloneRecord, selectedRecord) {
        if (cloneRecord) {
            rid = cloneRecord.data.id;
            parentNode = cloneRecord.parentNode;
        } else {
            rid = null;
            parentNode = selectedRecord;
        }
        var pid = parentNode.data.id;
        Ext.MessageBox.prompt(t('add'), t('enter_the_name_of_the_new_item'), function (button, value, object) {
            if(button=='ok' && value != ''){
                var params = {
                    parentId: pid,
                    type: type,
                    name: value,
                    active: true
                };
                if (rid !== null) {
                    params.rid = rid;
                }
                Ext.Ajax.request({
                    url: Routing.generate('opendxp_admin_user_add'),
                    method: 'POST',
                    params: params,
                    success: this.addComplete.bind(this, parentNode)
                });
            }
        }.bind(this));
    }
});


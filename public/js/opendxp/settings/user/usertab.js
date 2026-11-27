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


opendxp.registerNS("opendxp.settings.user.usertab");
/**
 * @private
 */
opendxp.settings.user.usertab = Class.create({

    initialize: function (parentPanel, id) {
        this.parentPanel = parentPanel;
        this.id = id;

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_user_get'),
            success: this.loadComplete.bind(this),
            params: {
                id: this.id
            }
        });
    },

    loadComplete: function (transport) {
        var response = Ext.decode(transport.responseText);
        if(response && response.success) {
            this.data = response;
            this.initPanel();
        }
    },

    initPanel: function () {

        this.panel = new Ext.TabPanel({
            title: this.data.user.name,
            closable: true,
            iconCls: "opendxp_icon_user",
            buttons: [{
                text: t("save"),
                handler: this.save.bind(this),
                iconCls: "opendxp_icon_accept"
            }]
        });

        this.panel.on("beforedestroy", function () {
            delete this.parentPanel.panels["user_" + this.id];
        }.bind(this));

        this.settings = new opendxp.settings.user.user.settings(this);
        this.workspaces = new opendxp.settings.user.workspaces(this);
        this.objectrelations = new opendxp.settings.user.user.objectrelations(this);
        this.keyBindings = new opendxp.settings.user.user.keyBindings(this);


        this.panel.add(this.settings.getPanel());
        this.panel.add(this.workspaces.getPanel());
        this.panel.add(this.objectrelations.getPanel());
        this.panel.add(this.keyBindings.getPanel());

        if(this.data.user.admin) {
            this.workspaces.disable();
        }

        this.parentPanel.getEditPanel().add(this.panel);
        this.parentPanel.getEditPanel().setActiveTab(this.panel);
        this.panel.setActiveTab(0);

    },

    activate: function () {
        this.parentPanel.getEditPanel().setActiveTab(this.panel);
    },

    save: function () {

        var active = null;
        var data = {
            id: this.id
        };
        var contentLanguages;

        try {
            var values = this.settings.getValues();
            if(values.hasOwnProperty("active")) {
                // only if "active" is available (if not available, the checkbox is disabled, eg. when modifying the logged in user)
                active = values["active"];
            }
            contentLanguages = values.contentLanguages;
            data.data = Ext.encode(values);
        } catch (e) {
            console.log(e);
        }

        try {
            data.workspaces = Ext.encode(this.workspaces.getValues());
        } catch (e2) {
            console.log(e2);
        }

        try {
            var keyBindingsFromForm = this.keyBindings.getValues();
            var userBindings = {};

            for (var key in keyBindingsFromForm) {
                if (keyBindingsFromForm.hasOwnProperty(key)) {
                    userBindings[key] = Ext.decode(keyBindingsFromForm[key]);
                }
            }

            this.data.user.keyBindings = Ext.encode(userBindings);
            if (this.id == opendxp.currentuser.id) {
                opendxp.globalmanager.get("user").keyBindings = this.data.user.keyBindings;
            }

            data.keyBindings = Ext.encode(keyBindingsFromForm);
        } catch (e3) {
            console.log(e3);
        }


        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_user_update'),
            method: "PUT",
            params: data,
            success: function (transport) {
                try{
                    var res = Ext.decode(transport.responseText);
                    if (res.success) {
                        opendxp.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                        if (this.id == opendxp.currentuser.id && contentLanguages) {
                                opendxp.settings.websiteLanguages = contentLanguages;
                        }

                        var tree = this.parentPanel.tree;
                        var store = tree.getStore();

                        var record = store.getById(this.id);
                        if (record) {
                            var view = tree.getView();
                            var nodeEl = Ext.fly(view.getNodeByRecord(record));
                            if (nodeEl) {
                                var nodeElInner = nodeEl.down(".x-grid-td");
                                if (nodeElInner) {
                                    if (active === true) {
                                        nodeElInner.removeCls("opendxp_unpublished");
                                    } else if (active === false) {
                                        nodeElInner.addCls("opendxp_unpublished");
                                    }
                                }
                            }
                        }


                    } else {
                        opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error",t(res.message));
                    }
                } catch(e){
                    opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }.bind(this)
        });
    }

});

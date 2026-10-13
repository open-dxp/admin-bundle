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

opendxp.registerNS("opendxp.notification.helper.x");

/**
 * @private
 */
opendxp.notification.helper.updateCount = function (count) {

    var currentValue = Ext.get("notification_value").getHtml();
    if(currentValue > count) {
        return;
    }

    if (count > 0) {
        Ext.get("notification_value").show();
        Ext.fly('notification_value').update(count);
    } else {
        Ext.get("notification_value").hide();
    }
};

/**
 * @private
 */
opendxp.notification.helper.incrementCount = function () {
    var value = Ext.get("notification_value").getHtml();
    if(value) {
        value++;
    } else {
        value = 1;
    }

    opendxp.notification.helper.updateCount(value);
};

/**
 * @private
 */
opendxp.notification.helper.showNotifications = function (notifications) {
    for (var i = 0; i < notifications.length; i++) {
        var row = notifications[i];
        var tools = [];
        tools.push({
            type: 'save',
            tooltip: t('mark_as_read'),
            handler: (function (row) {
                return function () {
                    this.up('window').close();
                    opendxp.notification.helper.markAsRead(row.id);
                }
            }(row))
        });
        if (row.linkedElementId) {
            tools.push({
                type: 'right',
                tooltip: t('open_linked_element'),
                handler: (function (row) {
                    return function () {
                        this.up('window').close();
                        opendxp.notification.helper.openLinkedElement(row);
                    }
                }(row))
            });
        }
        tools.push({
            type: 'maximize',
            tooltip: t('open'),
            handler: (function (row) {
                return function () {
                    this.up('window').close();
                    opendxp.notification.helper.openDetails(row.id);
                }
            }(row))
        });
        var notification = Ext.create('Ext.window.Toast', {
            iconCls: 'opendxp_icon_' + row.type,
            title: Ext.util.Format.htmlEncode(row.title),
            html: Ext.util.Format.htmlEncode(row.message),
            autoShow: true,
            width: 400,
            height: 150,
            closable: true,
            autoClose: false,
            tools: tools,
            align: "br"
        });
        notification.show();
    }
};

/**
 * @private
 */
opendxp.notification.helper.markAsRead = function (id, callback) {
    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_notification_markasread', {id: id}),
        method: 'PUT',
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};

/**
 * @private
 */
opendxp.notification.helper.openLinkedElement = function (row) {
    if ('document' == row['linkedElementType']) {
        opendxp.helpers.openElement(row['linkedElementId'], 'document');
    } else if ('asset' == row['linkedElementType']) {
        opendxp.helpers.openElement(row['linkedElementId'], 'asset');
    } else if ('object' == row['linkedElementType']) {
        opendxp.helpers.openElement(row['linkedElementId'], 'object');
    }
};

/**
 * @private
 */
opendxp.notification.helper.openDetails = function (id, callback) {
    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_notification_find', {id: id}),
        success: function (response) {
            response = Ext.decode(response.responseText);
            if (!response.success) {
                Ext.MessageBox.alert(t("error"), t("element_not_found"));
                return;
            }
            opendxp.notification.helper.openDetailsWindow(
                response.data.id,
                response.data.title,
                response.data.message,
                response.data.type,
                callback
            );
        }
    });
};

/**
 * @private
 */
opendxp.notification.helper.openDetailsWindow = function (id, title, message, type, callback) {
    var notification = new Ext.Window({
        modal: true,
        iconCls: 'opendxp_icon_' + type,
        title: Ext.util.Format.htmlEncode(title),
        html: Ext.util.Format.htmlEncode(message),
        autoShow: true,
        width: 700,
        height: 350,
        scrollable: true,
        closable: true,
        maximizable: true,
        bodyPadding: "10px",
        autoClose: false,
        listeners: {
            afterrender: function () {
                opendxp.notification.helper.markAsRead(id, callback);
            }
        }
    });
    notification.show(document);
    notification.focus();
};

/**
 * @private
 */
opendxp.notification.helper.delete = function (id, callback) {
    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_notification_delete', {id: id}),
        method: 'DELETE',
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};

/**
 * @private
 */
opendxp.notification.helper.deleteAll = function (callback) {
    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_notification_deleteall'),
        method: 'DELETE',
        success: function (response) {
            if (callback) {
                callback();
            }
        }
    });
};

/**
 * @private
 */
opendxp.notification.helper.setLastUpdateTimestamp = function () {
    this.lastUpdateTimestamp = parseInt(new Date().getTime() / 1000, 10);
};
opendxp.notification.helper.setLastUpdateTimestamp();

/**
 * @private
 */
opendxp.notification.helper.updateFromServer = function () {
    var user = opendxp.globalmanager.get("user");
    if (!document.hidden && user.isAllowed("notifications")) {
        opendxp.backgroundConnection.request({
            url: Routing.generate('opendxp_admin_notification_findlastunread'),
            params: {
                lastUpdate: this.lastUpdateTimestamp
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);
                opendxp.notification.helper.updateCount(data.unread);
                opendxp.notification.helper.showNotifications(data.data);
            }
        });

        opendxp.notification.helper.setLastUpdateTimestamp();
    }
};

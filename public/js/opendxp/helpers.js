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

/*global localStorage */
opendxp.registerNS("opendxp.helpers.x");

opendxp.helpers.sanitizeEmail = function (email) {
    return email.replace(/[^a-zA-Z0-9_\-@.+]/g,'');
};

opendxp.helpers.sanitizeUrlSlug = function (slug) {
    return slug.replace(/[^a-z0-9-_+/]/gi, '');
};

opendxp.helpers.registerKeyBindings = function (bindEl, ExtJS) {

    if (!ExtJS) {
        ExtJS = Ext;
    }

    var user = opendxp.globalmanager.get("user");
    var bindings = [];

    // firing event to enable bundles/extensions to add key bindings
    document.dispatchEvent(new CustomEvent(opendxp.events.preRegisterKeyBindings));

    var decodedKeyBindings = Ext.decode(user.keyBindings);
    if (decodedKeyBindings) {
        for (var i = 0; i < decodedKeyBindings.length; i++) {
            var item = decodedKeyBindings[i];
            if (item == null) {
                continue;
            }

            if (!item.key) {
                continue;
            }
            var action = item.action;
            var handler = opendxp.helpers.keyBindingMapping[action];
            if (handler) {
                var binding = item;
                item["fn"] = handler;
                bindings.push(binding);
            }
        }
    }

    opendxp.keymap = new ExtJS.util.KeyMap({
        target: bindEl,
        binding: bindings
    });
};

opendxp.helpers.openClassEditor = function () {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("classes")) {
        var toolbar = opendxp.globalmanager.get("layout_toolbar");
        toolbar.editClasses();
    }
};

opendxp.helpers.openWelcomePage = function (keyCode, e) {

    if (e["stopEvent"]) {
        e.stopEvent();
    }

    try {
        opendxp.globalmanager.get("layout_portal_welcome").activate();
    }
    catch (e) {
        opendxp.globalmanager.add("layout_portal_welcome", new opendxp.layout.portal());
    }
};

opendxp.helpers.openAsset = function (id, type, options) {

    if (opendxp.globalmanager.exists("asset_" + id) == false) {

        if (!opendxp.asset[type]) {
            opendxp.globalmanager.add("asset_" + id, new opendxp.asset.unknown(id, options));
        }
        else {
            opendxp.globalmanager.add("asset_" + id, new opendxp.asset[type](id, options));
        }

        opendxp.helpers.rememberOpenTab("asset_" + id + "_" + type);

        if (options != undefined) {
            if (options.ignoreForHistory) {
                var element = opendxp.globalmanager.get("asset_" + id);
                element.setAddToHistory(false);
            }
        }

    }
    else {
        opendxp.globalmanager.get("asset_" + id).activate();
    }
};

opendxp.helpers.closeAsset = function (id) {

    try {
        var tabId = "asset_" + id;
        var panel = Ext.getCmp(tabId);
        if (panel) {
            panel.close();
        }

        opendxp.helpers.removeTreeNodeLoadingIndicator("asset", id);
        opendxp.globalmanager.remove("asset_" + id);
    } catch (e) {
        console.log(e);
    }
};

opendxp.helpers.openDocument = function (id, type, options) {
    if (opendxp.globalmanager.exists("document_" + id) == false) {
        if (opendxp.document[type]) {
            opendxp.globalmanager.add("document_" + id, new opendxp.document[type](id, options));
            opendxp.helpers.rememberOpenTab("document_" + id + "_" + type);

            if (options !== undefined) {
                if (options.ignoreForHistory) {
                    var element = opendxp.globalmanager.get("document_" + id);
                    element.setAddToHistory(false);
                }
            }
        }
    }
    else {
        opendxp.globalmanager.get("document_" + id).activate();
    }
};

opendxp.helpers.closeDocument = function (id) {
    try {
        var tabId = "document_" + id;
        var panel = Ext.getCmp(tabId);
        if (panel) {
            panel.close();
        }

        opendxp.helpers.removeTreeNodeLoadingIndicator("document", id);
        opendxp.globalmanager.remove("document_" + id);
    } catch (e) {
        console.log(e);
    }

};

opendxp.helpers.openObject = function (id, type, options) {
    if (opendxp.globalmanager.exists("object_" + id) == false) {

        if (type != "folder" && type != "variant" && type != "object") {
            type = "object";
        }

        opendxp.globalmanager.add("object_" + id, new opendxp.object[type](id, options));
        opendxp.helpers.rememberOpenTab("object_" + id + "_" + type);

        if (options !== undefined) {
            if (options.ignoreForHistory) {
                var element = opendxp.globalmanager.get("object_" + id);
                element.setAddToHistory(false);
            }
        }
    }
    else {
        var tab = opendxp.globalmanager.get("object_" + id);
        tab.activate();
    }
};

opendxp.helpers.closeObject = function (id) {
    try {
        var tabId = "object_" + id;
        var panel = Ext.getCmp(tabId);
        if (panel) {
            panel.close();
        }

        opendxp.helpers.removeTreeNodeLoadingIndicator("object", id);
        opendxp.globalmanager.remove("object_" + id);
    } catch (e) {
        console.log(e);
    }
};

opendxp.helpers.updateTreeElementStyle = function (type, id, treeData) {
    if (treeData) {

        var key = type + "_" + id;
        if (opendxp.globalmanager.exists(key)) {
            var editMask = opendxp.globalmanager.get(key);
            if (editMask.tab) {
                if (typeof treeData.iconCls !== "undefined") {
                    editMask.tab.setIconCls(treeData.iconCls);
                }

                if (typeof treeData.icon !== "undefined") {
                    editMask.tab.setIcon(treeData.icon);
                }
            }
        }

        var treeNames = opendxp.elementservice.getElementTreeNames(type);

        for (var index = 0; index < treeNames.length; index++) {
            var treeName = treeNames[index];
            var tree = opendxp.globalmanager.get(treeName);
            if (!tree) {
                continue;
            }
            tree = tree.tree;
            var store = tree.getStore();
            var record = store.getById(id);
            if (record) {
                if (typeof treeData.icon !== "undefined") {
                    record.set("icon", treeData.icon);
                }

                if (typeof treeData.cls !== "undefined") {
                    record.set("cls", treeData.cls);
                }

                if (typeof treeData.iconCls !== "undefined") {
                    record.set("iconCls", treeData.iconCls);
                }

                if (typeof treeData.qtipCfg !== "undefined") {
                    record.set("qtipCfg", treeData.qtipCfg);
                }

                if (typeof treeData.key !== "undefined") {
                    record.set("key", treeData.key);
                }

                if (typeof treeData.text !== "undefined") {
                    record.set("text", treeData.text);
                }
            }
        }
    }
};

opendxp.helpers.getHistory = function () {
    var history = localStorage.getItem("opendxp_element_history");
    if (!history) {
        history = [];
    } else {
        history = JSON.parse(history);
    }
    return history;
};

opendxp.helpers.recordElement = function (id, type, name) {

    var history = opendxp.helpers.getHistory();

    var newDate = new Date();

    for (var i = history.length - 1; i >= 0; i--) {
        var item = history[i];
        if (item.type == type && item.id == id) {
            history.splice(i, 1);
        }
    }


    var historyItem = {
        id: id,
        type: type,
        name: name,
        time: newDate.getTime()
    };
    history.unshift(historyItem);

    history = history.slice(0, 30);

    var json = JSON.stringify(history);
    localStorage.setItem("opendxp_element_history", json);

    try {
        var historyPanel = opendxp.globalmanager.get("element_history");
        if (historyPanel) {
            var thePair = {
                "id": id,
                "type": type,
                "name": name,
                "time": newDate
            };

            var storeCount = historyPanel.store.getCount();
            for (var i = storeCount - 1; i >= 0; i--) {

                var record = historyPanel.store.getAt(i);
                var data = record.data;
                if (i > 100 || (data.id == id && data.type == type)) {
                    historyPanel.store.remove(record);
                }
            }

            historyPanel.store.insert(0, thePair);
            historyPanel.resultpanel.getView().refresh();
        }
    }
    catch (e) {
        console.log(e);
    }

};

opendxp.helpers.openElement = function (idOrPath, type, subtype) {
    if (typeof subtype != "undefined" && subtype !== null) {
        if (type == "document") {
            opendxp.helpers.openDocument(idOrPath, subtype);
        }
        else if (type == "asset") {
            opendxp.helpers.openAsset(idOrPath, subtype);
        }
        else if (type == "object") {
            opendxp.helpers.openObject(idOrPath, subtype);
        }
    } else {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_element_getsubtype'),
            params: {
                id: idOrPath,
                type: type
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                if (res.success) {
                    opendxp.helpers.openElement(res.id, res.type, res.subtype);
                } else {
                    Ext.MessageBox.alert(t("error"), t("element_not_found"));
                }
            }
        });
    }
};

opendxp.helpers.closeElement = function (id, type) {
    if (type == "document") {
        opendxp.helpers.closeDocument(id);
    }
    else if (type == "asset") {
        opendxp.helpers.closeAsset(id);
    }
    else if (type == "object") {
        opendxp.helpers.closeObject(id);
    }
};

opendxp.helpers.getElementTypeByObject = function (object) {
    var type = null;
    if (object instanceof opendxp.document.document) {
        type = "document";
    } else if (object instanceof opendxp.asset.asset) {
        type = "asset";
    } else if (object instanceof opendxp.object.abstract) {
        type = "object";
    }
    return type;
};

opendxp.helpers.getTreeNodeLoadingIndicatorElements = function (type, id) {
    // display loading indicator on treenode
    var elements = [];
    var treeNames = opendxp.elementservice.getElementTreeNames(type);

    for (index = 0; index < treeNames.length; index++) {
        var treeName = treeNames[index];
        var tree = opendxp.globalmanager.get(treeName);
        if (!tree) {
            continue;
        }
        tree = tree.tree;

        try {
            var store = tree.getStore();
            var node = store.getNodeById(id);
            if (node) {
                var view = tree.getView();
                var nodeEl = Ext.fly(view.getNodeByRecord(node));
                var icon = nodeEl.query(".x-tree-icon")[0];

                var iconEl = Ext.get(icon);
                if (iconEl) {
                    elements.push(iconEl);
                }
            }
        } catch (e) {
            //console.log(e);
        }
    }
    return elements;
};

opendxp.helpers.treeNodeLoadingIndicatorTimeouts = {};

opendxp.helpers.addTreeNodeLoadingIndicator = function (type, id, disableExpander) {

    if(disableExpander !== false) {
        disableExpander = true;
    }

    opendxp.helpers.treeNodeLoadingIndicatorTimeouts[type + id] = window.setTimeout(function () {
        // display loading indicator on treenode
        var iconEls = opendxp.helpers.getTreeNodeLoadingIndicatorElements(type, id);
        for (var index = 0; index < iconEls.length; index++) {
            var iconEl = iconEls[index];
            if (iconEl) {
                iconEl.addCls("opendxp_tree_node_loading_indicator");
                if(disableExpander) {
                    iconEl.up('.x-grid-cell').addCls('opendxp_treenode_hide_plus_button');
                }
            }
        }
    }, 200);
};

opendxp.helpers.removeTreeNodeLoadingIndicator = function (type, id) {

    clearTimeout(opendxp.helpers.treeNodeLoadingIndicatorTimeouts[type + id]);

    // display loading indicator on treenode
    var iconEls = opendxp.helpers.getTreeNodeLoadingIndicatorElements(type, id);
    for (var index = 0; index < iconEls.length; index++) {
        var iconEl = iconEls[index];
        if (iconEl) {
            iconEl.removeCls("opendxp_tree_node_loading_indicator");
            iconEl.up('.x-grid-cell').removeCls('opendxp_treenode_hide_plus_button');
        }
    }
};

opendxp.helpers.hasTreeNodeLoadingIndicator = function (type, id) {
    var iconEls = opendxp.helpers.getTreeNodeLoadingIndicatorElements(type, id);
    for (var index = 0; index < iconEls.length; index++) {
        var iconEl = iconEls[index];
        if (iconEl) {
            return iconEl.hasCls("opendxp_tree_node_loading_indicator");
        }
    }

    return false;
};


opendxp.helpers.openSeemode = function () {
    if (opendxp.globalmanager.exists("opendxp_seemode")) {
        opendxp.globalmanager.get("opendxp_seemode").start();
    }
    else {
        opendxp.globalmanager.add("opendxp_seemode", new opendxp.document.seemode());
    }
};

opendxp.helpers.isValidFilename = function (value) {
    var result = value.match(/[a-zA-Z0-9_.\-~]+/);
    if (result == value) {
        // key must be at least one character, an maximum 30 characters
        if (value.length < 1 && value.length > 30) {
            return false;
        }
        return true;
    }
    return false;
};


opendxp.helpers.getValidFilenameCache = {};

opendxp.helpers.getValidFilename = function (value, type) {

    value = value.trim();

    if (opendxp.helpers.getValidFilenameCache[value + type]) {
        return opendxp.helpers.getValidFilenameCache[value + type];
    }

    var response = Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_misc_getvalidfilename'),
        async: false,
        params: {
            value: value,
            type: type
        }
    });

    var res = Ext.decode(response.responseText);
    opendxp.helpers.getValidFilenameCache[value + type] = res["filename"];
    return res["filename"];
};

opendxp.helpers.showPrettyError = function (type, title, text, errorText, stack, code, hideDelay) {
    opendxp.helpers.showNotification(title, text, "error", errorText, hideDelay);
};

opendxp.helpers.showNotification = function (title, text, type, detailText, hideDelay) {
    // icon types: info,error,success
    if (type === "error") {

        if (detailText) {
            detailText =
                '<pre style="font-size:11px;word-wrap: break-word;">'
                    + strip_tags(detailText) +
                "</pre>";
        }

        var errWin = new Ext.Window({
            modal: true,
            iconCls: "opendxp_icon_error",
            title: title,
            width: 700,
            maxHeight: 500,
            html: text,
            autoScroll: true,
            bodyStyle: "padding: 10px;",
            buttonAlign: "center",
            shadow: false,
            closable: false,
            buttons: [{
                text: t("details"),
                hidden: !detailText,
                handler: function () {
                    errWin.close();

                    var detailWindow = new Ext.Window({
                        modal: true,
                        title: t('details'),
                        width: 1000,
                        height: '95%',
                        html: detailText,
                        autoScroll: true,
                        bodyStyle: "padding: 10px;",
                        buttonAlign: "center",
                        shadow: false,
                        closable: true,
                        buttons: [{
                            text: t("OK"),
                            handler: function () {
                                detailWindow.close();
                            }
                        }]
                    });
                    detailWindow.show();
                }
            }, {
                text: t("OK"),
                handler: function () {
                    errWin.close();
                }
            }]
        });
        errWin.show();
    } else {
        // Avoid overlapping any footer toolbar buttons
        // Find current active tab to find its footer if there is one
        let paddingY = 10;
        let tabsBody = document.getElementById('opendxp_panel_tabs-body');
        let activeTab = tabsBody.querySelector(':scope > [aria-expanded="true"]');
        if (activeTab) {
            let footerToolbar = activeTab.querySelector(':scope .x-toolbar-footer');
            if (footerToolbar) {
                paddingY += footerToolbar.scrollHeight;
            }
        }

        var notification = Ext.create('Ext.window.Toast', {
            iconCls: 'opendxp_icon_' + type,
            title: title,
            html: text,
            autoShow: true,
            width: 'auto',
            maxWidth: 350,
            closeable: true,
            align: "br",
            anchor: Ext.get(tabsBody),
            paddingX: 5,
            paddingY: paddingY
        });
        notification.show(document);
    }

};


opendxp.helpers.rename = function (keyCode, e) {

    e.stopEvent();

    var tabpanel = Ext.getCmp("opendxp_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        var el = activeTab.initialConfig;
        if (el.document && el.document.rename) {
            el.document.rename();

        }
        else if (el.object && el.object.rename) {
            el.object.rename();

        }
        else if (el.asset && el.asset.rename) {
            el.asset.rename();
        }
    }
};

opendxp.helpers.togglePublish = function (publish, keyCode, e) {

    e.stopEvent();

    var tabpanel = Ext.getCmp("opendxp_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        var el = activeTab.initialConfig;
        if (el.document) {
            if (publish) {
                el.document.publish();
            } else {
                el.document.unpublish();
            }
        }
        else if (el.object) {
            if (publish) {
                el.object.publish();
            } else {
                el.object.unpublish();
            }
        }
        else if (el.asset) {
            el.asset.save();
        }
    }
};


opendxp.helpers.handleCtrlS = function (keyCode, e) {

    e.stopEvent();

    var tabpanel = Ext.getCmp("opendxp_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        var el = activeTab.initialConfig;
        if (el.document) {
            if (el.document.data.published) {
                el.document.publish();
            } else {
                el.document.save('version');
            }
        }
        else if (el.object) {
            if (el.object.data.general.published) {
                el.object.publish();
            } else {
                el.object.save('version');
            }
        }
        else if (el.asset) {
            el.asset.save();
        }
    }
};

opendxp.helpers.showMetaInfo = function (keyCode, e) {

    e.stopEvent();

    var tabpanel = Ext.getCmp("opendxp_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        if (activeTab.initialConfig.document) {
            activeTab.initialConfig.document.showMetaInfo();
        } else if (activeTab.initialConfig.asset) {
            activeTab.initialConfig.asset.showMetaInfo();
        } else if (activeTab.initialConfig.object) {
            activeTab.initialConfig.object.showMetaInfo();
        }
    }
};

opendxp.helpers.openInTree = function (keyCode, e) {

    e.stopEvent();

    var tabpanel = Ext.getCmp("opendxp_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        if (activeTab.initialConfig.document || activeTab.initialConfig.asset || activeTab.initialConfig.object) {
            var tabId = activeTab.id;
            var parts = tabId.split("_");
            var type = parts[0];
            var elementId = parts[1];
            opendxp.treenodelocator.showInTree(elementId, type);

        }
    }
};


opendxp.helpers.handleF5 = function (keyCode, e) {

    e.stopEvent();

    var tabpanel = Ext.getCmp("opendxp_panel_tabs");
    var activeTab = tabpanel.getActiveTab();

    if (activeTab) {
        // for document
        if (activeTab.initialConfig.document) {
            activeTab.initialConfig.document.reload();
            return;
        }
        else if (activeTab.initialConfig.object) {
            activeTab.initialConfig.object.reload();
            return;
        }
    }

    var date = new Date();
    location.href = Routing.generate('opendxp_admin_index', {'_dc': date.getTime()});
};

opendxp.helpers.lockManager = function (cid, ctype, csubtype, data) {

    var lockDate = new Date(data.editlock.date * 1000);
    var lockDetails = "<br /><br />";
    lockDetails += "<b>" + t("path") + ": <i>" + data.editlock.cpath + "</i></b><br />";
    lockDetails += "<b>" + t("type") + ": </b>" + t(ctype) + "<br />";
    if (data.editlock.user) {
        lockDetails += "<b>" + t("user") + ":</b> " + data.editlock.user.name + "<br />";
    }
    lockDetails += "<b>" + t("since") + ": </b>" + Ext.util.Format.date(lockDate, "Y-m-d H:i");
    lockDetails += "<br /><br />" + t("element_lock_question");

    Ext.MessageBox.confirm(t("element_is_locked"), t("element_lock_message") + lockDetails,
        function (lock, buttonValue) {
            if (buttonValue == "yes") {
                Ext.Ajax.request({
                    url: Routing.generate('opendxp_admin_element_unlockelement'),
                    method: 'PUT',
                    params: {
                        id: lock[0],
                        type: lock[1]
                    },
                    success: function () {
                        opendxp.helpers.openElement(lock[0], lock[1], lock[2]);
                    }
                });
            }
        }.bind(this, arguments));
};


opendxp.helpers.closeAllUnmodified = function () {
    var unmodifiedElements = [];

    var tabs = Ext.getCmp("opendxp_panel_tabs").items;
    if (tabs.getCount() > 0) {
        tabs.each(function (item, index, length) {
            if (item.title.indexOf("*") > -1) {
                unmodifiedElements.push(item);
            }
        });
    }


    opendxp.helpers.closeAllElements(unmodifiedElements);
};

opendxp.helpers.closeAllElements = function (except, tabPanel) {

    var exceptions = [];
    if (except instanceof Ext.Panel) {
        exceptions.push(except);
    } else if (except instanceof Array) {
        exceptions = except;
    }

    if (typeof tabPanel == "undefined") {
        tabPanel = Ext.getCmp("opendxp_panel_tabs");
    }

    var tabs = tabPanel.items;
    if (tabs.getCount() > 0) {
        tabs.each(function (item, index, length) {
            window.setTimeout(function () {
                if (!in_array(item, exceptions)) {
                    item.close();
                }
            }, 100 * index);
        });
    }
};


opendxp.helpers.loadingShow = function () {
    opendxp.globalmanager.get("loadingmask").show();
};

opendxp.helpers.loadingHide = function () {
    opendxp.globalmanager.get("loadingmask").hide();
};

opendxp.helpers.itemselector = function (multiselect, callback, restrictions, config) {
    opendxp.globalmanager.get('searchImplementationRegistry').openItemSelector(
        multiselect,
        callback,
        restrictions,
        config
    );
};

opendxp.helpers.hasSearchImplementation = function () {
    return opendxp.globalmanager.get('searchImplementationRegistry').hasImplementation();
}

opendxp.helpers.getObjectRelationInlineSearchRoute = function () {
    if(opendxp.helpers.hasSearchImplementation()) {
        return opendxp.globalmanager.get('searchImplementationRegistry').getObjectRelationInlineSearchRoute();
    }

    return null;
}

opendxp.helpers.activateMaintenance = function () {

    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_misc_maintenance', {activate: true}),
        method: "POST"
    });

    var button = Ext.get("opendxp_menu_maintenance");
    if (!button.isVisible()) {
        opendxp.helpers.showMaintenanceDisableButton();
    }
};

opendxp.helpers.deactivateMaintenance = function () {

    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_misc_maintenance', {deactivate: true}),
        method: "POST"
    });

    var button = Ext.get("opendxp_menu_maintenance");
    button.setStyle("display", "none");
};

opendxp.helpers.showMaintenanceDisableButton = function () {
    var button = Ext.get("opendxp_menu_maintenance");
    button.show();
    button.clearListeners();
    button.on("click", opendxp.helpers.deactivateMaintenance);
};

opendxp.helpers.download = function (url) {
    opendxp.settings.showCloseConfirmation = false;
    window.setTimeout(function () {
        opendxp.settings.showCloseConfirmation = true;
    }, 1000);

    let iframe = document.getElementById('download_helper_iframe');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.style.display = 'none';
        iframe.setAttribute('id', 'download_helper_iframe');
        document.body.appendChild(iframe);
    }
    iframe.src = url;

    iframe.onload = function() {
        // if avoids infinity loop, which is caused by setting the src in the load function
        if (iframe.src !== 'about:blank') {
            const title = iframe.contentDocument.title;
            opendxp.helpers.showNotification(t('error'), title, 'error');
            iframe.src = 'about:blank'; //clear iframe because otherwise the error will stay in the dom
        }
    }
};

opendxp.helpers.getFileExtension = function (filename) {
    var extensionP = filename.split("\.");
    return extensionP[extensionP.length - 1];
};


opendxp.helpers.getOpenTab = function () {
    var openTabs = localStorage.getItem("opendxp_opentabs");
    if (!openTabs) {
        openTabs = [];
    } else {
        // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
        openTabs = JSON.parse(openTabs);
    }

    return openTabs;
};

opendxp.helpers.clearOpenTab = function () {
    localStorage.setItem("opendxp_opentabs", JSON.stringify([]));
};

opendxp.helpers.rememberOpenTab = function (item, forceOpenTab) {
    var openTabs = opendxp.helpers.getOpenTab();

    if (!in_array(item, openTabs)) {
        openTabs.push(item);
    }

    // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
    localStorage.setItem("opendxp_opentabs", JSON.stringify(openTabs));
    if (forceOpenTab) {
        localStorage.setItem("opendxp_opentabs_forceopenonce", true);
    }
};

opendxp.helpers.forgetOpenTab = function (item) {

    var openTabs = opendxp.helpers.getOpenTab();

    if (in_array(item, openTabs)) {
        var pos = array_search(item, openTabs);
        openTabs.splice(pos, 1);
    }

    // using native JSON functionalities here because of /admin/login/deeplink -> No ExtJS should be loaded
    localStorage.setItem("opendxp_opentabs", JSON.stringify(openTabs));
};

opendxp.helpers.forceOpenMemorizedTabsOnce = function () {
    if (localStorage.getItem("opendxp_opentabs_forceopenonce")) {
        localStorage.removeItem("opendxp_opentabs_forceopenonce");
        return true;
    }
    return false;
};

opendxp.helpers.openMemorizedTabs = function () {
    var openTabs = opendxp.helpers.getOpenTab();

    // limit to the latest 10
    openTabs.reverse();
    openTabs.splice(10, 1000);
    openTabs.reverse();

    var openedTabs = [];

    for (var i = 0; i < openTabs.length; i++) {
        if (!empty(openTabs[i])) {
            if (!in_array(openTabs[i], openedTabs)) {
                var parts = openTabs[i].split("_");
                window.setTimeout(function (parts) {
                    if (parts[1] && parts[2]) {
                        if (parts[0] == "asset") {
                            opendxp.helpers.openAsset(parts[1], parts[2], {
                                ignoreForHistory: true,
                                ignoreNotFoundError: true
                            });
                        } else if (parts[0] == "document") {
                            opendxp.helpers.openDocument(parts[1], parts[2], {
                                ignoreForHistory: true,
                                ignoreNotFoundError: true
                            });
                        } else if (parts[0] == "object") {
                            opendxp.helpers.openObject(parts[1], parts[2], {
                                ignoreForHistory: true,
                                ignoreNotFoundError: true
                            });
                        }
                    }
                }.bind(this, parts), 200);
            }
            openedTabs.push(openTabs[i]);
        }
    }
};

opendxp.helpers.assetSingleUploadDialog = function (parent, parentType, success, failure, context, uploadAssetType) {

    var params = {};
    params['parent' + ucfirst(parentType)] = parent;

    var url = Routing.generate('opendxp_admin_asset_addassetcompatibility', params);
    if (context) {
        url += "&context=" + Ext.encode(context);
    }

    if(uploadAssetType) {
        url += "&uploadAssetType=" + uploadAssetType;
    }

    opendxp.helpers.uploadDialog(url, 'Filedata', success, failure);
};

opendxp.helpers.uploadDialog = function (url, filename, success, failure, description) {

    if (typeof success != "function") {
        success = function () {
        };
    }

    if (typeof failure != "function") {
        failure = function () {
        };
    }

    if (typeof filename != "string") {
        filename = "Filedata";
    }

    if (empty(filename)) {
        filename = "Filedata";
    }

    const uploadWindowCompatible = new Ext.Window({
        autoHeight: true,
        title: t('upload'),
        closeAction: 'close',
        width: 400,
        modal: true
    });

    const items = [];

    if (description) {
        items.push({
            xtype: 'displayfield',
            value: description
        });
    }

    items.push({
        xtype: 'fileuploadfield',
        emptyText: t("select_files"),
        fieldLabel: t("file"),
        width: 470,
        name: filename+'[]',
        buttonText: "",
        buttonConfig: {
            iconCls: 'opendxp_icon_upload'
        },
        listeners: {
            change: function (fileUploadField) {
                let activeUploads = 0;
                const filesCount = fileUploadField.fileInputEl.dom.files.length;

                const win = new Ext.Window({
                    items: [],
                    modal: true,
                    closable: false,
                    bodyStyle: "padding:10px;",
                    width: 500,
                    autoHeight: true,
                    autoScroll: true
                });

                const finishedErrorHandler = function (pbar) {
                    activeUploads--;
                    win.remove(pbar);

                    if (activeUploads < 1) {
                        win.close();
                    }
                }.bind(this);

                Ext.each(fileUploadField.fileInputEl.dom.files, function (file) {
                    if (file.size > opendxp.settings["upload_max_filesize"]) {
                        opendxp.helpers.showNotification(t("error"), t("file_is_bigger_that_upload_limit") + " " + file.name, "error");
                        return;
                    }

                    const pbar = new Ext.ProgressBar({
                        width: 465,
                        text: file.name,
                        style: "margin-bottom: 5px"
                    });

                    if (!win.isVisible()){
                        win.show();
                    }

                    win.add(pbar);
                    win.updateLayout();

                    activeUploads++;
                    const percentComplete = activeUploads / filesCount;
                    let progressText = file.name + " ( " + Math.floor(percentComplete * 100) + "% )";
                    if (percentComplete == 1) {
                        progressText = file.name + " " + t("please_wait");
                    }

                    pbar.updateProgress(percentComplete, progressText);

                    const data = new FormData();
                    data.append(filename, file);
                    data.append("filename", file.name);
                    data.append("csrfToken", opendxp.settings['csrfToken']);

                    const request = new XMLHttpRequest();
                    const res = {
                        'response': request
                    };

                    const successWrapper = function (ev) {
                        let data = {success: false};
                        try {
                            data = JSON.parse(request.responseText);
                        } catch (e) {}
                        if (ev.currentTarget.status < 400 && data.success === true) {
                            success(res);
                            if (activeUploads == filesCount) {
                                win.close();
                                uploadWindowCompatible.close();
                            }
                        } else {
                            failure(res);
                            finishedErrorHandler(pbar);
                        }
                    };

                    const errorWrapper = function (ev) {
                        failure(res);
                        finishedErrorHandler(pbar);
                    };

                    request.addEventListener("load", successWrapper, false);
                    request.addEventListener("error", errorWrapper, false);
                    request.addEventListener("abort", errorWrapper, false);
                    request.open('POST', url);
                    request.send(data);

                });
            },
            afterrender: function(cmp){
                cmp.fileInputEl.set({
                    multiple:'multiple'
                });
            }
        }
    });

    const uploadForm = new Ext.form.FormPanel({
        fileUpload: true,
        width: 500,
        bodyStyle: 'padding: 10px;',
        items: items
    });

    uploadWindowCompatible.add(uploadForm);
    uploadWindowCompatible.show();
    uploadWindowCompatible.setWidth(501);
    uploadWindowCompatible.updateLayout();
};


opendxp.helpers.getClassForIcon = function (icon) {

    var styleContainerId = "opendxp_dynamic_class_for_icon";
    var styleContainer = Ext.get(styleContainerId);
    if (!styleContainer) {
        styleContainer = Ext.getBody().insertHtml("beforeEnd", '<style type="text/css" id="' + styleContainerId
            + '"></style>', true);
    }

    var content = styleContainer.dom.innerHTML;
    var classname = "opendxp_dynamic_class_for_icon_" + uniqid();
    content += ("." + classname + " { background: url(" + icon + ") left center no-repeat !important; background-size: contain !important; }\n");
    styleContainer.dom.innerHTML = content;

    return classname;
};

opendxp.helpers.searchAction = function (type) {
    opendxp.helpers.itemselector(false, function (selection) {
            opendxp.helpers.openElement(selection.id, selection.type, selection.subtype);
        }, {type: [type]},
        {
            asTab: true,
            context: {
                scope: "globalSearch"
            }
        });
};


opendxp.helpers.openElementByIdDialog = function (type, keyCode, e) {

    if (e["stopEvent"]) {
        e.stopEvent();
    }

    Ext.MessageBox.prompt(t('open_' + type + '_by_id'), t('please_enter_the_id_of_the_' + type),
        function (button, value, object) {
            if (button == "ok" && !Ext.isEmpty(value)) {
                opendxp.helpers.openElement(value, type);
            }
        });
};

opendxp.helpers.openDocumentByPath = function (path) {
    opendxp.helpers.openElement(path, "document");
};

opendxp.helpers.sanitizeAllowedTypes = function (data, name) {
    if (data[name]) {
        var newList = [];
        for (var i = 0; i < data[name].length; i++) {
            newList.push(data[name][i][name]);
        }
        data[name] = newList;
    }
};

opendxp.helpers.treeNodeThumbnailTimeout = null;
opendxp.helpers.treeNodeThumbnailHideTimeout = null;
opendxp.helpers.treeNodeThumbnailLastClose = 0;

opendxp.helpers.treeNodeThumbnailPreview = function (treeView, record, item, index, e, eOpts) {

    if (typeof record.data["thumbnail"] != "undefined") {

        // only display thumbnails when dnd is not active
        if (Ext.dd.DragDropMgr.dragCurrent) {
            return;
        }

        var thumbnail = record.data["thumbnail"];

        if (thumbnail) {

            if (opendxp.helpers.treeNodeThumbnailHideTimeout) {
                clearTimeout(opendxp.helpers.treeNodeThumbnailHideTimeout);
                opendxp.helpers.treeNodeThumbnailHideTimeout = null;
            }

            var treeEl = Ext.get("opendxp_panel_tree_" + this.position);
            var position = treeEl.getOffsetsTo(Ext.getBody());
            position = position[0];

            if (this.position == "right") {
                position = position - 420;
            } else {
                position = treeEl.getWidth() + position;
            }

            var container = Ext.get("opendxp_tree_preview");
            if (!container) {
                container = Ext.getBody().insertHtml("beforeEnd", '<div id="opendxp_tree_preview" class="hidden"><div id="opendxp_tree_preview_thumb"></div></div>');
                container = Ext.get(container);
            }

            var triggerTime = (new Date()).getTime();
            var thumbContainer = Ext.get("opendxp_tree_preview_thumb");
            thumbContainer.update('');

            opendxp.helpers.treeNodeThumbnailTimeout = window.setTimeout(function () {
                let img = document.createElement("img");
                img.src = thumbnail;
                img.addEventListener('load', function (ev) {

                    if(triggerTime > opendxp.helpers.treeNodeThumbnailLastClose) {
                        thumbContainer.addCls('complete');
                        container.removeCls("hidden");
                    }
                });

                img.addEventListener('error', function (ev) {
                    container.addCls("hidden");
                });

                container.applyStyles("left: " + position + "px");
                thumbContainer.dom.appendChild(img);

            }, 300);
        }
    }
};

opendxp.helpers.treeNodeThumbnailPreviewHide = function () {

    if (opendxp.helpers.treeNodeThumbnailTimeout) {
        clearTimeout(opendxp.helpers.treeNodeThumbnailTimeout);
        opendxp.helpers.treeNodeThumbnailTimeout = null;
    }

    let container = Ext.get("opendxp_tree_preview");
    if (container) {
        opendxp.helpers.treeNodeThumbnailLastClose = (new Date()).getTime();
        opendxp.helpers.treeNodeThumbnailHideTimeout = window.setTimeout(function () {
            container.addCls("hidden");
        }, 50);
    }
};

opendxp.helpers.showUser = function (specificUser) {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("users")) {
        var panel = null;
        try {
            panel = opendxp.globalmanager.get("users");
            panel.activate();
        }
        catch (e) {
            panel = new opendxp.settings.user.panel();
            opendxp.globalmanager.add("users", panel);
        }

        if (specificUser) {
            panel.openUser(specificUser);
        }
    }
};

opendxp.helpers.insertTextAtCursorPosition = function (text) {

    // get focused element
    var focusedElement = document.activeElement;
    var win = window;
    var doc = document;

    // now check if the focus is inside an iframe
    try {
        while (focusedElement.tagName.toLowerCase() == "iframe") {
            win = window[focusedElement.getAttribute("name")];
            doc = win.document;
            focusedElement = doc.activeElement;
        }
    } catch (e) {
        console.log(e);
    }

    var elTagName = focusedElement.tagName.toLowerCase();

    if (elTagName == "input" || elTagName == "textarea") {
        insertTextToFormElementAtCursor(focusedElement, text);
    } else if (elTagName == "div" && focusedElement.getAttribute("contenteditable")) {
        insertTextToContenteditableAtCursor(text, win, doc);
    }

};


opendxp.helpers.getMainTabMenuItems = function () {
    items = [{
        text: t('close_others'),
        iconCls: "",
        handler: function (menuItem) {
            var tabPanel = Ext.getCmp("opendxp_panel_tabs");
            var plugin = tabPanel.getPlugin("tabclosemenu");
            el = plugin.item;
            opendxp.helpers.closeAllElements(el);
            // clear the opentab store, so that also non existing elements are flushed
            opendxp.helpers.clearOpenTab();
        }.bind(this)
    }, {
        text: t('close_unmodified'),
        iconCls: "",
        handler: function (item) {
            opendxp.helpers.closeAllUnmodified();
            // clear the opentab store, so that also non existing elements are flushed
            opendxp.helpers.clearOpenTab();
        }.bind(this)
    }];


    // every tab panel can get this
    items.push({
        text: t('close_all'),
        iconCls: "",
        handler: function (item) {
            var tabPanel = Ext.getCmp("opendxp_panel_tabs");
            opendxp.helpers.closeAllElements(null, tabPanel);
            // clear the opentab store, so that also non existing elements are flushed
            opendxp.helpers.clearOpenTab();
        }.bind(this)
    });

    return items;
};


//opendxp.helpers.handleTabRightClick = function (tabPanel, el, index) {
//
//
//    if(Ext.get(el.tab)) {
//        Ext.get(el.tab).on("contextmenu", function (e) {
//
//            var items = [];
//
//            // this is only for the main tab panel
//            if(tabPanel.getId() == "opendxp_panel_tabs") {
//                items = [{
//                    text: t('close_others'),
//                    iconCls: "",
//                    handler: function (item) {
//                        opendxp.helpers.closeAllElements(el);
//                        // clear the opentab store, so that also non existing elements are flushed
//                        opendxp.helpers.clearOpenTab();
//                    }.bind(this)
//                }, {
//                    text: t('close_unmodified'),
//                    iconCls: "",
//                    handler: function (item) {
//                        opendxp.helpers.closeAllUnmodified();
//                        // clear the opentab store, so that also non existing elements are flushed
//                        opendxp.helpers.clearOpenTab();
//                    }.bind(this)
//                }];
//            }
//
//            // every tab panel can get this
//            items.push({
//                text: t('close_all'),
//                iconCls: "",
//                handler: function (item) {
//                    opendxp.helpers.closeAllElements(null,tabPanel);
//                    // clear the opentab store, so that also non existing elements are flushed
//                    opendxp.helpers.clearOpenTab();
//                }.bind(this)
//            });
//
//
//            var menu = new Ext.menu.Menu({
//                items: items
//            });
//
//            menu.showAt(e.getXY());
//            e.stopEvent();
//        });
//    }
//};

opendxp.helpers.uploadAssetFromFileObject = function (file, url, callbackSuccess, callbackProgress, callbackFailure) {

    if (typeof callbackSuccess != "function") {
        callbackSuccess = function () {
        };
    }
    if (typeof callbackProgress != "function") {
        callbackProgress = function () {
        };
    }
    if (typeof callbackFailure != "function") {
        callbackFailure = function () {
        };
    }

    if (file["size"]) {
        if (file["size"] > opendxp.settings["upload_max_filesize"]) {
            callbackSuccess();
            opendxp.helpers.showNotification(t("error"), t("file_is_bigger_that_upload_limit") + " " + file.name, "error");
            return;
        }
    }

    var data = new FormData();
    data.append('Filedata', file);
    data.append("filename", file.name);
    data.append("csrfToken", opendxp.settings['csrfToken']);

    var request = new XMLHttpRequest();

    // these wrappers simulate the jQuery behavior
    var successWrapper = function (ev) {
        var data = JSON.parse(request.responseText);
        if(ev.currentTarget.status < 400 && data.success === true) {
            callbackSuccess(data, request.statusText, request);
        } else {
            callbackFailure(request, request.statusText, ev);
        }
    };

    var errorWrapper = function (ev) {
        callbackFailure(request, request.statusText, ev);
    };

    request.upload.addEventListener("progress", callbackProgress, false);
    request.addEventListener("load", successWrapper, false);
    request.addEventListener("error", errorWrapper, false);
    request.addEventListener("abort", errorWrapper, false);
    request.open('POST', url);
    request.send(data);
};


opendxp.helpers.searchAndMove = function (parentId, callback, type) {
    if (type == "object") {
        config = {
            type: ["object"],
            subtype: {
                object: ["object", "folder"]
            },
            specific: {
                classes: null
            }
        };
    } else {
        config = {
            type: [type]
        }
    }
    opendxp.helpers.itemselector(true, function (selection) {

        var jobs = [];

        if (selection && selection.length > 0) {
            for (var i = 0; i < selection.length; i++) {
                var params;
                if (type == "object") {
                    params = {
                        id: selection[i]["id"],
                        values: Ext.encode({
                            parentId: parentId
                        })
                    };
                } else {
                    params = {
                        id: selection[i]["id"],
                        parentId: parentId
                    };
                }
                jobs.push([{
                    url: Routing.getBaseUrl() + "/admin/" + type + "/update",
                    method: 'PUT',
                    params: params
                }]);
            }
        }

        if (jobs.length == 0) {
            return;
        }

        this.addChildProgressBar = new Ext.ProgressBar({
            text: t('initializing')
        });

        this.addChildWindow = new Ext.Window({
            title: t("move"),
            layout: 'fit',
            width: 200,
            bodyStyle: "padding: 10px;",
            closable: false,
            plain: true,
            items: [this.addChildProgressBar],
            listeners: opendxp.helpers.getProgressWindowListeners()
        });

        this.addChildWindow.show();

        var pj = new opendxp.tool.paralleljobs({
            success: function (callbackFunction) {

                if (this.addChildWindow) {
                    this.addChildWindow.close();
                }

                this.deleteProgressBar = null;
                this.addChildWindow = null;

                if (typeof callbackFunction == "function") {
                    callbackFunction();
                }

                try {
                    var node = opendxp.globalmanager.get("layout_object_tree").tree.getNodeById(this.object.id);
                    if (node) {
                        tree.getStore().load({
                            node: node
                        });
                    }
                } catch (e) {
                    // node is not present
                }
            }.bind(this, callback),
            update: function (currentStep, steps, percent) {
                if (this.addChildProgressBar) {
                    var status = currentStep / steps;
                    this.addChildProgressBar.updateProgress(status, percent + "%");
                }
            }.bind(this),
            failure: function (response) {
                this.addChildWindow.close();
                Ext.MessageBox.alert(t("error"), t(response));
            }.bind(this),
            jobs: jobs
        });

    }.bind(this), config);
};


opendxp.helpers.sendTestEmail = function (from, to, subject, emailType, documentPath, content) {

    if(!emailType) {
        emailType = 'text';
    }

    var emailContentTextField = new Ext.form.TextArea({
        name: "content",
        fieldLabel: t("content"),
        height: 300,
    });
    emailContentTextField.hide();

    const documentTextField = new Ext.form.TextField({
        name: 'documentPath',
        flex: 1,
        editable: false
    });

    const items = [
        documentTextField
    ];

    if(opendxp.helpers.hasSearchImplementation()) {
        const searchDocumentButton = new Ext.Button({
            name: 'searchDocument',
            fieldLabel: t('document'),
            iconCls: 'opendxp_icon_search',
            handler: function() {
                opendxp.helpers.itemselector(false, function(e) {
                    documentTextField.setValue(e.fullpath);
                }, {
                    type: ["document"],
                    subtype: {
                        document: opendxp.settings.document_email_search_types
                    }
                });
            }
        });

        items.push(searchDocumentButton);
    }

    var documentComponent = Ext.create('Ext.form.FieldContainer', {
        fieldLabel: t('document'),
        layout: 'hbox',
        items: items,
        componentCls: "object_field",
        border: false,
        style: {
            padding: 0
        }
    });
    documentComponent.hide();


    var emailTypeDropdown = new Ext.form.ComboBox({
        name: 'emailType',
        width: 300,
        value: emailType,
        store: [
            ['document', t('document')],
            ['html', t('html')],
            ['text', t('text')]
        ],
        fieldLabel: t('type'),
        listeners: {
            select: function(t) {
                if(t.value === 'text' || t.value === 'html') {
                    emailContentTextField.show();
                } else {
                    emailContentTextField.hide();
                }

                if(t.value === 'document') {
                    documentComponent.show();
                    paramGrid.show();
                } else {
                    documentComponent.hide();
                    paramGrid.hide();
                }
            }
        }
    });

    var fromTextField = new Ext.form.TextField({
        name: "from",
        fieldLabel: t("from"),
    });

    var toTextField = new Ext.form.TextField({
        name: "to",
        fieldLabel: t("to"),
    });

    var subjectTextField = new Ext.form.TextField({
        name: "subject",
        fieldLabel: t("subject"),
    });

    var paramsStore = new Ext.data.ArrayStore({
        fields: [
            {name: 'key', type: 'string', persist: false},
            {name: 'value', type: 'string', persist: false}
        ]
    });

    var paramGrid = Ext.create('Ext.grid.Panel', {
        store: paramsStore,
        columns: [
            {
                text: t('key'),
                dataIndex: 'key',
                editor: new Ext.form.TextField(),
                width: 200
            },
            {
                text: t('value'),
                dataIndex: 'value',
                editor: new Ext.form.TextField(),
                flex: 1
            }
        ],
        stripeRows: true,
        columnLines: true,
        bodyCls: "opendxp_editable_grid",
        autoHeight: true,
        selModel: Ext.create('Ext.selection.CellModel'),
        hideHeaders: false,
        plugins: [
            Ext.create('Ext.grid.plugin.CellEditing', {})
        ],
        tbar: [
            {
                iconCls: "opendxp_icon_table_row opendxp_icon_overlay_add",
                handler: function() {
                    paramsStore.add({'key' : '', 'value': ''});
                }
            },
            {
                xtype: 'label',
                html: t('parameters')
            }
        ]
    });
    paramGrid.hide();

    var win = new Ext.Window({

        width: 800,
        height: 600,
        modal: true,
        title: t("send_test_email"),
        layout: "fit",
        closeAction: "close",
        items: [{
            xtype: "form",
            bodyStyle: "padding:10px;",
            itemId: "form",
            items: [
                fromTextField,
                toTextField,
                subjectTextField,
                emailTypeDropdown,
                emailContentTextField,
                documentComponent,
                paramGrid
            ],
            defaults: {
                width: 780
            }
        }],
        buttons: [{
            text: t("send"),
            iconCls: "opendxp_icon_email",
            handler: function () {
                send();
            }
        }]
    });

    var send = function () {


        var params = win.getComponent("form").getForm().getFieldValues();
        if(emailTypeDropdown.getValue() === 'document') {
            var allRecords = paramsStore
                .queryBy(function() { return true; }) // returns a collection
                .getRange();
            var emailParamsArray = [];
            for (var i = 0; i < allRecords.length; i++) {
                emailParamsArray.push({"key": allRecords[i].data['key'], "value": allRecords[i].data['value']});

            }
            params['mailParamaters'] =  JSON.stringify(emailParamsArray);
        }


        win.disable();
        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_email_sendtestemail'),
            params: params,
            method: "post",
            success: function () {
                Ext.Msg.show({
                    title: t("send_test_email"),
                    message: t("send_test_email_success"),
                    buttons: Ext.Msg.YESNO,
                    icon: Ext.Msg.QUESTION,
                    fn: function (btn) {
                        win.enable();
                        if (btn === 'no') {
                            win.close();
                        }
                    }
                });
            },
            failure: function () {
                win.close();
            }
        });

    };



    if(emailType) {
        emailTypeDropdown.setValue(emailType);
        if(emailType === 'document') {
            documentComponent.show();
            paramGrid.show();
        }
        if(emailType === 'html' || emailType === 'text') {
            emailContentTextField.show();
        }
    }
    if(documentPath) {
        documentTextField.setValue(documentPath);
    }
    if(content) {
        emailContentTextField.setValue(content);
    }
    if(from) {
        fromTextField.setValue(from);
    }
    if(to) {
        toTextField.setValue(to);
    }
    if(subject) {
        subjectTextField.setValue(subject);
    }


    win.show();


};

/* this is here so that it can be opened in the parent window when in editmode frame */
opendxp.helpers.openImageCropper = function (imageId, data, saveCallback, config) {
    var cropper = new top.opendxp.element.tag.imagecropper(imageId, data, saveCallback, config);
    return cropper;
};

/* this is here so that it can be opened in the parent window when in editmode frame */
opendxp.helpers.openImageHotspotMarkerEditor = function (imageId, data, saveCallback, config) {
    var editor = new opendxp.element.tag.imagehotspotmarkereditor(imageId, data, saveCallback, config);
    return editor;
};


opendxp.helpers.editmode = {};

opendxp.helpers.editmode.openLinkEditPanel = function (data, callback, config) {
    const TARGETS = ["", "_blank", "_self", "_top", "_parent"];
    const TYPES = ["asset", "document", "object"];

    config = config || {};
    const disabledFields = config.disabledFields || [];
    const allowedTargets = Ext.Array.intersect(TARGETS, config.allowedTargets || TARGETS);
    const allowedTypes = Ext.Array.intersect(TYPES, config.allowedTypes || TYPES);

    const internalTypeField = new Ext.form.Hidden({
        fieldLabel: 'internalType',
        value: data.internalType,
        name: 'internalType',
        readOnly: true,
        width: 520
    });

    const linkTypeField = new Ext.form.Hidden({
        fieldLabel: 'linktype',
        value: data.linktype,
        name: 'linktype',
        readOnly: true,
        width: 520
    });

    const pathField = new Ext.form.TextField({
        fieldLabel: t('path'),
        value: data.path,
        name: "path",
        width: 520,
        fieldCls: "opendxp_droptarget_input",
        enableKeyEvents: true,
        listeners: {
            keyup: function (el) {
                const value = el.getValue();
                const pathRegex = new RegExp('^(/|(/[^/]+)+/?)$');

                if(value && !value.match(pathRegex)) {
                    internalTypeField.setValue(null);
                    linkTypeField.setValue("direct");
                }
            }
        }
    });

    pathField.on("render", function (el) {
        // add drop zone
        new Ext.dd.DropZone(el.getEl(), {
            reference: this,
            ddGroup: "element",
            getTargetFromEvent: function (e) {
                return pathField.getEl();
            },

            onNodeOver: function (target, dd, e, data) {
                if (data.records.length !== 1) {
                    return;
                }

                data = data.records[0].data;
                if (data.type !== "folder" && allowedTypes.includes(data.elementType)) {
                    return Ext.dd.DropZone.prototype.dropAllowed;
                }
            }.bind(this),

            onNodeDrop: function (target, dd, e, data) {
                if(!opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                    return false;
                }

                data = data.records[0].data;
                if (data.type !== "folder" && allowedTypes.includes(data.elementType)) {
                    internalTypeField.setValue(data.elementType);
                    linkTypeField.setValue('internal');
                    pathField.setValue(data.path);
                    return true;
                }
                return false;
            }.bind(this)
        });
    }.bind(this));

    const textField = disabledFields.includes('text') ? null : {
        fieldLabel: t('text'),
        name: 'text',
        value: data.text
    };

    const fieldContainerItems = [
        pathField,
    ];

    if (opendxp.helpers.hasSearchImplementation()) {
        fieldContainerItems.push({
            xtype: "button",
            iconCls: "opendxp_icon_search",
            style: "margin-left: 5px",
            handler: function () {
                opendxp.helpers.itemselector(false, function (item) {
                    if (item) {
                        internalTypeField.setValue(item.type);
                        linkTypeField.setValue('internal');
                        pathField.setValue(item.fullpath);
                        return true;
                    }
                }, {
                    type: allowedTypes
                });
            }
        });
    }

    const fieldContainer = {
        xtype: "fieldcontainer",
        layout: 'hbox',
        border: false,
        items: fieldContainerItems,
    };

    const propertyFields = [];
    if (!disabledFields.includes('target')) {
        propertyFields.push({
            xtype: "combo",
            fieldLabel: t('target'),
            name: 'target',
            triggerAction: 'all',
            editable: true,
            mode: "local",
            store: allowedTargets,
            value: data.target,
            width: 300
        });
    }
    if (!disabledFields.includes('parameters')) {
        propertyFields.push({
            fieldLabel: t('parameters'),
            name: 'parameters',
            value: data.parameters
        });
    }
    if (!disabledFields.includes('anchor')) {
        propertyFields.push({
            fieldLabel: t('anchor'),
            name: 'anchor',
            value: data.anchor
        });
    }
    if (!disabledFields.includes('title')) {
        propertyFields.push({
            fieldLabel: t('title'),
            name: 'title',
            value: data.title
        });
    }
    const propertiesFieldSet = propertyFields.length === 0 ? null : {
        xtype: 'fieldset',
        layout: 'vbox',
        title: t('properties'),
        collapsible: false,
        defaultType: 'textfield',
        width: '100%',
        defaults: {
            width: 250
        },
        items: propertyFields
    };

    const advancedFields = [];
    if (!disabledFields.includes('accesskey')) {
        advancedFields.push({
            fieldLabel: t('accesskey'),
            name: 'accesskey',
            value: data.accesskey
        });
    }
    if (!disabledFields.includes('rel')) {
        advancedFields.push({
            fieldLabel: t('relation'),
            name: 'rel',
            width: 300,
            value: data.rel
        });
    }
    if (!disabledFields.includes('tabindex')) {
        advancedFields.push({
            fieldLabel: ('tabindex'),
            name: 'tabindex',
            value: data.tabindex
        });
    }
    if (!disabledFields.includes('class')) {
        advancedFields.push({
            fieldLabel: t('class'),
            name: 'class',
            width: 300,
            value: data["class"]
        });
    }
    const advancedTab = advancedFields.length === 0 ? null : {
        title: t('advanced'),
        layout: 'form',
        defaultType: 'textfield',
        border: false,
        items: advancedFields
    };

    const form = new Ext.FormPanel({
        itemId: "form",
        items: [
            {
                xtype: 'tabpanel',
                deferredRender: false,
                defaults: {autoHeight: true, bodyStyle: 'padding:10px'},
                border: false,
                items: [
                    {
                        title: t('basic'),
                        layout: 'vbox',
                        border: false,
                        defaultType: 'textfield',
                        items: [
                            // do not change the order, the server-side works with setValues - setPath expects
                            // the types are already set correctly
                            internalTypeField,
                            linkTypeField,
                            textField,
                            {
                                xtype: "fieldcontainer",
                                layout: 'hbox',
                                border: false,
                                items: [pathField, {
                                    xtype: "button",
                                    iconCls: "opendxp_icon_search",
                                    style: "margin-left: 5px",
                                    handler: function () {
                                        opendxp.helpers.itemselector(false, function (item) {
                                            if (item) {
                                                internalTypeField.setValue(item.type);
                                                linkTypeField.setValue('internal');
                                                pathField.setValue(item.fullpath);
                                                return true;
                                            }
                                        }, {
                                            type: Ext.Array.intersect(["asset", "document", "object"], allowedTypes)
                                        });
                                    }
                                }]
                            },
                            propertiesFieldSet
                        ]
                    },
                    advancedTab
                ]
            }
        ],
        buttons: [
            {
                text: t("empty"),
                listeners: {
                    "click": callback["empty"]
                },
                iconCls: "opendxp_icon_empty"
            },
            {
                text: t("cancel"),
                listeners: {
                    "click": callback["cancel"]
                },
                iconCls: "opendxp_icon_cancel"
            },
            {
                text: t("save"),
                listeners: {
                    "click": callback["save"]
                },
                iconCls: "opendxp_icon_save"
            }
        ]
    });


    const window = new Ext.Window({
        modal: false,
        width: 600,
        maxHeight: 470,
        title: t("edit_link"),
        items: [form],
        layout: "fit"
    });

    window.show();

    return window;
};


opendxp.helpers.editmode.openVideoEditPanel = function (data, callback) {

    const allowedTypes = data.allowedTypes;
    let window = null;
    let form = null;
    const fieldPath = new Ext.form.TextField({
        fieldLabel: t('path'),
        itemId: "path",
        value: data.path,
        name: "path",
        width: 420,
        fieldCls: "opendxp_droptarget_input",
        enableKeyEvents: true,
        listeners: {
            keyup: function (el) {
                if (allowedTypes.includes("youtube")
                    && (el.getValue().indexOf("youtu.be") >= 0 || el.getValue().indexOf("youtube.com") >= 0) && el.getValue().indexOf("http") >= 0) {
                    form.getComponent("type").setValue("youtube");
                    updateType("youtube");
                } else if (allowedTypes.includes("vimeo")
                    && el.getValue().indexOf("vimeo") >= 0 && el.getValue().indexOf("http") >= 0) {
                    form.getComponent("type").setValue("vimeo");
                    updateType("vimeo");
                } else if (allowedTypes.includes("dailymotion")
                    && (el.getValue().indexOf("dai.ly") >= 0 || el.getValue().indexOf("dailymotion") >= 0) && el.getValue().indexOf("http") >= 0) {
                    form.getComponent("type").setValue("dailymotion");
                    updateType("dailymotion");
                }
            }.bind(this)
        }
    });
    const poster = new Ext.form.TextField({
        fieldLabel: t('poster_image'),
        value: data.poster,
        name: "poster",
        width: 420,
        fieldCls: "opendxp_droptarget_input",
        enableKeyEvents: true,
        listeners: {
            keyup: function (el) {
                //el.setValue(data.poster)
            }.bind(this)
        }
    });

    const initDD = function (el) {
        // register at global DnD manager
        new Ext.dd.DropZone(el.getEl(), {
            reference: this,
            ddGroup: 'element',
            getTargetFromEvent: function (e) {
                return el.getEl();
            },

            onNodeOver: function (target, dd, e, data) {
                if (data.records.length === 1) {
                    data = data.records[0].data;
                    if (target && target.getId() === poster.getId()) {
                        if (data.elementType === 'asset' && data.type === 'image') {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        }
                    } else {
                        if (data.type === 'video') {
                            return Ext.dd.DropZone.prototype.dropAllowed;
                        }
                    }
                }
                return Ext.dd.DropZone.prototype.dropNotAllowed;
            }.bind(this),

            onNodeDrop: function (target, dd, e, data) {

                if (!opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                    return false;
                }

                if (target) {
                    data = data.records[0].data;

                    if (target.getId() === fieldPath.getId()) {
                        if (data.type === 'video') {
                            fieldPath.setValue(data.path);
                            form.getComponent('type').setValue('asset');
                            updateType('asset');
                            return true;
                        }
                    } else if (target.getId() === poster.getId()) {
                        if (data.elementType === 'asset' && data.type === 'image') {
                            poster.setValue(data.path);
                            return true;
                        }
                    }
                }

                return false;
            }.bind(this)
        });
    };

    fieldPath.on('render', initDD);
    poster.on('render', initDD);

    const openButton = new Ext.Button({
        iconCls: 'opendxp_icon_open',
        handler: function () {
            opendxp.helpers.openElement(fieldPath.getValue(), 'asset');
            window.close();
        }
    });

    let searchButton = undefined;
    let posterImageSearchButton = undefined;
    if(opendxp.helpers.hasSearchImplementation()){
        searchButton = new Ext.Button({
            iconCls: "opendxp_icon_search",
            handler: function () {
                opendxp.helpers.itemselector(false, function (item) {
                    if (item) {
                        fieldPath.setValue(item.fullpath);
                        return true;
                    }
                }, {
                    type: ["asset"],
                    subtype: {
                        asset: ["video"]
                    }
                });
            }
        });

        posterImageSearchButton = new Ext.Button({
            iconCls: "opendxp_icon_search",
            handler: function () {
                opendxp.helpers.itemselector(false, function (item) {
                    if (item) {
                        poster.setValue(item.fullpath);
                        return true;
                    }
                }, {
                    type: ["asset"],
                    subtype: {
                        asset: ["image"]
                    }
                });
            }
        });
    }

    const posterImageOpenButton = new Ext.Button({
        iconCls: "opendxp_icon_open",
        handler: function () {
            opendxp.helpers.openElement(poster.getValue(), 'asset');
            window.close();
        }
    });

    const updateType = function (type) {
        if (typeof searchButton !== 'undefined') {
            searchButton.enable();
        }
        openButton.enable();

        var labelEl = form.getComponent("pathContainer").getComponent("path").labelEl;
        labelEl.update(t("path"));

        if (type !== "asset") {
            if (typeof searchButton !== 'undefined') {
                searchButton.disable();
            }
            openButton.disable();
        }

        if (type === "youtube") {
            labelEl.update("ID");
        }

        if (type === "vimeo") {
            labelEl.update("ID");
        }

        if (type === "dailymotion") {
            labelEl.update("ID");
        }
    };

    const pathContainerItems = [
        fieldPath
    ];
    const posterContainerItems = [
        poster
    ];

    if(opendxp.helpers.hasSearchImplementation()) {
        pathContainerItems.push(searchButton);
        posterContainerItems.push(posterImageSearchButton, posterImageOpenButton);
    }

    pathContainerItems.push(openButton);

    form = new Ext.FormPanel({
        itemId: "form",
        bodyStyle: "padding:10px;",
        items: [{
            xtype: "combo",
            itemId: "type",
            fieldLabel: t('type'),
            name: 'type',
            triggerAction: 'all',
            editable: false,
            width: 270,
            mode: "local",
            store: allowedTypes,
            value: data.type,
            listeners: {
                select: function (combo) {
                    var type = combo.getValue();
                    updateType(type);
                }.bind(this)
            }
        }, {
            xtype: "fieldcontainer",
            layout: 'hbox',
            border: false,
            itemId: "pathContainer",
            items: pathContainerItems
        }, {
            xtype: "fieldcontainer",
            layout: 'hbox',
            border: false,
            itemId: "posterContainer",
            items: posterContainerItems
        }, {
            xtype: "textfield",
            name: "title",
            itemId: "title",
            fieldLabel: t('title'),
            width: 420,
            value: data.title
        }, {
            xtype: "textarea",
            itemId: "description",
            name: "description",
            fieldLabel: t('description'),
            width: 420,
            height: 50,
            value: data.description
        }],
        buttons: [
            {
                text: t("save"),
                listeners: {
                    "click": callback["save"]
                },
                iconCls: "opendxp_icon_save"
            },
            {
                text: t("cancel"),
                iconCls: "opendxp_icon_cancel",
                listeners: {
                    "click": callback["cancel"]
                }
            }
        ]
    });

    window = new Ext.Window({
        width: 510,
        height: 370,
        title: t("video"),
        items: [form],
        layout: "fit",
        listeners: {
            afterrender: function () {
                updateType(data.type);
            }.bind(this)
        }
    });
    window.show();

    return window;
};


opendxp.helpers.showAbout = function () {

    var html = '<div class="opendxp_about_window">';
    html += '<br><img src="/bundles/opendxpadmin/img/logo.svg" style="width: 300px;"><br>';

    html += '<br><b>Core Version: ' + opendxp.settings.version + '</b>';

    html += '<br><br><a href="https://www.opendxp.ch/" target="_blank">www.opendxp.ch</a>';
    html += '<br><br><a href="https://github.com/open-dxp/opendxp/blob/1.x/LICENSE.md" target="_blank">License</a> | <a href="mailto:contact@opendxp.ch">Contact</a>';
    html += '</div>';

    var win = new Ext.Window({
        title: t("about"),
        width: 500,
        height: 300,
        bodyStyle: "padding: 10px;",
        modal: true,
        html: html
    });

    win.show();
};

opendxp.helpers.markColumnConfigAsFavourite = function (objectId, classId, gridConfigId, searchType, global, type) {

    type = type || "object";

    var assetRoute = 'opendxp_admin_asset_assethelper_gridmarkfavouritecolumnconfig';
    var objectRoute = 'opendxp_admin_dataobject_dataobjecthelper_gridmarkfavouritecolumnconfig';
    var route = null;

    if (type === 'object') {
        route = objectRoute;
    }
    else if (type === 'asset') {
        route = assetRoute;
    }
    else {
        throw new Error('Unknown type given, given "' + type + '"');
    }

    try {
        var url = Routing.generate(route);

        Ext.Ajax.request({
            url: url,
            method: 'POST',
            params: {
                objectId: objectId,
                classId: classId,
                gridConfigId: gridConfigId,
                searchType: searchType,
                global: global ? 1 : 0,
                type: type
            },
            success: function (response) {
                try {
                    var rdata = Ext.decode(response.responseText);

                    if (rdata && rdata.success) {
                        opendxp.helpers.showNotification(t("success"), t("saved_successfully"), "success");

                        if (rdata.specializedConfigs) {
                            opendxp.helpers.removeOtherConfigs(objectId, classId, gridConfigId, searchType);
                        }
                    }
                    else {
                        opendxp.helpers.showNotification(t("error"), t("saving_failed"),
                            "error", t(rdata.message));
                    }
                } catch (e) {
                    opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }.bind(this),
            failure: function () {
                opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error");
            }
        });

    } catch (e3) {
        opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error");
    }
};


opendxp.helpers.removeOtherConfigs = function (objectId, classId, gridConfigId, searchType) {
    Ext.MessageBox.show({
        title: t('apply_to_all_objects'),
        msg: t('apply_to_all_objects_msg'),
        buttons: Ext.Msg.YESNO,
        icon: Ext.MessageBox.INFO,
        fn: function (btn) {
            if (btn === 'yes') {
                Ext.Ajax.request({
                    url: Routing.generate('opendxp_admin_dataobject_dataobjecthelper_gridconfigapplytoall'),
                    method: 'POST',
                    params: {
                        objectId: objectId,
                        classId: classId,
                        gridConfigId: gridConfigId,
                        searchType: searchType,
                    }
                });
            }

        }.bind(this)
    });
};

opendxp.helpers.saveColumnConfig = function (objectId, classId, configuration, searchType, button, callback, settings, type, context, filter) {

    type = type || "object";

    var assetRoute = 'opendxp_admin_asset_assethelper_gridsavecolumnconfig';
    var objectRoute = 'opendxp_admin_dataobject_dataobjecthelper_gridsavecolumnconfig';
    var route = null;

    if (type === 'object') {
        route = objectRoute;
    }
    else if (type === 'asset') {
        route = assetRoute;
    }
    else {
        throw new Error('Unknown type given, given "' + type + '"');
    }

    try {
        type = type || "object";
        var data = {
            id: objectId,
            class_id: classId,
            gridconfig: Ext.encode(configuration),
            searchType: searchType,
            settings: Ext.encode(settings),
            context: Ext.encode(context),
            type: type,
            filter: Ext.encode(filter),
        };

        var url = Routing.generate(route);

        Ext.Ajax.request({
            url: url,
            method: "post",
            params: data,
            success: function (response) {
                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        if (button) {
                            button.hide();
                        }
                        if (typeof callback == "function") {
                            callback(rdata);
                        }
                        opendxp.helpers.showNotification(t("success"), t("saved_successfully"), "success");
                    }
                    else {
                        opendxp.helpers.showNotification(t("error"), t("saving_failed"),
                            "error", t(rdata.message));
                    }
                } catch (e) {
                    opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error");
                }
            }.bind(this),
            failure: function () {
                opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error");
            }
        });

    } catch (e3) {
        opendxp.helpers.showNotification(t("error"), t("saving_failed"), "error");
    }
};

opendxp.helpers.openGenericIframeWindow = function (id, src, iconCls, title) {
    try {
        opendxp.globalmanager.get(id).activate();
    }
    catch (e) {
        opendxp.globalmanager.add(id, new opendxp.tool.genericiframewindow(id, src, iconCls, title));
    }
};

opendxp.helpers.hideRedundantSeparators = function (menu) {
    var showSeparator = false;

    for (var i = 0; i < menu.items.length; i++) {
        var item = menu.items.getAt(i);

        if (item instanceof Ext.menu.Separator) {
            if (!showSeparator || i == menu.items.length - 1) {
                item.hide();
            }
            showSeparator = false;
        } else {
            showSeparator = true;
        }
    }
};

opendxp.helpers.initMenuTooltips = function () {
    Ext.each(Ext.query("[data-menu-tooltip]:not(.initialized)"), function (el) {
        var item = Ext.get(el);

        if (item) {
            item.on("mouseenter", function (e) {
                var opendxp_tooltip = Ext.get('opendxp_tooltip');
                var item = Ext.get(e.target);
                opendxp_tooltip.show();
                opendxp_tooltip.removeCls('right');
                opendxp_tooltip.update(item.getAttribute("data-menu-tooltip"));

                var offset = item.getXY();
                var top = offset[1];
                top = top + (item.getHeight() / 2);

                opendxp_tooltip.applyStyles({
                    top: top + "px",
                    left: '60px',
                    right: 'auto'
                });
            }.bind(this));

            item.on("mouseleave", function (e) {
                Ext.get('opendxp_tooltip').hide();
            });

            item.addCls("initialized", "true");
        }
    });
};

opendxp.helpers.requestNicePathDataGridDecorator = function (gridView, targets) {

    if(targets && targets.count() > 0) {
        gridView.mask();
    }
    targets.each(function (record) {
        var el = gridView.getRow(record);
        if (el) {
            el = Ext.fly(el);
            el.addCls("grid_nicepath_requested");
        }
    }, this);

};

opendxp.helpers.requestNicePathData = function (source, targets, config, fieldConfig, context, decorator, responseHandler) {
    if (context && (context['containerType'] == "batch" || context['containerType'] == "filterByRelationWindow")) {
        return;
    }

    if (!config.loadEditModeData && (typeof targets === "undefined" || !fieldConfig.pathFormatterClass || (typeof fieldConfig.visibleFields === "string" && fieldConfig.visibleFields !== '' && fieldConfig.visibleFields.split(',').indexOf('fullpath') === -1))) {
        return;
    }

    if (!targets.getCount() > 0) {
        return;
    }

    config = config || {};
    Ext.applyIf(config, {
        idProperty: "id"
    });

    var elementData = {};

    targets.each(function (record) {
        var recordId = record.data[config.idProperty];
        elementData[recordId] = record.data;
    }, this);

    if (decorator) {
        decorator(targets);
    }

    elementData = Ext.encode(elementData);

    Ext.Ajax.request({
        method: 'POST',
        url: Routing.generate('opendxp_admin_element_getnicepath'),
        params: {
            source: Ext.encode(source),
            targets: elementData,
            context: Ext.encode(context),
            loadEditModeData: config.loadEditModeData,
            idProperty: config.idProperty
        },
        success: function (response) {
            try {
                var rdata = Ext.decode(response.responseText);
                if (rdata.success) {

                    var responseData = rdata.data;
                    responseHandler(responseData);

                    opendxp.layout.refresh();
                }
            } catch (e) {
                console.log(e);
            }
        }.bind(this)
    });

    return true;
};

opendxp.helpers.getNicePathHandlerStore = function (store, config, gridView, responseData) {
    config = config || {};
    Ext.applyIf(config, {
        idProperty: "id",
        pathProperty: "path"
    });

    store.ignoreDataChanged = true;
    store.each(function (record, id) {
        var recordId = record.data[config.idProperty];

        if (typeof responseData[recordId] != "undefined") {

            if(config.loadEditModeData) {
                for(var i = 0; i < config.fields.length; i++) {
                    record.set(config.fields[i], responseData[recordId][config.fields[i]], {dirty: false});
                }
                if(responseData[recordId]['$$nicepath']) {
                    record.set(config.pathProperty, responseData[recordId]['$$nicepath'], {dirty: false});
                }
            } else {
                record.set(config.pathProperty, responseData[recordId], {dirty: false});
            }

            var el = gridView.getRow(record);
            if (el) {
                el = Ext.fly(el);
                el.removeCls("grid_nicepath_requested");
            }

        }
    }, this);
    store.ignoreDataChanged = false;

    gridView.unmask();
    gridView.updateLayout();
};

opendxp.helpers.exportWarning = function (type, callback) {
    var iconComponent = new Ext.Component({
        cls: "x-message-box-warning x-dlg-icon"
    });

    var textContainer = Ext.Component({
        html: type.warningText
    });

    var promptContainer = new Ext.container.Container({
        flex: 1,
        layout: {
            type: 'vbox',
            align: 'stretch'
        },
        padding: '0px 0px 0px 10px',
        items: [textContainer]
    });

    var topContainer = new Ext.container.Container({
            layout: 'hbox',
            padding: 10,
            style: {
                overflow: 'hidden'
            },
            items: [iconComponent, promptContainer]
        }
    );

    var objectSettingsContainer = type.getObjectSettingsContainer();

    var formPanelItems = [];

    if (objectSettingsContainer) {
        formPanelItems.push(objectSettingsContainer);
    }

    var exportSettingsContainer = type.getExportSettingsContainer();

    if (exportSettingsContainer) {
        formPanelItems.push(exportSettingsContainer);
    }

    var formPanel = new Ext.form.FormPanel({
        bodyStyle: 'padding:10px',
        items: formPanelItems
    });

    var window = new Ext.Window({
        modal: true,
        title: type.text,
        width: 600,
        bodyStyle: "padding: 10px;",
        buttonAlign: "center",
        shadow: false,
        closable: true,
        items: [topContainer, formPanel],
        buttons: [{
            text: t("OK"),
            handler: function () {
                if (formPanel.isValid()) {
                    callback(formPanel.getValues());
                    window.close();
                }
            }.bind(this)
        },
            {
                text: t("cancel"),
                handler: function () {
                    window.close();
                }
            }
        ]
    });

    window.show();
};

opendxp.helpers.generatePassword = function (len) {
    var length = (len) ? (len) : (20);
    var string = "abcdefghijklmnopqrstuvwxyz"; //to upper
    var numeric = '0123456789';
    var password = "";
    var character = "";
    while (password.length < length) {
        entity1 = Math.ceil(string.length * Math.random() * Math.random());
        entity2 = Math.ceil(numeric.length * Math.random() * Math.random());
        hold = string.charAt(entity1);
        hold = (entity1 % 2 == 0) ? (hold.toUpperCase()) : (hold);
        character += hold;
        character += numeric.charAt(entity2);
        password = character;
    }
    return password;
};

opendxp.helpers.isValidPassword = function (pass) {
    if (pass.length < 10) {
        return false;
    }
    return true;
};

opendxp.helpers.getDeeplink = function (type, id, subtype) {
    let target = type + "_" + id + "_" + subtype;
    let url    = Routing.generate('opendxp_admin_login_deeplink', {}, true) + '?' + target;

    if (opendxp.settings['custom_admin_entrypoint_url'] !== null) {
        url = opendxp.settings['custom_admin_entrypoint_url'] + '?deeplink=' + target;
    }

    return url;
};

opendxp.helpers.showElementHistory = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("objects") || user.isAllowed("documents") || user.isAllowed("assets")) {
        opendxp.layout.toolbar.prototype.showElementHistory();
    }
};

opendxp.helpers.closeAllTabs = function() {
    opendxp.helpers.closeAllElements();
    // clear the opentab store, so that also non existing elements are flushed
    opendxp.helpers.clearOpenTab();

};

opendxp.helpers.searchAndReplaceAssignments = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("objects") || user.isAllowed("documents") || user.isAllowed("assets")) {
        new opendxp.element.replace_assignments();
    }
};

opendxp.helpers.redirects = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("redirects")) {
        opendxp.layout.toolbar.prototype.editRedirects();
    }
};

opendxp.helpers.sharedTranslations = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("translations")) {
        opendxp.layout.toolbar.prototype.editTranslations();
    }
};

opendxp.helpers.recycleBin = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("recyclebin")) {
        opendxp.layout.toolbar.prototype.recyclebin();
    }
};

opendxp.helpers.notesEvents = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("notes_events")) {
        opendxp.layout.toolbar.prototype.notes();
    }
};

opendxp.helpers.tagConfiguration = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("tags_configuration")) {
        opendxp.layout.toolbar.prototype.showTagConfiguration();
    }
};

opendxp.helpers.users = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("users")) {
        opendxp.layout.toolbar.prototype.editUsers();
    }
};

opendxp.helpers.roles = function() {
    var user = opendxp.globalmanager.get("user");
    if (user.isAllowed("users")) {
        opendxp.layout.toolbar.prototype.editRoles();
    }
};

opendxp.helpers.clearDataCache = function() {
    var user = opendxp.globalmanager.get("user");
    if ((user.isAllowed("clear_cache") || user.isAllowed("clear_temp_files") || user.isAllowed("clear_fullpage_cache"))) {
        opendxp.layout.toolbar.prototype.clearCache({'only_opendxp_cache': true})
    }
};

// HAS TO BE THE VERY LAST ENTRY !!!
opendxp.helpers.keyBindingMapping = {
    "save": opendxp.helpers.handleCtrlS,
    "publish": opendxp.helpers.togglePublish.bind(this, true),
    "unpublish": opendxp.helpers.togglePublish.bind(this, false),
    "rename": opendxp.helpers.rename.bind(this),
    "refresh": opendxp.helpers.handleF5,
    "openDocument": opendxp.helpers.openElementByIdDialog.bind(this, "document"),
    "openAsset": opendxp.helpers.openElementByIdDialog.bind(this, "asset"),
    "openObject": opendxp.helpers.openElementByIdDialog.bind(this, "object"),
    "openClassEditor": opendxp.helpers.openClassEditor,
    "openInTree": opendxp.helpers.openInTree,
    "showMetaInfo": opendxp.helpers.showMetaInfo,
    "searchDocument": opendxp.helpers.searchAction.bind(this, "document"),
    "searchAsset": opendxp.helpers.searchAction.bind(this, "asset"),
    "searchObject": opendxp.helpers.searchAction.bind(this, "object"),
    "showElementHistory": opendxp.helpers.showElementHistory,
    "closeAllTabs": opendxp.helpers.closeAllTabs,
    "searchAndReplaceAssignments": opendxp.helpers.searchAndReplaceAssignments,
    "redirects": opendxp.helpers.redirects,
    "sharedTranslations": opendxp.helpers.sharedTranslations,
    "recycleBin": opendxp.helpers.recycleBin,
    "notesEvents": opendxp.helpers.notesEvents,
    "tagManager": opendxp.helpers.tagManager,
    "tagConfiguration": opendxp.helpers.tagConfiguration,
    "users": opendxp.helpers.users,
    "roles": opendxp.helpers.roles,
    "clearDataCache": opendxp.helpers.clearDataCache
};

opendxp.helpers.showPermissionError = function(permission) {
    Ext.MessageBox.alert(t("error"), sprintf(t('permission_missing'), t(permission)));
};

opendxp.helpers.registerAssetDnDSingleUpload = function (element, parent, parentType, success, failure, context) {

    if (typeof success != "function") {
        success = function () {
        };
    }

    if (typeof failure != "function") {
        failure = function () {
        };
    }

    var fn = function (e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'copy';
        return false;
    };

    element.addEventListener("dragenter", fn, true);
    element.addEventListener("dragover", fn, true);
    element.addEventListener("drop", function (e) {

        e.stopPropagation();
        e.preventDefault();

        var dataTransfer = e.dataTransfer;

        var win = new Ext.Window({
            items: [],
            modal: true,
            closable: false,
            bodyStyle: "padding:10px;",
            width: 500,
            autoHeight: true,
            autoScroll: true
        });
        win.show();

        if(dataTransfer["files"]) {
            if(dataTransfer["files"][0]) {
                var file = dataTransfer["files"][0];

                if (window.FileList && file.name && file.size) { // check for size (folder has size=0)
                    var pbar = new Ext.ProgressBar({
                        width:465,
                        text: file.name,
                        style: "margin-bottom: 5px"
                    });

                    win.add(pbar);
                    win.updateLayout();

                    var params = {};

                    if(parent !== undefined){
                        if(parentType === 'path') {
                            params['parentPath'] = parent;
                        } else if (parentType === 'id') {
                            params['parentId'] = parent;
                        }
                    }

                    if (context) {
                        params['context'] = Ext.encode(context);
                    }

                    var uploadUrl = Routing.generate('opendxp_admin_asset_addasset', params);

                    opendxp.helpers.uploadAssetFromFileObject(file, uploadUrl,
                        function (evt) {
                            // success
                            win.close();
                            success(evt);
                        },
                        function (evt) {
                            //progress
                            if (evt.lengthComputable) {
                                var percentComplete = evt.loaded / evt.total;
                                var progressText = file.name + " ( " + Math.floor(percentComplete*100) + "% )";
                                if(percentComplete == 1) {
                                    progressText = file.name + " " + t("please_wait");
                                }

                                pbar.updateProgress(percentComplete, progressText);
                            }
                        },
                        function (evt) {
                            // error
                            var res = Ext.decode(evt["responseText"]);
                            opendxp.helpers.showNotification(t("error"), res.message ? res.message : t("error"), "error", evt["responseText"]);
                            win.close();
                            failure(evt);
                        }
                    );

                } else if (!empty(file.type) && file.size < 1) { //throw error for 0 byte file
                    Ext.MessageBox.alert(t('error'), t('error_empty_file_upload'));
                    win.close();
                } else {
                    Ext.MessageBox.alert(t('error'), t('unsupported_filetype'));
                    win.close();
                }
            } else {
                // if no files are uploaded (doesn't match criteria, ...) close the progress win immediately
                win.close();
            }
        }
    }.bind(this), true);
};

opendxp.helpers.dragAndDropValidateSingleItem = function (data) {
    if(data.records.length > 1) {
        Ext.MessageBox.alert(t('error'), t('you_can_only_drop_one_element_here'));
        return false;
    }

    return true;
};

opendxp.helpers.openProfile = function () {
    try {
        opendxp.globalmanager.get("profile").activate();
    }
    catch (e) {
        opendxp.globalmanager.add("profile", new opendxp.settings.profile.panel());
    }
};

opendxp.helpers.copyStringToClipboard = function (str) {
    var selection = document.getSelection(),
        prevSelection = (selection.rangeCount > 0) ? selection.getRangeAt(0) : false,
        el;

    // create element and insert string
    el = document.createElement('textarea');
    el.value = str;
    el.setAttribute('readonly', '');
    el.style.position = 'absolute';
    el.style.left = '-9999px';

    // insert element, select all text and copy
    document.body.appendChild(el);
    el.select();
    document.execCommand('copy');
    document.body.removeChild(el);

    // restore previous selection
    if (prevSelection) {
        selection.removeAllRanges();
        selection.addRange(prevSelection);
    }
};

opendxp.helpers.treeToolTipShow = function (el, record, item) {

    if (record.data.qtipCfg) {
        var text = "<b>" + record.data.qtipCfg.title + "</b> | ";

        if (record.data.qtipCfg.text) {
            text += record.data.qtipCfg.text;
        } else {
            text += (t("type") + ": "+ t(record.data.type));
        }

        var opendxp_tooltip = Ext.get('opendxp_tooltip');

        opendxp_tooltip.show();
        opendxp_tooltip.update(text);
        opendxp_tooltip.removeCls('right');

        var offsetTabPanel = Ext.get('opendxp_panel_tabs').getXY();
        var offsetTreeNode = Ext.get(item).getXY();
        var parentTree = el.ownerCt.ownerCt;

        if(parentTree.region === 'west') {
            opendxp_tooltip.applyStyles({
                top: (offsetTreeNode[1] + 8) + "px",
                left: offsetTabPanel[0] + "px",
                right: 'auto'
            });
        }

        if(parentTree.region === 'east') {
            opendxp_tooltip.addCls('right');
            opendxp_tooltip.applyStyles({
                top: (offsetTreeNode[1] + 8) + "px",
                right: (parentTree.width + 35) + "px",
                left: 'auto'
            });
        }
    }
};

opendxp.helpers.getAssetMetadataDataTypes = function (allowIn) {
    var result = [];
    for (var property in opendxp.asset.metadata.data) {
        // filter out base class
        if (property !== "data" && opendxp.asset.metadata.data.hasOwnProperty(property)) {
            if (opendxp.asset.metadata.data[property].prototype.allowIn[allowIn]) {
                result.push(property);
            }
        }
    }
    return result;
};

opendxp.helpers.treeToolTipHide = function () {
    Ext.get('opendxp_tooltip').hide();
};

opendxp.helpers.progressWindowOffsets = [-50];

opendxp.helpers.getProgressWindowListeners = function () {
    return {
        show: function(win) {
            let winY = opendxp.helpers.progressWindowOffsets.reduce(function(a, b) {
                return Math.min(a, b);
            });

            win.alignTo(Ext.getBody(), "br-br", [-40, winY]);
            let newOffset = winY - (win.getHeight()+20);
            opendxp.helpers.progressWindowOffsets.push(newOffset);
            win.myProgressWinOffset = newOffset;
        },
        destroy: function(win) {
            let index = opendxp.helpers.progressWindowOffsets.indexOf(win.myProgressWinOffset);
            if (index !== -1) {
                opendxp.helpers.progressWindowOffsets.splice(index, 1);
            }
        }
    };
};

opendxp.helpers.reloadUserImage = function (userId) {
    var image = Routing.generate('opendxp_admin_user_getimage', {id: userId, '_dc': Ext.Date.now()});

    if (opendxp.currentuser.id == userId) {
        Ext.get("opendxp_avatar").query('img')[0].src = image;
    }

    if (Ext.getCmp("opendxp_user_image_" + userId)) {
        Ext.getCmp("opendxp_user_image_" + userId).setSrc(image);
    }

    if (Ext.getCmp("opendxp_profile_image_" + userId)) {
        Ext.getCmp("opendxp_profile_image_" + userId).setSrc(image);
    }
};

/**
 * Takes a number representing seconds and formats it as a human-readable string such as "1:15:05" for 1 hour 15 minutes 5 seconds
 * @param {int|float} dataDuration duration in seconds
 * @returns {string|*}
 */
opendxp.helpers.formatTimeDuration = function (dataDuration) {
    if (!is_numeric(dataDuration)) {
        // Unknown data, return as is
        return dataDuration;
    }

    let durationString = '';

    let hours = Math.floor(dataDuration / 3600);
    dataDuration %= 3600;
    if (hours > 0) {
        durationString += hours + ":";
    }

    durationString += Math.floor(dataDuration / 60) + ":";
    durationString += ("0" + Math.round(dataDuration % 60)).slice(-2);

    return durationString;
};

/**
 * Delete confirm dialog box
 *
 * @param title
 * @param name
 * @param deleteCallback
 */
opendxp.helpers.deleteConfirm = function (title, name, deleteCallback) {
    Ext.Msg.confirm(t('delete'), sprintf(t('delete_message_advanced'),
            title, name),
        function (btn) {
            if (btn === 'yes') {
                if (typeof deleteCallback == "function") {
                    deleteCallback();
                }
            }
        }.bind(this))
};

opendxp.helpers.treeDragDropValidate = function (node, oldParent, newParent) {
    const disabledLayoutTypes = ['accordion', 'text', 'iframe', 'button']
    if (newParent.data.editor) {
        if (disabledLayoutTypes.includes(newParent.data.editor.type)) {
            return false;
        }

        return this.isComponentAsChildAllowed(newParent, node);
    }

    if (newParent.data.root && node.data.type !== 'layout') {
        return false;
    }

    return true;
};

opendxp.helpers.isComponentAsChildAllowed = function (parentNode, childNode) {
    const parentType = parentNode.data.editor.type;
    const childType = childNode.data.editor.type;
    const allowedChildren = opendxp.object.helpers.layout.getAllowedTypes();

    if (allowedChildren[parentType] &&
        allowedChildren[parentType].includes(childType) ||
        (allowedChildren[parentType].includes('data') && childNode.data.type === 'data')
    ) {
        return true
    }

    return false;
}

/**
 * Building menu with priority
 * @param items
 */
opendxp.helpers.buildMenu = function(items) {
    // priority for every menu and submenu starts at 10
    // leaving enough space for bundles etc.
    let priority = 10;
    for(let i = 0; i < items.length; i++) {
        // only adding priority if not set yet
        if(items[i].priority === undefined && items[i].text !== undefined) {
            items[i].priority = priority;
            priority += 10;
        }
        // if there are no submenus left, skip to the next item
        if(items[i].menu === undefined || null === items[i].menu) {
            continue;
        }

        // if the submenu has no items, remove the submenu itself
        if(items[i].menu.items.length === 0){
            items.splice(i, 1);
            continue;
        }

        opendxp.helpers.buildMenu(items[i].menu.items);
        items[i].menu = Ext.create('opendxp.menu.menu', items[i].menu);
    }
};

opendxp.helpers.buildMainNavigationMarkup = function(menu) {
    // priority for main menu starts at 10
    // leaving enough space for bundles etc.

    let priority = 10;
    Object.keys(menu).forEach(key => {
        if(menu[key].priority === undefined) {
            menu[key].priority = priority;
            priority += 10;
        }
    });


    const dh = Ext.DomHelper;
    const ul = Ext.get("opendxp_navigation_ul");
    const menuPrefix = 'opendxp_menu_';

    // sorting must be done manually here.
    Object.keys(menu).sort((a, b) => {
        // a and b are the keys like file, extras e.t.c
        // we use the keys to get the menu object themselves with the priorities
        return opendxp.helpers.priorityCompare(menu[a], menu[b]);
    }).filter(key => {
        // the notifications are excluded from this
        return !menu[key]['exclude'];
    }).forEach(key => {
        const li = {
            id: menuPrefix + key,
            tag: 'li',
            cls: 'opendxp_menu_item opendxp_menu_needs_children',
            html: '<div id="menuitem-' + key + '-iconEl" data-ref="iconEl" class="x-menu-item-main-icon x-menu-item-icon ' + menu[key]['iconCls'] + '"></div>',
            'data-menu-tooltip': menu[key]['label']
        };
        if(menu[key]['style']) {
            li.style = menu[key]['style'];
        }
        dh.append(ul, li);
    });

    // add the maintenance at last
    dh.append(ul,
        {
            id: menuPrefix + 'maintenance',
            tag: 'li',
            cls: 'opendxp_menu_item',
            style: 'display:none;',
            'data-menu-tooltip': t('deactivate_maintenance')
        }
    );
}

opendxp.helpers.priorityCompare = function(a, b) {
    let priorityA = a.priority ?? Number.MAX_VALUE;
    let priorityB = b.priority ?? Number.MAX_VALUE;

    if(priorityA > priorityB) {
        return 1;
    }

    if(priorityA < priorityB) {
        return -1;
    }

    return 0;
}

opendxp.helpers.documentTypeHasSpecificRole = function(documentType, role) {

    return opendxp.settings.document_types_configuration[documentType][role];
}

opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled = function() {
    return opendxp?.settings?.new_admin_style;
}

opendxp.helpers.getTabBar = function (attributes) {
    let tabBar;

    if (opendxp.helpers.checkIfNewHeadbarLayoutIsEnabled()) {
        tabBar = {
            ...(() => attributes?.tabBar || {})(),
            layout: {
                pack: 'end'
            },
            defaults: {
                height: 46,
            },
            cls: 'opendxp_editor_tabbar'
        };
    } else {
        tabBar = {
            ...(() => attributes?.tabBar || {})(),
            cls: 'opendxp_editor_tabbar'
        };
    }

    let tabAttr = Object.assign(attributes, {
        tabBar: tabBar,
        tabPosition: 'top',
        region:'center',
        deferredRender:true,
        enableTabScroll:true,
        border: false,
        activeTab: 0
    });

    return new Ext.TabPanel(tabAttr);
}

// Sends an Ajax request, it is recommended to be used when doing simple calls or to third-party services, in contrast to Ext.Ajax.request which, by default, sends extra info (eg. custom headers) that are usually needed to be working within OpenDXP interface.
opendxp.helpers.sendRequest = function (
    method,
    url,
    successCallback = function (response) {},
    failureCallback = function (response) {},
    alwaysCallback = function (response) {}
) {
    const request = new XMLHttpRequest();

    request.onload = function() {
        if (this.status >= 200 && this.status < 400) {
            successCallback(this);
        } else {
            failureCallback(this);
        }
        alwaysCallback(this);
    };

    request.onerror = function () {
        failureCallback(this);
        alwaysCallback(this);
    }

    request.open(method, url);
    request.send();
};

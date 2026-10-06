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

opendxp.registerNS("opendxp.elementservice.x");

/**
 * @private
 */
opendxp.elementservice.deleteElement = function (options) {
    var elementType = options.elementType;
    var url = Routing.getBaseUrl() + "/admin/"  + elementType + "/delete-info?";
    // check for dependencies
    Ext.Ajax.request({
        url: url,
        params: {id: options.id, type: elementType},
        success: opendxp.elementservice.deleteElementsComplete.bind(window, options)
    });
};

/**
 * @private
 */
opendxp.elementservice.deleteElementsComplete = function(options, response) {
    try {
        var res = Ext.decode(response.responseText);
        if (res.errors) {
            var message = res.batchDelete ? t('delete_error_batch') : t('delete_error');
            var hasDeleteable = true;

            if (res.itemResults) {
                var reasons = res.itemResults.filter(function (result) {
                    return !result.allowed;
                }).map(function (result) {
                    if (res.batchDelete) {
                        return htmlspecialchars(result.key + ': ' + result.reason);
                    }

                    return htmlspecialchars(result.reason);
                });

                message += "<br /><b style='display: block; text-align: center; padding: 10px 0;'>" + reasons.join('<br/>') + "</b>";

                // remove all items that are not allowed to be deleted
                res.itemResults = res.itemResults.filter(item => item.allowed);

                hasDeleteable = res.itemResults.length > 0;
            }
            Ext.MessageBox.show({
                title:t('delete'),
                msg: message,
                buttons: hasDeleteable ? Ext.Msg.OKCANCEL : Ext.Msg.CANCEL,
                icon: Ext.MessageBox.INFO,
                fn: function(r, options, button) {
                    if (button === "ok" && hasDeleteable && r.deletejobs && r.batchDelete) {
                        opendxp.elementservice.deleteElementCheckDependencyComplete.call(this, window, r, options);
                    }
                }.bind(window, res, options)
            });
        }
        else {
            opendxp.elementservice.deleteElementCheckDependencyComplete.call(this, window, res, options);
        }
    }
    catch (e) {
        console.log(e);
    }
}

/**
 * @private
 */
opendxp.elementservice.deleteElementCheckDependencyComplete = function (window, res, options) {

    try {
        let message = '';
        if (res.batchDelete) {
            message += sprintf(t('delete_message_batch'), res.itemResults.length) + "<br /><div>";
            if (res.itemResults.length > 0) {
                message += "<ul>";
                res.itemResults.forEach(function (item) {
                    message += '<li>' + htmlspecialchars(item.path) + '<b>' + htmlspecialchars(item.key) + '</b></li>';
                })
                message += "</ul>";
            }
            message += "</div>";
        } else {
            message += t('delete_message');
        }

        if (res.elementKey) {
            message += "<br /><b style='display: block; text-align: center; padding: 10px 0;'>\"" + htmlspecialchars(res.itemResults[0].path + res.elementKey) + "\"</b>";
        }
        if (res.hasDependencies) {
            message += "<br />" + t('delete_message_dependencies');
        }

        if (res['children'] > 100) {
            message += "<br /><br /><b>" + t("too_many_children_for_recyclebin") + "</b>";
        }

        if(res.itemResults[0].type === "folder") {
            message += `<br /><br /><b> ${t('delete_entire_folder_question')} </b>`;
        }

        Ext.MessageBox.show({
            title:t('delete'),
            msg: message,
            buttons: Ext.Msg.OKCANCEL ,
            icon: Ext.MessageBox.INFO ,
            fn: opendxp.elementservice.deleteElementFromServer.bind(window, res, options)
        });
    }
    catch (e) {
        console.log(e);
    }
};

/**
 * @private
 */
opendxp.elementservice.getElementTreeNames = function(elementType) {
    var treeNames = ["layout_" + elementType + "_tree"]
    if (opendxp.settings.customviews.length > 0) {
        for (var cvs = 0; cvs < opendxp.settings.customviews.length; cvs++) {
            var cv = opendxp.settings.customviews[cvs];
            if (!cv.treetype && elementType == "object" || cv.treetype == elementType) {
                treeNames.push("layout_" + elementType + "_tree_" + cv.id);
            }
        }
    }
    return treeNames;
};

/**
 * @private
 */
opendxp.elementservice.deleteElementFromServer = function (r, options, button) {

    if (button == "ok" && r.deletejobs) {
        var successHandler = options["success"];
        var elementType = options.elementType;
        var id = options.id;
        const preDeleteEventName = 'preDelete' + elementType.charAt(0).toUpperCase() + elementType.slice(1);

        let ids = Ext.isString(id) ? id.split(',') : [id];
        try {
            ids.forEach(function (elementId) {
                const preDeleteEvent = new CustomEvent(opendxp.events[preDeleteEventName], {
                    detail: {
                        elementId: elementId
                    },
                    cancelable: true
                });

                const isAllowed = document.dispatchEvent(preDeleteEvent);
                if (!isAllowed) {
                    r.deletejobs = r.deletejobs.filter((job) => job[0].params.id != elementId);
                    ids = ids.filter((id) => id != elementId);
                }
            });
        } catch (e) {
            opendxp.helpers.showPrettyError('asset', t("error"), t("delete_failed"), e.message);
            return;
        }

        ids.forEach(function (elementId) {
            opendxp.helpers.addTreeNodeLoadingIndicator(elementType, elementId);
        });

        var affectedNodes = opendxp.elementservice.getAffectedNodes(elementType, id);
        for (var index = 0; index < affectedNodes.length; index++) {
            var node = affectedNodes[index];
            if (node) {
                var nodeEl = Ext.fly(node.getOwnerTree().getView().getNodeByRecord(node));
                if(nodeEl) {
                    nodeEl.addCls("opendxp_delete");
                }
            }
        }

        if (opendxp.globalmanager.exists(elementType + "_" + id)) {
            var tabPanel = Ext.getCmp("opendxp_panel_tabs");
            tabPanel.remove(elementType + "_" + id);
        }

        if(r.deletejobs.length > 2) {
            this.deleteProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            this.deleteWindow = new Ext.Window({
                title: t("delete"),
                layout:'fit',
                width:200,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                items: [this.deleteProgressBar],
                listeners: opendxp.helpers.getProgressWindowListeners()
            });

            this.deleteWindow.show();
        }

        var pj = new opendxp.tool.paralleljobs({
            success: function (id, successHandler) {
                var refreshParentNodes = [];
                const postDeleteEventName = 'postDelete' + elementType.charAt(0).toUpperCase() + elementType.slice(1);
                for (var index = 0; index < affectedNodes.length; index++) {
                    var node = affectedNodes[index];
                    try {
                        if (node) {
                            refreshParentNodes[node.parentNode.id] = node.parentNode.id;
                        }
                    } catch (e) {
                        console.log(e);
                        opendxp.helpers.showNotification(t("error"), t("error_deleting_item"), "error");
                        if (node) {
                            tree.getStore().load({
                                node: node.parentNode
                            });
                        }
                    }
                }

                for (var parentNodeId in refreshParentNodes) {
                    opendxp.elementservice.refreshNodeAllTrees(elementType, parentNodeId);
                }

                if(this.deleteWindow) {
                    this.deleteWindow.close();
                }

                this.deleteProgressBar = null;
                this.deleteWindow = null;

                ids.forEach(function (elementId) {
                    const postDeleteEvent = new CustomEvent(opendxp.events[postDeleteEventName], {
                        detail: {
                            elementId: elementId
                        }
                    });

                    document.dispatchEvent(postDeleteEvent);
                });

                if(typeof successHandler == "function") {
                    successHandler();
                }
            }.bind(this, id, successHandler),
            update: function (currentStep, steps, percent, response) {
                if(this.deleteProgressBar) {
                    var status = currentStep / steps;
                    this.deleteProgressBar.updateProgress(status, percent + "%");
                }

                if(response && response['deleted']) {
                    var ids = Object.keys(response['deleted']);
                    ids.forEach(function (id) {
                        opendxp.helpers.closeElement(id, elementType);
                    })
                }
            }.bind(this),
            failure: function (id, message) {
                if (this.deleteWindow) {
                    this.deleteWindow.close();
                }

                opendxp.helpers.showNotification(t("error"), t("error_deleting_item"), "error", t(message));
                for (var index = 0; index < affectedNodes.length; index++) {
                    try {
                        var node = affectedNodes[i];
                        if (node) {
                            tree.getStore().load({
                                node: node.parentNode
                            });
                        }
                    } catch (e) {
                        console.log(e);
                    }
                }
            }.bind(this, id),
            jobs: r.deletejobs
        });
    }
};

/**
 * @private
 */
opendxp.elementservice.updateAsset = function (id, data, callback) {

    if (!callback) {
        callback = function() {
        };
    }

    data.id = id;

    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_asset_update'),
        method: "PUT",
        params: data,
        success: callback
    });
};

opendxp.elementservice.updateDocument = function (id, data, callback) {

    if (!callback) {
        callback = function() {
        };
    }

    data.id = id;

    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_document_document_update'),
        method: "PUT",
        params: data,
        success: callback
    });
};

opendxp.elementservice.updateObject = function (id, values, callback) {

    if (!callback) {
        callback = function () {
        };
    }

    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_dataobject_dataobject_update'),
        method: "PUT",
        params: {
            id: Ext.encode(id),
            values: Ext.encode(values)
        },
        success: callback
    });
};

opendxp.elementservice.getAffectedNodes = function(elementType, id) {

    var ids = Ext.isString(id) ? id.split(',') : [id];
    var treeNames = opendxp.elementservice.getElementTreeNames(elementType);
    var affectedNodes = [];
    for (var index = 0; index < treeNames.length; index++) {
        var treeName = treeNames[index];
        var tree = opendxp.globalmanager.get(treeName);
        if (!tree) {
            continue;
        }
        tree = tree.tree;
        var store = tree.getStore();

        ids.forEach(function (id) {
            var record = store.getNodeById(id);
            if (record) {
                affectedNodes.push(record);
            }
        });
    }

    const prepareAffectedNodes = new CustomEvent(opendxp.events.prepareAffectedNodes, {
        detail: {
            affectedNodes: affectedNodes,
            id: id,
            elementType: elementType
        }
    });
    document.dispatchEvent(prepareAffectedNodes);

    return affectedNodes;
};


opendxp.elementservice.applyNewKey = function(affectedNodes, elementType, id, value) {
    value = Ext.util.Format.htmlEncode(value);
    for (var index = 0; index < affectedNodes.length; index++) {
        var record = affectedNodes[index];
        record.set("text", value);
        record.set("path", record.data.basePath + value);
    }
    opendxp.helpers.addTreeNodeLoadingIndicator(elementType, id);

    return affectedNodes;
};

opendxp.elementservice.editDocumentKeyComplete =  function (options, button, value, object) {
    if (button == "ok") {

        var record;
        var id = options.id;
        var elementType = options.elementType;
        value = opendxp.helpers.getValidFilename(value, "document");

        if (options.sourceTree) {
            var tree = options.sourceTree;
            var store = tree.getStore();
            record = store.getById(id);
            if(opendxp.elementservice.isKeyExistingInLevel(record.parentNode, value, record)) {
                return;
            }
            if(opendxp.elementservice.isDisallowedDocumentKey(record.parentNode.id, value)) {
                return;
            }
        }

        var originalText;
        var originalPath;
        var affectedNodes = opendxp.elementservice.getAffectedNodes(elementType, id);
        if (affectedNodes) {
            record = affectedNodes[0];
            if(record) {
                originalText = record.get("text");
                originalPath = record.get("path");
            }
        }
        opendxp.elementservice.applyNewKey(affectedNodes, elementType, id, value);

        opendxp.elementservice.updateDocument(id, {
            key: value
        }, function (response) {
            var record, index;
            var rdata = Ext.decode(response.responseText);
            if (!rdata || !rdata.success) {
                for (index = 0; index < affectedNodes.length; index++) {
                    record = affectedNodes[index];
                    record.set("text", originalText);
                    record.set("path", originalPath);
                }
                opendxp.helpers.showNotification(t("error"), t("error_renaming_item"), "error",
                    t(rdata.message));
                return;
            }

            if(rdata && rdata.success) {
                // removes loading indicator added in the applyNewKey method
                opendxp.helpers.removeTreeNodeLoadingIndicator(elementType, id);
            }

            for (index = 0; index < affectedNodes.length; index++) {
                record = affectedNodes[index];
                opendxp.elementservice.refreshNode(record);
            }

            try {
                if (rdata && rdata.success) {
                    if (rdata.treeData) {
                        opendxp.helpers.updateTreeElementStyle('document', id, rdata.treeData);
                    }

                    opendxp.elementservice.reopenElement(options);

                    //trigger edit document key complete event
                    const postEditDocumentKey = new CustomEvent(opendxp.events.postEditDocumentKey, {
                        detail: {
                            document: record,
                            key: value
                        }
                    });

                    document.dispatchEvent(postEditDocumentKey);
                }  else {
                    const message = typeof rdata.message !== 'undefined' ? t(rdata.message) : '';
                    opendxp.helpers.showNotification(t("error"), t("error_renaming_item"), "error", message);
                }
            } catch (e) {
                opendxp.helpers.showNotification(t("error"), t("error_renaming_item"), "error");
            }
        }.bind(this));
    }
};

opendxp.elementservice.editObjectKeyComplete = function (options, button, value, object) {
    if (button == "ok") {

        var record;
        var id = options.id;
        var elementType = options.elementType;
        value = opendxp.helpers.getValidFilename(value, "object");

        if (options.sourceTree) {
            var tree = options.sourceTree;
            var store = tree.getStore();
            record = store.getById(id);
            if(opendxp.elementservice.isKeyExistingInLevel(record.parentNode, value, record)) {
                return;
            }
        }

        var affectedNodes = opendxp.elementservice.getAffectedNodes(elementType, id);
        if (affectedNodes) {
            record = affectedNodes[0];
            if(record) {
                originalText = record.get("text");
                originalPath = record.get("path");
            }
        }
        opendxp.elementservice.applyNewKey(affectedNodes, elementType, id, value);

        opendxp.elementservice.updateObject(id, {key: value},
            function (response) {
                var index, record;
                for (index = 0; index < affectedNodes.length; index++) {
                    record = affectedNodes[index];
                    opendxp.elementservice.refreshNode(record);
                }

                try {
                    var rdata = Ext.decode(response.responseText);
                    if (rdata && rdata.success) {
                        if (rdata.treeData) {
                            opendxp.helpers.updateTreeElementStyle('object', id, rdata.treeData);
                        }

                        opendxp.elementservice.reopenElement(options);
                        // removes loading indicator added in the applyNewKey method
                        opendxp.helpers.removeTreeNodeLoadingIndicator(elementType, id);

                        //trigger edit object key complete event
                        const postEditObjectKey = new CustomEvent(opendxp.events.postEditObjectKey, {
                            detail: {
                                object: record,
                                key: value
                            }
                        });

                        document.dispatchEvent(postEditObjectKey);
                    }  else {
                        const message = typeof rdata.message !== 'undefined' ? t(rdata.message) : '';
                        opendxp.helpers.showNotification(t("error"), t("error_renaming_item"), "error", message);
                        for (index = 0; index < affectedNodes.length; index++) {
                            record = affectedNodes[index];
                            opendxp.elementservice.refreshNode(record.parentNode);
                        }
                    }
                } catch (e) {
                    opendxp.helpers.showNotification(t("error"), t("error_renaming_item"), "error");
                    for (index = 0; index < affectedNodes.length; index++) {
                        record = affectedNodes[index];
                        opendxp.elementservice.refreshNode(record.parentNode);
                    }
                }
            }.bind(this))
        ;
    }
};

opendxp.elementservice.reopenElement = function(options) {
    var elementType = options.elementType;
    if (opendxp.globalmanager.exists(elementType + "_" + options.id)) {
        opendxp.helpers["close"  + ucfirst(elementType)](options.id);
        opendxp.helpers["open" + ucfirst(elementType)](options.id, options.elementSubType);
    }

};

opendxp.elementservice.editAssetKeyComplete = function (options, button, value, object) {
    try {
        if (button == "ok") {
            var record;
            var id = options.id;
            var elementType = options.elementType;

            value = opendxp.helpers.getValidFilename(value, "asset");

            if (options.sourceTree) {
                var tree = options.sourceTree;
                var store = tree.getStore();
                record = store.getById(id);
                // check for ident filename in current level

                var parentChildren = record.parentNode.childNodes;
                for (var i = 0; i < parentChildren.length; i++) {
                    if (parentChildren[i].data.text == value && this != parentChildren[i].data.text) {
                        Ext.MessageBox.alert(t('rename'), t('name_already_in_use'));
                        return;
                    }
                }
            }

            var affectedNodes = opendxp.elementservice.getAffectedNodes(elementType, id);
            if (affectedNodes) {
                record = affectedNodes[0];
                if(record) {
                    originalText = record.get("text");
                    originalPath = record.get("path");
                }
            }
            opendxp.elementservice.applyNewKey(affectedNodes, elementType, id, value);

            opendxp.elementservice.updateAsset(id, {filename: value},
                function (response) {
                    var index, record;
                    var rdata = Ext.decode(response.responseText);
                    if (!rdata || !rdata.success) {
                        for (index = 0; index < affectedNodes.length; index++) {
                            record = affectedNodes[index];
                            record.set("text", originalText);
                            record.set("path", originalPath);
                        }

                        const message = typeof rdata.message !== 'undefined' ? t(rdata.message) : '';
                        opendxp.helpers.showNotification(t("error"), t("error_renaming_item"),
                        "error", message);
                        return;
                    }

                    if (rdata && rdata.success) {
                        // removes loading indicator added in the applyNewKey method
                        opendxp.helpers.removeTreeNodeLoadingIndicator(elementType, id);
                    }

                    for (index = 0; index < affectedNodes.length; index++) {
                        record = affectedNodes[index];
                        opendxp.elementservice.refreshNode(record);
                    }

                    try {
                        if (rdata && rdata.success) {
                            if (rdata.treeData) {
                                opendxp.helpers.updateTreeElementStyle('asset', id, rdata.treeData);
                            }

                            opendxp.elementservice.reopenElement(options);

                            //trigger edit asset key complete event
                            const postEditAssetKey = new CustomEvent(opendxp.events.postEditAssetKey, {
                                detail: {
                                    asset: record,
                                    key: value
                                }
                            });

                            document.dispatchEvent(postEditAssetKey);
                        }  else {
                            const message = typeof rdata.message !== 'undefined' ? t(rdata.message) : '';
                            opendxp.helpers.showNotification(t("error"), t("error_renaming_item"),
                                "error", message);
                        }
                    } catch (e) {
                        opendxp.helpers.showNotification(t("error"), t("error_renaming_item"),
                            "error");
                    }
                }.bind(this))
            ;
        }
    } catch (e) {
        console.log(e);
    }
};

opendxp.elementservice.editElementKey = function(options) {
    var completeCallback;
    if (options.elementType == "asset") {
        completeCallback = opendxp.elementservice.editAssetKeyComplete.bind(this, options);
    } else if (options.elementType == "document") {
        completeCallback = opendxp.elementservice.editDocumentKeyComplete.bind(this, options);
    } else if (options.elementType == "object") {
        completeCallback = opendxp.elementservice.editObjectKeyComplete.bind(this, options);
    } else {
        throw new Error("type " + options.elementType + " not supported!");
    }

    if(
        options['elementType'] === 'document' &&
        (options['elementSubType'] === 'page' || options['elementSubType'] === 'hardlink') &&
        opendxp.globalmanager.get("user").isAllowed('redirects')
    ) {
        // for document pages & hardlinks we need an additional checkbox for auto-redirects
        var messageBox = null;
        completeCallback = opendxp.elementservice.editDocumentKeyComplete.bind(this);
        var submitFunction = function () {
            completeCallback(options, 'ok', messageBox.getComponent('key').getValue());
            messageBox.close();
        };

        messageBox = new Ext.Window({
            modal: true,
            width: 500,
            title: t('rename'),
            items: [{
                xtype: 'container',
                html: t('please_enter_the_new_name')
            }, {
                xtype: "textfield",
                width: "100%",
                name: 'key',
                itemId: 'key',
                value: options.default,
                listeners: {
                    afterrender: function () {
                        window.setTimeout(function () {
                            this.focus(true);
                        }.bind(this), 100);
                    }
                }
            }],
            bodyStyle: 'padding: 10px 10px 0px 10px',
            buttonAlign: 'center',
            buttons: [{
                text: t('OK'),
                handler: submitFunction
            },{
                text: t('cancel'),
                handler: function() {
                    messageBox.close();
                }
            }]
        });

        messageBox.show();

        var map = new Ext.util.KeyMap({
            target: messageBox.getEl(),
            key:  Ext.event.Event.ENTER,
            fn: submitFunction
        });
    } else {
        Ext.MessageBox.prompt(t('rename'), t('please_enter_the_new_name'), completeCallback, window, false, options.default);
    }
};


opendxp.elementservice.refreshNode = function (node) {
    var ownerTree = node.getOwnerTree();

    node.data.expanded = true;
    ownerTree.getStore().load({
        node: node
    });
};


opendxp.elementservice.isDisallowedDocumentKey = function (parentNodeId, key) {

    if(parentNodeId == 1) {
        var disallowedKeys = ["admin","install","plugin"];
        if(in_arrayi(key, disallowedKeys)) {
            Ext.MessageBox.alert(t('name_is_not_allowed'),
                t('name_is_not_allowed'));
            return true;
        }
    }
    return false;
};

opendxp.elementservice.isKeyExistingInLevel = function(parentNode, key, node) {

    key = opendxp.helpers.getValidFilename(key, parentNode.data.elementType);
    var parentChildren = parentNode.childNodes;
    for (var i = 0; i < parentChildren.length; i++) {
        if (parentChildren[i].data.text == key && node != parentChildren[i]) {
            Ext.MessageBox.alert(t('error'),
                t('name_already_in_use'));
            return true;
        }
    }
    return false;
};

opendxp.elementservice.addObject = function(options) {

    var url = options.url;
    delete options.url;
    delete options["sourceTree"];

    Ext.Ajax.request({
        url: url,
        method: 'POST',
        params: options,
        success: opendxp.elementservice.addObjectComplete.bind(this, options)
    });
};

opendxp.elementservice.addDocument = function(options) {

    var url = options.url;
    delete options.url;
    delete options["sourceTree"];

    Ext.Ajax.request({
        url: url,
        method: 'POST',
        params: options,
        success: opendxp.elementservice.addDocumentComplete.bind(this, options)
    });
};

opendxp.elementservice.refreshRootNodeAllTrees = function(elementType) {
    var treeNames = opendxp.elementservice.getElementTreeNames(elementType);
    for (var index = 0; index < treeNames.length; index++) {
        try {
            var treeName = treeNames[index];
            var tree = opendxp.globalmanager.get(treeName);
            if (!tree) {
                continue;
            }
            tree = tree.tree;
            var rootNode = tree.getRootNode();
            if (rootNode) {
                opendxp.elementservice.refreshNode(rootNode);
            }
        } catch (e) {
            console.log(e);
        }
    }
};



opendxp.elementservice.refreshNodeAllTrees = function(elementType, id) {
    var treeNames = opendxp.elementservice.getElementTreeNames(elementType);
    for (var index = 0; index < treeNames.length; index++) {
        try {
            var treeName = treeNames[index];
            var tree = opendxp.globalmanager.get(treeName);
            if (!tree) {
                continue;
            }
            tree = tree.tree;
            var store = tree.getStore();
            var parentRecord = store.getById(id);
            if (parentRecord) {
                parentRecord.data.leaf = false;
                parentRecord.expand();
                opendxp.elementservice.refreshNode(parentRecord);
            }
        } catch (e) {
            console.log(e);
        }
    }
};

opendxp.elementservice.addDocumentComplete = function (options, response) {
    try {
        response = Ext.decode(response.responseText);
        if (response && response.success) {
            opendxp.elementservice.refreshNodeAllTrees(options.elementType, options.parentId);

            let docTypes = opendxp.globalmanager.get('document_valid_types');
            if (in_array(response["type"], docTypes)) {
                opendxp.helpers.openDocument(response.id, response.type);

                const postAddDocumentTree = new CustomEvent(opendxp.events.postAddDocumentTree, {
                    detail: {
                        id: response.id,
                    }
                });

                document.dispatchEvent(postAddDocumentTree);

            }
        }  else {
            opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error",
                t(response.message));
        }
    } catch(e) {
        opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error");
    }
};

opendxp.elementservice.addObjectComplete = function(options, response) {
    try {
        var rdata = Ext.decode(response.responseText);
        if (rdata && rdata.success) {
            opendxp.elementservice.refreshNodeAllTrees(options.elementType, options.parentId);

            if (rdata.id && rdata.type) {
                if (rdata.type == "object") {
                    opendxp.helpers.openObject(rdata.id, rdata.type);

                    const postAddObjectTree = new CustomEvent(opendxp.events.postAddObjectTree, {
                        detail: {
                            id: rdata.id,
                        }
                    });

                    document.dispatchEvent(postAddObjectTree);
                }
            }
        }  else {
            opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error", t(rdata.message));
        }
    } catch (e) {
        opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error");
    }

};


opendxp.elementservice.lockElement = function(options) {
    try {
        var updateMethod = opendxp.elementservice["update" + ucfirst(options.elementType)];
        updateMethod(options.id,
            {
                locked: options.mode
            },
            function() {
                opendxp.elementservice.refreshRootNodeAllTrees(options.elementType);
            }
        );
    } catch (e) {
        console.log(e);
    }
};

opendxp.elementservice.unlockElement = function(options) {
    try {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_element_unlockpropagate'),
            method: 'PUT',
            params: {
                id: options.id,
                type: options.elementType
            },
            success: function () {
                opendxp.elementservice.refreshRootNodeAllTrees(options.elementType);
            }.bind(this)
        });
    } catch (e) {
        console.log(e);
    }
};

opendxp.elementservice.setElementPublishedState = function(options) {
    var elementType = options.elementType;
    var id = options.id;
    var published = options.published;

    var affectedNodes = opendxp.elementservice.getAffectedNodes(elementType, id);
    for (var index = 0; index < affectedNodes.length; index++) {
        try {
            var node = affectedNodes[index];
            if (node) {
                var tree = node.getOwnerTree();
                var view = tree.getView();
                var nodeEl = Ext.fly(view.getNodeByRecord(node));
                if (nodeEl) {
                    var nodeElInner = nodeEl.down(".x-grid-td");
                    if (nodeElInner) {
                        if (published) {
                            nodeElInner.removeCls("opendxp_unpublished");
                        } else {
                            nodeElInner.addCls("opendxp_unpublished");
                        }
                    }
                }

                if(!node.data['cls']) {
                    node.data['cls'] = '';
                }

                if (published) {
                    node.data.cls = node.data.cls.replace(/opendxp_unpublished/g, '');
                } else {
                    node.data.cls += " opendxp_unpublished";
                }

                node.data.published = published;
            }
        } catch (e) {
            console.log(e);
        }
    }
};

opendxp.elementservice.setElementToolbarButtons = function(options) {
    var elementType = options.elementType;
    var id = options.id;
    var key = elementType + "_" + id;
    if (opendxp.globalmanager.exists(key)) {
        if (options.published) {
            opendxp.globalmanager.get(key).toolbarButtons.unpublish.show();
        } else {
            opendxp.globalmanager.get(key).toolbarButtons.unpublish.hide();
        }
    }
};

opendxp.elementservice.reloadVersions = function(options) {
    var elementType = options.elementType;
    var id = options.id;
    var key = elementType + "_" + id;

    if (opendxp.globalmanager.exists(key)) {
        // reload versions
        if (opendxp.globalmanager.get(key).versions) {
            if (typeof opendxp.globalmanager.get(key).versions.reload  == "function") {
                opendxp.globalmanager.get(key).versions.reload();
            }
        }
    }
};

opendxp.elementservice.showLocateInTreeButton = function(elementType) {
    var locateConfigs = opendxp.globalmanager.get("tree_locate_configs");

    if (locateConfigs[elementType]) {
        return true;
    }
    return false;
};

opendxp.elementservice.integrateWorkflowManagement = function(elementType, elementId, elementEditor, buttons) {

    if(elementEditor.data.workflowManagement && elementEditor.data.workflowManagement.hasWorkflowManagement === true) {

        var workflows = elementEditor.data.workflowManagement.workflows;

        if(workflows.length > 0) {

            var button = opendxp.elementservice.getWorkflowActionsButton(workflows, elementType, elementId, elementEditor);

            if(button !== false) {
                buttons.push("-");
                buttons.push(button);
            }
        }

        buttons.push("-");
        buttons.push({
            xtype: 'container',
            html: [
                elementEditor.data.workflowManagement.statusInfo
            ]
        });

    }

};

opendxp.elementservice.getWorkflowActionsButton = function(workflows, elementType, elementId, elementEditor) {
    var workflowsWithTransitions = [];

    workflows.forEach(function(el){

        if(el.allowedTransitions.length) {
            workflowsWithTransitions.push(el);
        } else if(el.globalActions.length) {
            workflowsWithTransitions.push(el);
        }
    }.bind(workflowsWithTransitions));

    if(workflowsWithTransitions.length > 0) {

        var items = [];

        var workflowTransitionHandler = function (workflow, transition, elementEditor, elementId, elementType) {
            var applyWorkflow = function (workflow, transition, elementEditor, elementId, elementType) {
                if (transition.notes) {
                    new opendxp.workflow.transitionPanel(elementType, elementId, elementEditor, workflow.name, transition);
                } else {
                    opendxp.workflow.transitions.perform(elementType, elementId, elementEditor, workflow.name, transition);
                }
            };

            if (elementEditor.isDirty()) {
                if (transition.unsavedChangesBehaviour === 'warn') {
                    opendxp.helpers.showNotification(t("error"), t("workflow_transition_unsaved_data"), "error");
                } else if (transition.unsavedChangesBehaviour === 'save') {
                    elementEditor.save(null, null, null, function () {
                        applyWorkflow(workflow, transition, elementEditor, elementId, elementType);
                    });
                } else {
                    applyWorkflow(workflow, transition, elementEditor, elementId, elementType);
                }
            } else {
                applyWorkflow(workflow, transition, elementEditor, elementId, elementType);
            }
        };

        workflowsWithTransitions.forEach(function (workflow) {
            if (workflowsWithTransitions.length > 1) {
                items.push({
                    xtype: 'container',
                    html: '<span class="opendxp-workflow-action-workflow-label">' + t(workflow.label) + '</span>'
                });
            }

            for (i = 0; i < workflow.allowedTransitions.length; i++) {
                var transition = workflow.allowedTransitions[i];
                transition.isGlobalAction = false;
                items.push({
                    text: t(transition.label),
                    iconCls: transition.iconCls,
                    handler: function (workflow, transition) {
                        workflowTransitionHandler(workflow, transition, elementEditor, elementId, elementType);
                    }.bind(this, workflow, transition)
                });
            }


            for (i = 0; i < workflow.globalActions.length; i++) {
                var transition = workflow.globalActions[i];
                transition.isGlobalAction = true;
                items.push({
                    text: t(transition.label),
                    iconCls: transition.iconCls,
                    handler: function (workflow, transition) {
                        workflowTransitionHandler(workflow, transition, elementEditor, elementId, elementType);
                    }.bind(this, workflow, transition)
                });
            }
        });

        return {
            text: t('actions'),
            scale: "medium",
            iconCls: 'opendxp_material_icon_workflow opendxp_material_icon',
            cls: 'opendxp_workflow_button',
            menu: {
                xtype: 'menu',
                items: items
            }
        };
    }

    return false;
};

opendxp.elementservice.replaceAsset = function (id, callback) {
    opendxp.helpers.uploadDialog(Routing.generate('opendxp_admin_asset_replaceasset', {id: id}), "Filedata", function() {
        if(typeof callback == "function") {
            callback();
        }
    }.bind(this), function (res) {
        var message = false;
        try {
            var response = Ext.util.JSON.decode(res.response.responseText);
            if(response.message) {
                message = response.message;
            }

        } catch(e) {}

        Ext.MessageBox.alert(t("error"), message || t("error"));
    });
};


/**
 * The menu entries that download a folder, or the assets selected in it, as a ZIP file. The selected IDs are read when
 * an entry is clicked, so a list can hand over a function that returns its current selection.
 */
opendxp.elementservice.getAssetZipDownloadMenu = function (id, getSelectedIds) {
    getSelectedIds = getSelectedIds || function () {
        return null;
    };

    return [{
        text: t("originals"),
        iconCls: "opendxp_icon_zip opendxp_icon_overlay_download",
        handler: function () {
            const selectedIds = getSelectedIds();
            if (selectedIds !== false) {
                opendxp.elementservice.downloadAssetFolderAsZip(id, selectedIds);
            }
        }
    }, {
        text: t("thumbnails"),
        iconCls: "opendxp_icon_image opendxp_icon_overlay_download",
        handler: function () {
            const selectedIds = getSelectedIds();
            if (selectedIds !== false) {
                opendxp.elementservice.downloadAssetImagesAsThumbnailZip(id, selectedIds);
            }
        }
    }];
};

opendxp.elementservice.downloadAssetImagesAsThumbnailZip = function (id, selectedIds) {
    const idsParam = selectedIds && selectedIds.length ? selectedIds.join(',') : '';

    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_asset_downloadaszipimagecount'),
        params: {
            id: id,
            selectedIds: idsParam
        },
        success: function (response) {
            if (Ext.decode(response.responseText).count === 0) {
                Ext.Msg.alert(t('error'), t(idsParam ? 'zip_selection_without_images' : 'zip_folder_without_images'));
                return;
            }

            const thumbnailStore = new Ext.data.JsonStore({
                autoDestroy: true,
                proxy: {
                    type: 'ajax',
                    url: Routing.generate('opendxp_admin_settings_thumbnaildownloadable')
                },
                fields: ['id']
            });

            thumbnailStore.load(function (records) {
                if (records.length === 0) {
                    Ext.Msg.alert(t('error'), t('no_downloadable_thumbnail'));
                    return;
                }

                const thumbnailCombo = new Ext.form.ComboBox({
                    fieldLabel: t("thumbnail"),
                    store: thumbnailStore,
                    displayField: "id",
                    valueField: "id",
                    queryMode: 'local',
                    editable: false,
                    forceSelection: true,
                    value: records[0].get('id'),
                    width: 350
                });

                const dialog = new Ext.Window({
                    title: t("thumbnails_as_zip"),
                    modal: true,
                    resizable: false,
                    bodyStyle: "padding: 10px;",
                    items: [thumbnailCombo],
                    buttons: [{
                        text: t("download"),
                        iconCls: "opendxp_icon_download",
                        handler: function () {
                            const thumbnail = thumbnailCombo.getValue();
                            dialog.close();
                            opendxp.elementservice.downloadAssetFolderAsZip(id, selectedIds, thumbnail);
                        }
                    }]
                });

                dialog.show();
            });
        }
    });
};

opendxp.elementservice.downloadAssetFolderAsZip = function (id, selectedIds, thumbnail) {

    var that = {};

    var idsParam = '';
    if(selectedIds && selectedIds.length) {
        idsParam = selectedIds.join(',');
    }

    Ext.Ajax.request({
        url: Routing.generate('opendxp_admin_asset_downloadaszipjobs'),
        params: {
            id: id,
            selectedIds: idsParam,
            thumbnail: thumbnail || ''
        },
        success: function(response) {
            var res = Ext.decode(response.responseText);

            that.downloadProgressBar = new Ext.ProgressBar({
                text: t('initializing')
            });

            that.downloadProgressWin = new Ext.Window({
                title: t("download_as_zip"),
                layout:'fit',
                width:200,
                bodyStyle: "padding: 10px;",
                closable:false,
                plain: true,
                items: [that.downloadProgressBar],
                listeners: opendxp.helpers.getProgressWindowListeners()
            });

            that.downloadProgressWin.show();


            var pj = new opendxp.tool.paralleljobs({
                success: function () {
                    if(that.downloadProgressWin) {
                        that.downloadProgressWin.close();
                    }

                    that.downloadProgressBar = null;
                    that.downloadProgressWin = null;

                    opendxp.helpers.download(Routing.generate('opendxp_admin_asset_downloadaszip', thumbnail ? {jobId: res.jobId, id: id, thumbnail: thumbnail} : {jobId: res.jobId, id: id}));
                },
                update: function (currentStep, steps, percent) {
                    if(that.downloadProgressBar) {
                        var status = currentStep / steps;
                        that.downloadProgressBar.updateProgress(status, percent + "%");
                    }
                },
                failure: function (message) {
                    that.downloadProgressWin.close();
                    opendxp.helpers.showNotification(t("error"), t("error"),
                        "error", t(message));
                },
                jobs: res.jobs
            });
        }
    });
};

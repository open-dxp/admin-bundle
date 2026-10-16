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

opendxp.registerNS("opendxp.object.classificationstore.columnConfigDialog");
/**
 * @private
 */
opendxp.object.classificationstore.columnConfigDialog = Class.create({

    keysAdded: 0,
    requestIsPending: false,

    getConfigDialog: function(ownerTree, node, selectionPanel, callback) {
        this.ownerTree = ownerTree;
        this.node = node;
        this.selectionPanel = selectionPanel;
        this.callback = callback;

        var selectionWindow = new opendxp.object.classificationstore.relationSelectionWindow(this, node.data.layout.storeId);
        selectionWindow.show();
    },


    handleSelectionWindowClosed: function() {
        if (this.keysAdded == 0 && !this.requestIsPending) {
            // no keys added, remove the node
            this.node.remove();
        }
    },

    requestPending: function() {
        this.requestIsPending = true;
    },

    handleAddKeys: function (response) {
        var data = Ext.decode(response.responseText);

        var originalKey =  this.node.data.key;

        if(data && data.success) {
            for (var i=0; i < data.data.length; i++) {
                var keyDef = data.data[i];

                var encodedKey = "~classificationstore~" + originalKey + "~" +  keyDef.groupId + "-" + keyDef.keyId;

                if (this.selectionPanel.getRootNode().findChild("key", encodedKey)) {
                    // key already exists, continue
                    continue;
                }

                if (this.keysAdded > 0) {
                    var configEncoded = Ext.encode(this.node.data);
                    var configDecoded = Ext.decode(configEncoded);
                    delete configDecoded.id;
                    delete configDecoded.options;
                    delete configDecoded.layout.gridType;

                    var copy = Ext.apply({}, configDecoded); // copy it

                    this.node = this.selectionPanel.getRootNode().createNode(copy);
                }


                this.node.set("key", encodedKey);
                this.node.data.layout.gridType = keyDef.type;

                if (keyDef.type == "select") {
                    this.node.data.layout.options = Ext.decode(keyDef.possiblevalues);
                }

                this.node.set("text", "#" + keyDef.keyName);
                this.node.set("layout", keyDef.layout);
                this.node.set("dataType", keyDef.layout.fieldtype);

                if (this.keysAdded > 0) {
                    this.selectionPanel.getRootNode().appendChild(this.node);
                }
                this.keysAdded++;
            }
        }

        if (this.keysAdded == 0) {
            this.node.remove();
        } else if (this.callback) {
            this.callback();
        }
    }
});

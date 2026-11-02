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

opendxp.registerNS("opendxp.object.tags.block");
/**
 * @private
 */
opendxp.object.tags.block = Class.create(opendxp.object.tags.abstract, {

    type: "block",
    dirty: false,

    initialize: function (data, fieldConfig) {

        this.dirty = false;
        this.data = [];
        this.currentElements = [];
        this.layoutDefinitions = {};
        this.dataFields = {};

        if (data) {
            this.data = data;
        }
        this.fieldConfig = {};
        Ext.apply(this.fieldConfig, fieldConfig);
    },

    getGridColumnConfig: function(field) {
        return {text: t(field.label), width: 150, sortable: false, dataIndex: field.key,
            renderer: function (key, value, metaData, record) {
                this.applyPermissionStyle(key, value, metaData, record);

                return t("not_supported");
            }.bind(this, field.key)};
    },


    getLayoutEdit: function () {
        this.fieldConfig.datatype ="layout";
        this.fieldConfig.fieldtype = "panel";

        var panelConf = {
            autoHeight: true,
            border: true,
            style: "margin-bottom: 10px",
            componentCls: this.getWrapperClassNames(),
            collapsible: this.fieldConfig.collapsible,
            collapsed: this.fieldConfig.collapsed
        };
        if(this.fieldConfig.title) {
            panelConf.title = this.fieldConfig.title;
        }

        this.component = new Ext.Panel(panelConf);

        // The server resolves oIndex against the last saved version, also an auto save. oIndex must follow its order.
        const postSave = function (event) {
            if (event.detail.object === this.object) {
                this.component.items.each(function (item, index) {
                    item.opendxp_oIndex = index;
                });
            }
        }.bind(this);

        document.addEventListener(opendxp.events.postSaveObject, postSave);

        this.component.on('destroy', function () {
            document.removeEventListener(opendxp.events.postSaveObject, postSave);
        });

        this.component.addListener("render", function() {
            if(this.object.data.metaData[this.getName()] && this.object.data.metaData[this.getName()].hasParentValue) {
                this.addInheritanceSourceButton(this.object.data.metaData[this.getName()]);
            }
        }.bind(this));

        this.initData();

        return this.component;
    },

    initData: function () {

        if(this.data.length < 1) {
            this.component.add(this.getControls());
        } else {
            Ext.suspendLayouts();
            for (var i=0; i<this.data.length; i++) {
                this.addBlockElement(
                    i,
                    {
                        oIndex: this.data[i].oIndex
                    },
                    this.data[i].data,
                    true);
            }
            Ext.resumeLayouts();
        }

        this.component.updateLayout();
    },

    getControls: function (blockElement) {
        var items = [];

        if(blockElement) {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "opendxp_block_button_plus",
                iconCls: "opendxp_icon_plus_up",
                handler: this.addBlock.bind(this, blockElement, "before")
            });

            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "opendxp_block_button_plus",
                iconCls: "opendxp_icon_plus_down",
                handler: this.addBlock.bind(this, blockElement, "after")
            });

            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "opendxp_block_button_minus",
                iconCls: "opendxp_icon_minus",
                listeners: {
                    "click": this.removeBlock.bind(this, blockElement)
                }
            });

            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: "opendxp_block_button_up",
                iconCls: "opendxp_icon_up",
                listeners: {
                    "click": this.moveBlockUp.bind(this, blockElement)
                }
            });

            items.push({
                disabled: this.fieldConfig.disallowReorder,
                cls: "opendxp_block_button_down",
                iconCls: "opendxp_icon_down",
                listeners: {
                    "click": this.moveBlockDown.bind(this, blockElement)
                }
            });
        } else {
            items.push({
                disabled: this.fieldConfig.disallowAddRemove,
                cls: "opendxp_block_button_plus",
                iconCls: "opendxp_icon_plus",
                handler: this.addBlock.bind(this, blockElement, "after")
            });
        }

        var toolbar = new Ext.Toolbar({
            items: items
        });

        return toolbar;
    },

    detectBlockIndex: function (blockElement) {
        // detect index
        var index;

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.component.items.items[s].key == blockElement.key) {
                index = s;
                break;
            }
        }
        return index;
    },

    closeOpenEditors: function () {

        // currently just wysiwyg
        for (var i=0; i<this.currentElements.length; i++) {
            if(typeof this.currentElements[i] == "object") {
                for(var e=0; e<this.currentElements[i]["fields"].length; e++) {
                    if(typeof this.currentElements[i]["fields"][e]["close"] == "function") {
                        this.currentElements[i]["fields"][e].close();
                    }
                }
            }
        }
    },

    addBlock: function (blockElement, position) {

        this.closeOpenEditors();

        if(this.fieldConfig.maxItems) {
            var itemAmount = 0;
            for(var s=0; s<this.component.items.items.length; s++) {
                if(typeof this.component.items.items[s].key != "undefined") {
                    itemAmount++;
                }
            }

            if(itemAmount >= this.fieldConfig.maxItems) {
                Ext.MessageBox.alert(t("error"), t("limit_reached"));
                return;
            }
        }

        var index = 0;
        if(blockElement) {
            index = this.detectBlockIndex(blockElement);
        }

        if (position !== "before") {
            index++;
        }

        this.addBlockElement(index, {});
    },

    removeBlock: function (blockElement) {

        this.closeOpenEditors();

        var key = blockElement.key;
        this.currentElements[key] = "deleted";

        this.component.remove(blockElement);
        this.dirty = true;

        // check for remaining elements
        if(this.component.items.items.length < 1) {
            this.component.removeAll();
            this.component.add(this.getControls());
            this.component.updateLayout();
            this.currentElements = [];
        }
    },

    moveBlockUp: function (blockElement) {

        this.closeOpenEditors();

        this.component.moveBefore(blockElement, blockElement.previousSibling());
        this.dirty = true;
    },

    moveBlockDown: function (blockElement) {

        this.closeOpenEditors();

        this.component.moveAfter(blockElement, blockElement.nextSibling());
        this.dirty = true;
    },

    addBlockElement: function (index, config, blockData, ignoreChange) {

        var oIndex = config.oIndex;
        this.closeOpenEditors();

        // remove the initial toolbar if there is no element
        if(this.currentElements.length < 1) {
            this.component.removeAll();
        }

        this.dataFields = {};
        this.currentData = {};

        if(blockData) {
            this.currentData = blockData;
        }

        // var items = this.getRecursiveLayout(this.layoutDefinitions[type]).items;
        var fieldConfig = this.fieldConfig;

        var context = this.getContext();
        context["subContainerType"] = "block";
        context["subContainerKey"] = fieldConfig.name;
        context["applyDefaults"] = true;
        var items = this.getRecursiveLayout(fieldConfig, undefined, context, undefined, undefined, undefined, true);

        items = items.items;

        var blockElement = new Ext.Panel({
            opendxp_oIndex: oIndex,
            bodyStyle: "padding:10px;",
            style: "margin: 10px 0 10px 0;"  + this.fieldConfig.styleElement,
            manageHeight: false,
            border: false,
            items: [
                {
                    xtype: 'panel',
                    style: "margin: 10px 0 10px 0;",
                    items: items
                }
            ],
            disabled: this.fieldConfig.noteditable
        });

        blockElement.insert(0, this.getControls(blockElement));

        blockElement.key = this.currentElements.length;
        // blockElement.fieldtype = type;
        this.component.insert(index, blockElement);
        this.component.updateLayout();

        this.currentElements.push({
            container: blockElement,
            fields: this.dataFields
            // ,
            // type: type
        });

        if(!ignoreChange) {
            this.dirty = true;
        }

        this.dataFields = {};
        this.currentData = {};

        this.updateBlockIndices();
    },

    updateBlockIndices: function() {
        for (let itemIndex = 0; itemIndex < this.component.items.items.length; itemIndex++) {
            let item = this.component.items.items[itemIndex];

            for (let j = 0; j < this.currentElements.length; j++) {
                if (item !== this.currentElements[j].container) continue;

                const fields = this.currentElements[j].fields;
                for (const fieldName in fields) {
                    fields[fieldName].context.index = itemIndex;
                }
            }
        }
    },

    getDataForField: function (fieldConfig) {
        var name = fieldConfig.name;
        return this.currentData[name];
    },

    getMetaDataForField: function(fieldConfig) {
        return null;
    },

    addToDataFields: function (field, name) {
        if (this.dataFields[name]) {
            // this is especially for localized fields which get aggregated here into one field definition
            // in the case that there are more than one localized fields in the class definition
            // see also ClassDefinition::extractDataDefinitions();
            if (typeof this.dataFields[name]['addReferencedField'] === 'function') {
                this.dataFields[name].addReferencedField(field);
            }
        } else {
            this.dataFields[name] = field;
        }
    },

    getLayoutShow: function () {

        this.component = this.getLayoutEdit();
        this.component.disable();

        return this.component;
    },

    getValue: function () {

        var data = [];
        var element;
        var elementData = {};

        for(var s=0; s<this.component.items.items.length; s++) {
            elementData = {};
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u=0; u<elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    try {
                        // no check for dirty, ... always send all field to the server
                        elementData[element.fields[elementFieldName].getName()] = element.fields[elementFieldName].getValue();
                    } catch (e) {
                        console.log(e);
                        elementData[element.fields[elementFieldName].getName()] = "";
                    }

                }

                data.push({
                    data: elementData,
                    oIndex: element.container.opendxp_oIndex
                });
            }
        }

        return data;
    },

    getName: function () {
        return this.fieldConfig.name;
    },

    isDirty: function() {

        // check elements
        var element;

        if(!this.isRendered()) {
            return false;
        }

        if(typeof this.component.items == "undefined") {
            return false;
        }

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u=0; u<elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    if(element.fields[elementFieldName].isDirty()) {
                        return true;
                    }
                }
            }
        }

        return this.dirty;
    },

    isMandatory: function () {
        var element;

        for(var s=0; s<this.component.items.items.length; s++) {
            if(this.currentElements[this.component.items.items[s].key]) {
                element = this.currentElements[this.component.items.items[s].key];

                var elementFieldNames = Object.keys(element.fields);

                for (var u=0; u<elementFieldNames.length; u++) {
                    var elementFieldName = elementFieldNames[u];
                    if(element.fields[elementFieldName].isMandatory()) {
                        return true;
                    }
                }
            }
        }

        return false;
    }
});

opendxp.object.tags.block.addMethods(opendxp.object.helpers.edit);

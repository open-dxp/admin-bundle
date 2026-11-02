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

opendxp.registerNS("opendxp.object.edit");
/**
 * @private
 */
opendxp.object.edit = Class.create({

    initialize: function(object) {
        this.object = object;
        this.dataFields = {};
        this.toolbar = null;
    },

    getLayout: function (conf) {
        if (this.layout == null) {
            var items = [];
            if (conf) {
                items = this.getRecursiveLayout(conf).items;
            }


            this.layout = Ext.create('Ext.panel.Panel', {
                title: t('edit'),
                padding: 10,
                border: false,
                layout: "fit",
                iconCls: "opendxp_material_icon_edit opendxp_material_icon",
                cls: "opendxp_object_panel_edit",
                items: items,
                listeners: {
                    afterrender: function () {
                        opendxp.layout.refresh();
                    }
                }
            });
        }

        return this.layout;
    },

    getDataForField: function (fieldConfig) {
        var name = fieldConfig.name;
        return this.object.data.data[name];
    },

    getMetaDataForField: function (fieldConfig) {
        var name = fieldConfig.name;
        return this.object.data.metaData[name];
    },

    addToDataFields: function (field, name) {
        if(this.dataFields[name]) {
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

    getValues: function (omitMandatoryCheck) {

        if (!this.layout.rendered) {
            throw "edit not available";
        }

        var dataKeys = Object.keys(this.dataFields);
        var values = {};
        var currentField;

        for (var i = 0; i < dataKeys.length; i++) {
            if (this.dataFields[dataKeys[i]] && typeof this.dataFields[dataKeys[i]] == "object") {
                currentField = this.dataFields[dataKeys[i]];

                //only include changed values in save response.
                if(currentField.isDirty()) {
                    values[currentField.getName()] =  currentField.getValue();
                }
            }
        }

        return values;
    }

});

opendxp.object.edit.addMethods(opendxp.object.helpers.edit);

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

opendxp.registerNS("opendxp.asset.helpers.gridTabAbstract");
/**
 * @private
 */
opendxp.asset.helpers.gridTabAbstract = Class.create(opendxp.element.helpers.gridTabAbstract, {

    objecttype: 'asset',
    exportSource: 'assets',
    batchPrepareUrl: null,
    batchProcessUrl: null,

    initialize: function() {
        this.batchPrepareUrl = Routing.generate('opendxp_admin_asset_assethelper_getbatchjobs');
        this.batchProcessUrl = Routing.generate('opendxp_admin_asset_assethelper_batch');
    },

    createGrid: function (columnConfig) {
    },

    openColumnConfig: function (allowPreview) {
        var gridConfig = this.getGridConfig();
        var fields = gridConfig.columns;

        var fieldKeys = Object.keys(fields);

        var visibleColumns = [];
        for (var i = 0; i < fieldKeys.length; i++) {
            var field = fields[fieldKeys[i]];
            if (!field.hidden) {
                var fc = {
                    key: fieldKeys[i],
                    label: field.fieldConfig.label,
                    dataType: field.fieldConfig.type,
                    layout: field.fieldConfig.layout,
                    language: field.fieldConfig.language,
                };
                if (field.fieldConfig.width) {
                    fc.width = field.fieldConfig.width;
                }
                if (field.fieldConfig.locked) {
                    fc.locked = field.fieldConfig.locked;
                }

                if (field.isOperator) {
                    fc.isOperator = true;
                    fc.attributes = field.fieldConfig.attributes;

                }

                visibleColumns.push(fc);
            }
        }

        var columnConfig = {
            language: gridConfig.language,
            pageSize: gridConfig.pageSize,
            selectedGridColumns: visibleColumns
        };

        var applyCallback = function (data, settings, save) {
            this.gridLanguage = data.language;
            this.gridPageSize = data.pageSize;
            this.createGrid(true, data.columns, settings, save);
        }.bind(this);

        var resetCallback = function () {
            Ext.Ajax.request({
                url: Routing.generate('opendxp_admin_asset_assethelper_gridgetcolumnconfig'),
                params: {
                    gridtype: "grid",
                    searchType: this.searchType
                },
                success: function (response) {
                    response = Ext.decode(response.responseText);
                    if (response) {
                        fields = response.availableFields;
                        this.createGrid(false, fields, response.settings, false);
                        if (typeof this.saveColumnConfigButton !== "undefined") {
                            this.saveColumnConfigButton.hide();
                        }
                    } else {
                        opendxp.helpers.showNotification(t("error"), t("error_resetting_config"),
                            "error", t(rdata.message));
                    }
                }.bind(this),
                failure: function () {
                    opendxp.helpers.showNotification(t("error"), t("error_resetting_config"), "error");
                }
            });
        }.bind(this);

        let eventData = {
            instance: null,
            implementation: null,
            additionalConfig: null,
            context: this
        };

        const prepareAssetMetadataGridConfigurator = new CustomEvent(opendxp.events.prepareAssetMetadataGridConfigurator, {
            detail: {
                eventData: eventData
            }
        });

        document.dispatchEvent(prepareAssetMetadataGridConfigurator);

        if (eventData.instance) {
            // everything is handled by the event handler, nothing else to do
            return;
        }

        // replace implementation
        let implementation = eventData.implementation;

        if (!implementation) {
            implementation = opendxp.asset.helpers.gridConfigDialog;
        }

        var dialog = new implementation(columnConfig, applyCallback, resetCallback,
            true,                       // showSaveAndShareTab
            this.settings,
            {                           // preview settings
                allowPreview: true,
                folderId: this.element.id
            },
            eventData.additionalConfig
        );
    },

    getGridConfig: function () {
        var config = {
            language: this.gridLanguage,
            pageSize: this.gridPageSize,
            sortinfo: this.sortinfo,
            columns: {}
        };

        var cm = this.grid.getView().getGridColumns();

        for (var i = 0; i < cm.length; i++) {
            if (cm[i].dataIndex) {
                var name = cm[i].dataIndex;
                config.columns[name] = {
                    name: name,
                    position: i,
                    hidden: cm[i].hidden,
                    width: cm[i].width,
                    locked: cm[i].locked,
                    fieldConfig: this.fieldObject[name],
                    //isOperator: this.fieldObject[name].isOperator
                };
            }
        }

        return config;
    },
});

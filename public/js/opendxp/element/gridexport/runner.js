opendxp.registerNS("opendxp.element.gridexport.runner");
/**
 * Exports the rows of a grid. It asks for the settings, writes the export batch by batch and downloads the file.
 *
 * The config of a grid holds:
 * - source: the name of the grid export source
 * - getParameters: a function that returns the filters and the sorting of the grid
 * - getSelectedIds: a function that returns the IDs of the rows selected in the checkbox column, optional
 * - warnings: a note per format on top of the dialog, for example {csv: "…"}, optional
 * - settings: fields of the grid for the dialog, optional. Their values go into the parameters.
 */
opendxp.element.gridexport.runner = Class.create({
    initialize: function (config) {
        this.config = config;
    },

    start: function () {
        var selectedIds = this.config.getSelectedIds ? this.config.getSelectedIds() : [];

        if (selectedIds.length === 0) {
            this.showSettings([]);
            return;
        }

        Ext.Msg.show({
            title: t("export"),
            message: sprintf(t("grid_export_only_selected"), selectedIds.length),
            icon: Ext.Msg.QUESTION,
            buttons: Ext.Msg.YESNO,
            buttonText: {
                no: t("grid_export_all_rows")
            },
            fn: function (button) {
                if (button === "yes") {
                    this.showSettings(selectedIds);
                } else if (button === "no") {
                    this.showSettings([]);
                }
            }.bind(this)
        });
    },

    showSettings: function (selectedIds) {
        var warnings = this.config.warnings || {};

        var warning = new Ext.Component({
            html: this.getWarningHtml(warnings.csv),
            hidden: !warnings.csv,
            padding: 10
        });

        var delimiterField = new Ext.form.TextField({
            fieldLabel: t("delimiter"),
            name: "delimiter",
            maxLength: 1,
            allowBlank: false,
            value: ";"
        });

        var exportItems = [{
            xtype: "radiogroup",
            fieldLabel: t("format"),
            columns: [80, 80],
            items: [
                {boxLabel: "CSV", name: "format", inputValue: "csv", checked: true},
                {boxLabel: "XLSX", name: "format", inputValue: "xlsx"}
            ],
            listeners: {
                change: function (radioGroup, value) {
                    delimiterField.setHidden(value.format !== "csv");
                    delimiterField.setDisabled(value.format !== "csv");
                    warning.setHtml(this.getWarningHtml(warnings[value.format]));
                    warning.setHidden(!warnings[value.format]);
                }.bind(this)
            }
        }, {
            xtype: "combo",
            fieldLabel: t("header"),
            name: "header",
            store: [
                ["title", t("label")],
                ["name", t("system_key")],
                ["no_header", t("no_header")]
            ],
            value: "title",
            editable: false,
            forceSelection: true
        }, delimiterField];

        var settings = this.config.settings || [];

        var formPanel = new Ext.form.FormPanel({
            bodyStyle: "padding: 10px;",
            fieldDefaults: {
                labelWidth: 200
            },
            items: [{
                xtype: "fieldset",
                title: t("export"),
                items: exportItems
            }].concat(settings)
        });

        var dialog = new Ext.Window({
            modal: true,
            title: t("export"),
            width: 600,
            items: [warning, formPanel],
            buttonAlign: "center",
            buttons: [{
                text: t("export"),
                iconCls: "opendxp_icon_export",
                handler: function () {
                    if (!formPanel.isValid()) {
                        return;
                    }

                    var values = formPanel.getForm().getFieldValues();
                    var parameters = this.config.getParameters();

                    settings.forEach(function (setting) {
                        setting.query("field").forEach(function (field) {
                            parameters[field.getName()] = values[field.getName()];
                        });
                    });

                    dialog.close();
                    this.createExport(values, parameters, selectedIds);
                }.bind(this)
            }, {
                text: t("cancel"),
                iconCls: "opendxp_icon_cancel",
                handler: function () {
                    dialog.close();
                }
            }]
        });

        dialog.show();
    },

    getWarningHtml: function (text) {
        if (!text) {
            return "";
        }

        return '<div class="opendxp_grid_export_warning">' + Ext.util.Format.htmlEncode(text) + '</div>';
    },

    createExport: function (values, parameters, selectedIds) {
        Ext.Ajax.request({
            url: Routing.generate("opendxp_admin_gridexport_start"),
            method: "POST",
            params: {
                source: this.config.source,
                parameters: Ext.encode(this.toRequestValue(parameters)),
                "selectedIds[]": selectedIds,
                format: values.format,
                header: values.header,
                delimiter: values.delimiter || ";",
                language: opendxp.settings.language,
                timezone: getUserTimezone()
            },
            success: function (response) {
                var gridExport = Ext.decode(response.responseText);

                if (gridExport.total <= opendxp.settings.grid_export_confirm_threshold) {
                    this.showProgress(gridExport);
                    return;
                }

                var total = new Intl.NumberFormat(opendxp.settings.language).format(gridExport.total);

                Ext.Msg.confirm(t("export"), sprintf(t("grid_export_confirmation"), total), function (button) {
                    if (button === "yes") {
                        this.showProgress(gridExport);
                        return;
                    }

                    this.deleteExport(gridExport);
                }.bind(this));
            }.bind(this)
        });
    },

    showProgress: function (gridExport) {
        this.running = true;
        this.numberFormat = new Intl.NumberFormat(opendxp.settings.language);

        this.progressBar = new Ext.ProgressBar({
            text: t("initializing")
        });

        this.progressWindow = new Ext.Window({
            title: t("export"),
            layout: "fit",
            width: 300,
            bodyStyle: "padding: 10px;",
            plain: true,
            items: [this.progressBar],
            listeners: Ext.apply(opendxp.helpers.getProgressWindowListeners(), {
                beforeclose: function () {
                    if (this.running) {
                        this.cancelExport(gridExport);
                    }
                }.bind(this)
            })
        });

        this.progressWindow.show();
        this.writeBatch(gridExport, 0);
    },

    writeBatch: function (gridExport, batch) {
        if (batch >= gridExport.batchCount) {
            this.closeProgress();
            opendxp.helpers.download(Routing.generate("opendxp_admin_gridexport_download", {id: gridExport.id}));
            return;
        }

        var rows = Math.min(batch * gridExport.batchSize, gridExport.total);
        this.progressBar.updateProgress(rows / gridExport.total, sprintf(
            "%s / %s",
            this.numberFormat.format(rows),
            this.numberFormat.format(gridExport.total)
        ));

        this.request = Ext.Ajax.request({
            url: Routing.generate("opendxp_admin_gridexport_batch"),
            method: "POST",
            params: {
                id: gridExport.id,
                batch: batch
            },
            success: function () {
                if (this.running) {
                    this.writeBatch(gridExport, batch + 1);
                }
            }.bind(this),
            failure: function () {
                if (!this.running) {
                    return;
                }

                this.closeProgress();
                this.deleteExport(gridExport);
            }.bind(this)
        });
    },

    cancelExport: function (gridExport) {
        this.running = false;
        Ext.Ajax.abort(this.request);
        this.deleteExport(gridExport);
    },

    closeProgress: function () {
        this.running = false;
        this.progressWindow.close();
    },

    /**
     * Converts a value the way a form request does. The source receives the parameters as the grid sends them.
     */
    toRequestValue: function (value) {
        if (Ext.isArray(value)) {
            return value.map(this.toRequestValue.bind(this));
        }

        if (Ext.isObject(value)) {
            var converted = {};
            Ext.Object.each(value, function (key, item) {
                converted[key] = this.toRequestValue(item);
            }, this);

            return converted;
        }

        if (value === null || value === undefined) {
            return value;
        }

        if (Ext.isDate(value)) {
            return Ext.Date.toString(value);
        }

        return String(value);
    },

    deleteExport: function (gridExport) {
        Ext.Ajax.request({
            url: Routing.generate("opendxp_admin_gridexport_delete"),
            method: "POST",
            params: {
                id: gridExport.id
            }
        });
    }
});

/**
 * Returns the parameters that a store sends to load its rows: the extra parameters, the filters and the sorting.
 */
opendxp.element.gridexport.runner.getStoreParameters = function (store) {
    var proxy = store.getProxy();
    var parameters = Ext.apply({}, proxy.getExtraParams());

    if (store.getFilters().getCount() > 0) {
        parameters.filter = proxy.encodeFilters(store.getFilters().getRange());
    }

    if (store.getSorters().getCount() > 0) {
        parameters.sort = proxy.encodeSorters(store.getSorters().getRange());
    }

    return parameters;
};

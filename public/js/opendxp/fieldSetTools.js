Ext.define('opendxp.FieldSetTools', {
    extend: 'Ext.form.FieldSet',

    createLegendCt: function () {
        const legend = this.callParent(arguments);

        (this.config.tools || []).forEach(function (tool) {
            this.createToolCmp(tool, legend);
        }, this);

        return legend;
    },

    createToolCmp: function (tool, legend) {
        const config = {
            type: tool.type,
            html: this.title,
            ui: this.ui,
            tooltip: tool.qtip,
            handler: tool.handler,
            hidden: tool.hidden,
            cls: [
                this.baseCls + '-header-tool-default',
                this.baseCls + '-header-tool-right',
                tool.cls
            ].filter(Boolean).join(' '),
            ariaRole: 'checkbox',
            ariaRenderAttributes: {
                'aria-checked': !this.collapsed
            }
        };

        if (tool.id) {
            config.id = tool.id;
        }

        legend.add(new Ext.panel.Tool(config));

        return legend;
    }
});

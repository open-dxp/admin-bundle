Ext.define('opendxp.filters', {
    extend: 'Ext.grid.filters.Filters',
    alias: 'plugin.opendxp.gridfilters',

    menuFilterText: t('filter'),

    createColumnFilter: function (column) {
        this.callParent([column]);

        const type = column.filter.type;
        const filter = column.filter.filter;

        if (!(column.filter instanceof Ext.grid.filters.filter.TriFilter)) {
            filter.config.type = type;

            return;
        }

        ['lt', 'gt', 'eq'].forEach(function (operator) {
            filter[operator].config.type = type;

            if (column.decimalPrecision) {
                column.filter.fields[operator].decimalPrecision = column.decimalPrecision;
            }
        });
    }
});

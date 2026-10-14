Ext.define('opendxp.tree.Panel', {
    extend: 'Ext.tree.Panel',

    initComponent: function () {
        // Ext.tree.Panel builds the same automatic column, but always as a plain treecolumn.
        if (!this.columns) {
            this.isAutoTree = true;
            this.addCls(this.autoWidthCls);
            this.viewConfig = Ext.apply({isAutoTree: true}, this.viewConfig);
            this.columns = [{xtype: 'opendxptreecolumn', flex: 1, dataIndex: this.displayField}];
        }

        this.callParent();
    }
});

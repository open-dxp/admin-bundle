Ext.define('opendxp.tree.Column', {
    extend: 'Ext.tree.Column',
    alias: 'widget.opendxptreecolumn',

    cellTpl: Ext.tree.Column.prototype.cellTpl.concat('<tpl if="folderBar">{folderBar}</tpl>'),

    initTemplateRendererData: function () {
        const data = this.callParent(arguments);

        if (opendxp.tree.FolderBar.isShown(data.record)) {
            // Ext hides the expander of a loaded folder without children, as after a search without results.
            data.expandable = true;
            data.metaData.tdCls = Ext.String.trim((data.metaData.tdCls || '') + ' opendxp_tree_paged_node');
            // The bar starts below the icon of its folder, behind the lines and the elbow of the folder.
            data.folderBar = opendxp.tree.FolderBar.getHtml(data.record, data.lines.length + 1);
        }

        return data;
    }
});

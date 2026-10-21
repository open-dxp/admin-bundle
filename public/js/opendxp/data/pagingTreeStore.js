Ext.define('opendxp.data.PagingTreeStore', {
    extend: 'Ext.data.TreeStore',

    constructor: function (config) {
        this.callParent([config]);

        this.on({
            nodeappend: function (parent) {
                parent.set('expandable', true);
            },
            noderemove: function (parent) {
                if (!parent.hasChildNodes()) {
                    parent.set('expandable', false);
                }
            }
        });
    },

    onProxyLoad: function (operation) {
        if (operation.wasSuccessful()) {
            this.setPagingData(operation.initialConfig.node, operation.getResponse().responseJson);
        }

        this.callParent([operation]);
        this.getProxy().setExtraParam('start', 0);
    },

    setPagingData: function (node, data) {
        node.filter = data.filter;
        node.inSearch = data.inSearch;
        node.pagingData = {
            total: data.total,
            offset: data.offset,
            limit: data.limit,
            canSortManually: !data.filter && data.total < data.limit
        };
    },

    /**
     * Loads one page of the children of an open folder. The load of Ext.data.TreeStore empties and closes the folder
     * until the answer arrives. A page keeps the old children until it swaps them.
     */
    loadPage: function (node, offset, params, callback, scope) {
        const pageRequest = node.pageRequest = (node.pageRequest || 0) + 1;
        const options = {
            node: node,
            id: node.getId(),
            params: params,
            pageRequest: pageRequest,
            internalScope: this,
            internalCallback: this.onPageLoad,
            callback: function (records, operation, success) {
                if (pageRequest === node.pageRequest) {
                    Ext.callback(callback, scope, [success]);
                }
            }
        };

        // The proxy sends its extra parameter start in place of the start of the operation.
        this.getProxy().setExtraParam('start', offset);
        this.setLoadOptions(options);
        const operation = this.createOperation('read', options);

        if (this.fireEvent('beforeload', this, operation) !== false) {
            operation.execute();
        }
    },

    onPageLoad: function (operation) {
        const options = operation.initialConfig;
        const node = options.node;

        this.getProxy().setExtraParam('start', 0);

        if (options.pageRequest !== node.pageRequest || !operation.wasSuccessful()) {
            return;
        }

        this.setPagingData(node, operation.getResponse().responseJson);

        // A load of Ext.data.TreeStore sets loading as well, so that the store keeps no removed records.
        // Destroying the old children would send a destroy request for each.
        this.loading = true;
        node.removeAll(false);
        this.loading = false;
        node.appendChild(operation.getRecords());

        this.fireEvent('load', this, operation.getRecords(), true, operation, node);
    }
});

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

/**
 * @private
 */
opendxp.helpers.grid = {};

opendxp.helpers.grid.buildDefaultStore = function (url, fields, itemsPerPage, customConfig) {

    url = url.indexOf('?') === -1 ? url + '?' : url + '&';

    var proxy = Ext.create('opendxp.data.proxy.ajax', {
        timeout: 120000,
        batchActions: false,
        type: 'ajax',
        reader: {
            type: 'json',
            rootProperty: 'data'
        },
        writer: {
            type: 'json',
            writeAllFields: true,
            rootProperty: 'data',
            encode: 'true'
        },
        api: {
            create: url + 'xaction=create',
            read: url + 'xaction=read',
            update: url + 'xaction=update',
            destroy: url + 'xaction=destroy'
        },
        actionMethods: {
            create: 'POST',
            read: 'POST',
            update: 'POST',
            destroy: 'POST'
        }
    });

    var config = {
        proxy: proxy,
        autoLoad: true,
        autoSync: true,
        pageSize: itemsPerPage,
        fields: fields,
        remoteSort: true,
        remoteFilter: true
    };

    if (customConfig) {
        Ext.apply(config, customConfig);
    }

    return Ext.create('Ext.data.Store', config);
};

opendxp.helpers.grid.getDefaultPageSize = function (scale) {
    if (scale < 0) {
        return 25;
    }
    return 50;
};

opendxp.helpers.grid.buildDefaultPagingToolbar = function (store, options) {
    var config = {
        pageSize: opendxp.helpers.grid.getDefaultPageSize(),
        store: store,
        displayInfo: true,
        displayMsg: '{0} - {1} / {2}',
        emptyMsg: t('no_items_found'),
        scrollable: true
    };
    if (typeof options !== 'undefined') {
        config = Ext.applyIf(options, config);
    }
    var pagingtoolbar = Ext.create('Ext.PagingToolbar', config);

    if (!config.hideSelection) {
        // add per-page selection
        pagingtoolbar.add('-');

        pagingtoolbar.add(Ext.create('Ext.Toolbar.TextItem', {
            text: t('items_per_page')
        }));
        pagingtoolbar.add(Ext.create('Ext.form.ComboBox', {
            store: [
                [25, '25'],
                [50, '50'],
                [100, '100'],
                [200, '200'],
                [999999, t('all')]
            ],
            mode: 'local',
            width: 80,
            value: config.pageSize,
            triggerAction: 'all',
            editable: true,
            listeners: {
                change: function (box, newValue, oldValue) {
                    var store = this.getStore();
                    newValue = intval(newValue);
                    if (!newValue) {
                        newValue = options.pageSize;
                    }
                    store.setPageSize(newValue);
                    this.pageSize = newValue;
                    this.moveFirst();
                }.bind(pagingtoolbar)
            }
        }));
    }

    return pagingtoolbar;
};

opendxp.helpers.grid.getTranslationColumnRenderer = function (value, metaData, record, rowIndex, colIndex, store) {
    return Ext.util.Format.htmlEncode(t(value));
};

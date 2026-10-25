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

// The build ext-all has no manifest. Without it Ext JS reports version 6.
Ext.setVersion('ext', '7.0.0.159');
Ext.setVersion('core', '7.0.0.159');

Ext.define('opendxp.overrides.form.field.Date', {
    override: 'Ext.form.field.Date',
    startDay: 1
});

Ext.define('opendxp.overrides.dd.DragDropManager', {
    override: 'Ext.dd.DragDropManager',

    startDrag: function (x, y) {
        opendxp.helpers.treeNodeThumbnailPreviewHide();
        this.callParent([x, y]);
    }
});

Ext.define('opendxp.overrides.dom.Element', {
    override: 'Ext.dom.Element',
    // An editable takes its element id from its name. The name of an editable in a block holds a colon and a dot.
    validIdRe: /^[a-z][a-z0-9\-_:.]*$/i,

    // A drop zone can destroy its component on drop. The mouseup afterwards focuses the destroyed element.
    focus: function (defer, dom) {
        if (!dom && !this.dom) {
            return this;
        }

        return this.callParent([defer, dom]);
    }
});

Ext.define('opendxp.overrides.grid.RowEditor', {
    override: 'Ext.grid.RowEditor',
    syncButtonPosition: function (context) {
        var me = this,
            scrollDelta = me.getScrollDelta(),
            floatingButtons = me.getFloatingButtons(),
            scrollHeight = Math.max(0, me.scroller.getSize().y - me.scroller.getClientSize().y),
            overflow = scrollDelta - (scrollHeight - me.scroller.getPosition().y);

        floatingButtons.show();

        // Ext JS puts the buttons above the last row from the second row on. There they cover the column headers.
        if (overflow > 0 || (context.rowIdx > 1 && context.isLastRenderedRow())) {
            if (!me._buttonsOnTop) {
                floatingButtons.setButtonPosition('top');
                me._buttonsOnTop = true;
                me.layout.setAlign('bottom');
                me.updateLayout();
            }

            scrollDelta = 0;
        } else if (me._buttonsOnTop !== false) {
            floatingButtons.setButtonPosition('bottom');
            me._buttonsOnTop = false;
            me.layout.setAlign('top');
            me.updateLayout();
        } else {
            floatingButtons.setButtonPosition(floatingButtons.position);
        }

        return scrollDelta;
    }
});

Ext.define('opendxp.overrides.grid.filters.filter.Number', {
    override: 'Ext.grid.filters.filter.Number',

    menuItems: ['lt', 'gt', '-', 'eq', 'in'],

    config: {
        fields: {
            gt: {
                iconCls: Ext.baseCSSPrefix + 'grid-filters-gt',
                margin: '0 0 3px 0'
            },
            lt: {
                iconCls: Ext.baseCSSPrefix + 'grid-filters-lt',
                margin: '0 0 3px 0'
            },
            eq: {
                iconCls: Ext.baseCSSPrefix + 'grid-filters-eq',
                margin: '0 0 3px 0'
            },
            in: {
                xtype: 'textfield',
                iconCls: Ext.baseCSSPrefix + 'grid-filters-find',
                emptyText: 'Enter Numbers...',
                margin: 0
            }
        }
    },

    constructor: function (config) {
        var me = this,
            filterIn;

        me.callParent([config]);

        filterIn = me.getStoreFilter('in');

        if (filterIn) {
            me.onStateRestore(filterIn);
            me.active = true;
            me.setColumnActive(true);
        }

        me.filter.in = filterIn || me.createFilter({operator: 'in', type: 'numeric', value: null}, 'in');
    },

    /**
     * Sets the value of one operator. A list of numbers and an equal number exclude each other.
     */
    setValue: function (value) {
        var me = this,
            filterIn = me.filter.in,
            filterCollection = me.getGridStore().getFilters();

        if (me.preventFilterRemoval) {
            return;
        }

        if (!('in' in value) && !('eq' in value)) {
            me.callParent([value]);

            return;
        }

        filterCollection.beginUpdate();

        if (filterIn.getValue() && (!value.in || 'eq' in value)) {
            me.preventFilterRemoval = true;
            me.fields.in.setValue(null);
            filterIn.setValue(null);
            me.removeStoreFilter(filterIn);
            me.preventFilterRemoval = false;
        }

        if (value.in) {
            filterIn.setValue(value.in);
            me.addStoreFilter(filterIn);
        }

        me.callParent(['in' in value ? {} : value]);

        filterCollection.endUpdate();
    }
});

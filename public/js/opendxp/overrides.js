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

Ext.setVersion("ext", "7.0.0.159");
Ext.setVersion("core", "7.0.0.159");

if(typeof window['t'] !== 'function') {
    // for compatibility reasons
    window.t = function(v) { return v; };
}


Ext.form.field.Date.prototype.startDay = 1;

Ext.override(Ext.dd.DragDropMgr, {
        startDrag: function (x, y) {

            // always hide tree-previews on drag start
            opendxp.helpers.treeNodeThumbnailPreviewHide();

            this.callParent(arguments);
        }
    }
);

/**
 * Undesired behaviour: submenu is hidden on clicking owner menu item
 * fix see https://www.sencha.com/forum/showthread.php?305492-Undesired-behaviour-submenu-is-hidden-on-clicking-owner-menu-item
 * @param e
 */
Ext.menu.Manager.checkActiveMenus = function(e) {
    var allMenus = this.visible,
        len = allMenus.length,
        i, menu,
        mousedownCmp = Ext.Component.fromElement(e.target);
    if (len) {
        // Clone here, we may modify this collection while the loop is active
        allMenus = allMenus.slice();
        for (i = 0; i < len; ++i) {
            menu = allMenus[i];
            // Hide the menu if:
            //      The menu does not own the clicked upon element AND
            //      The menu is not the child menu of a clicked upon MenuItem
            if (!(menu.owns(e) || (mousedownCmp && mousedownCmp.isMenuItem && mousedownCmp.menu === menu))) {
                menu.hide();
            }
        }
    }
};


Ext.define('opendxp.FieldSetTools', {
    extend: 'Ext.form.FieldSet',

    createLegendCt: function () {
        var me = this;
        var result = this.callSuper(arguments);

        if (me.config.tools && me.config.tools.length > 0) {
            for (var i = 0; i < me.config.tools.length; i++) {
                var tool = me.config.tools[i];
                this.createToolCmp(tool, result);
            }
        }
        return result;

    },


    createToolCmp: function(tool, result) {
        var me = this;
        var cls = me.baseCls + '-header-tool-default ' + me.baseCls + '-header-tool-right';
        if (tool['cls']) {
            cls = cls + ' ' + tool['cls'];
        }
        var cfg = {
            type: tool['type'],
            html: me.title,
            ui: me.ui,
            tooltip: tool.qtip,
            handler: tool.handler,
            hidden: tool.hidden,
            cls: cls,
            ariaRole: 'checkbox',
            ariaRenderAttributes: {
                'aria-checked': !me.collapsed
            }
        };

        if (tool['id']) {
            cfg['id'] = tool['id'];
        }

        var cmp = new Ext.panel.Tool(cfg);
        result.add(cmp);
        return result;
    },
});

Ext.define('opendxp.filters', {
    extend: 'Ext.grid.filters.Filters',
    alias: 'plugin.opendxp.gridfilters',
    menuFilterText: t('filter'),

    createColumnFilter: function(column) {
        this.callSuper(arguments);
        var type = column.filter.type;
        var theFilter = column.filter.filter;

        if (column.filter instanceof Ext.grid.filters.filter.TriFilter) {
            theFilter.lt.config.type = type;
            theFilter.gt.config.type = type;
            theFilter.eq.config.type = type;

            if (column.decimalPrecision) {
                column.filter.fields.lt.decimalPrecision = column.decimalPrecision;
                column.filter.fields.gt.decimalPrecision = column.decimalPrecision;
                column.filter.fields.eq.decimalPrecision = column.decimalPrecision;
            }
        } else {
            theFilter.config.type = type;
        }
    }
});

// See https://www.sencha.com/forum/showthread.php?288385
// Column renderer will give no metadata parameter after change a value of cell.
// It happens because column renderer method is invoked with null second parameter here
Ext.define('Ext.overrides.grid.View', {
        extend: 'Ext.grid.View',

        alias: 'widget.patchedgridview'
        ,

        handleUpdate: function(store, record, operation, changedFieldNames) {
            var me = this,
                rowTpl = me.rowTpl,
                oldItem, oldItemDom, oldDataRow,
                newItemDom,
                newAttrs, attLen, attName, attrIndex,
                overItemCls,
                focusedItemCls,
                selectedItemCls,
                columns,
                column,
                columnsToUpdate = [],
                len, i,
                hasVariableRowHeight = me.variableRowHeight,
                cellUpdateFlag,
                updateTypeFlags = 0,
                cell,
                fieldName,
                value,
                defaultRenderer,
                scope,
                ownerCt = me.ownerCt;


            if (me.viewReady) {
                oldItemDom = me.getNodeByRecord(record);

                if (oldItemDom) {
                    overItemCls = me.overItemCls;
                    focusedItemCls = me.focusedItemCls;
                    selectedItemCls = me.selectedItemCls;
                    columns = me.ownerCt.getVisibleColumnManager().getColumns();

                    if (!me.getRowFromItem(oldItemDom) || (updateTypeFlags & 1) || (oldItemDom.tBodies[0].childNodes.length > 1)) {
                        oldItem = Ext.fly(oldItemDom, '_internal');
                        newItemDom = me.createRowElement(record, me.dataSource.indexOf(record), columnsToUpdate);
                        if (oldItem.hasCls(overItemCls)) {
                            Ext.fly(newItemDom).addCls(overItemCls);
                        }
                        if (oldItem.hasCls(focusedItemCls)) {
                            Ext.fly(newItemDom).addCls(focusedItemCls);
                        }
                        if (oldItem.hasCls(selectedItemCls)) {
                            Ext.fly(newItemDom).addCls(selectedItemCls);
                        }

                        newAttrs = newItemDom.attributes;
                        attLen = newAttrs.length;
                        for (attrIndex = 0; attrIndex < attLen; attrIndex++) {
                            attName = newAttrs[attrIndex].name;
                            if (attName !== 'id') {
                                oldItemDom.setAttribute(attName, newAttrs[attrIndex].value);
                            }
                        }

                        if (columns.length && (oldDataRow = me.getRow(oldItemDom))) {
                            me.updateColumns(oldDataRow, Ext.fly(newItemDom).down(me.rowSelector, true), columnsToUpdate);
                        }

                        while (rowTpl) {
                            if (rowTpl.syncContent) {
                                if (rowTpl.syncContent(oldItemDom, newItemDom, changedFieldNames ? columnsToUpdate : null) === false) {
                                    break;
                                }
                            }
                            rowTpl = rowTpl.nextTpl;
                        }
                    }
                    else {
                        this.refresh();
                    }

                    if (hasVariableRowHeight) {
                        Ext.suspendLayouts();
                    }


                    me.fireEvent('itemupdate', record, me.store.indexOf(record), oldItemDom);

                    if (hasVariableRowHeight) {
                        me.refreshSize();

                        Ext.resumeLayouts(true);
                    }
                }
            }
        }
    }
);

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

Ext.define('opendxp.tree.FolderBar', {
    singleton: true,

    fullWidth: 156,

    compactWidth: 114,

    tpl: new Ext.XTemplate(
        '<button type="button" data-action="widen" title="{widenText}" aria-label="{widenText}"',
            ' class="opendxp_tree_folder_widen"></button>',
        '<div class="opendxp_tree_folder_bar<tpl if="filter"> opendxp_tree_folder_bar_searching</tpl>"',
            ' role="toolbar" data-node-id="{nodeId}">',
            '<div class="opendxp_tree_folder_bar_indent">{indent}</div>',
            '<div class="opendxp_tree_folder_bar_controls">',
                '<button type="button" data-action="open-search" title="{searchText}" aria-label="{searchText}"',
                    ' class="opendxp_tree_folder_bar_open_search"></button>',
                '<label class="opendxp_tree_folder_bar_search">',
                    '<input type="text" value="{filter:htmlEncode}" placeholder="{searchText}"',
                        ' aria-label="{searchText}" autocomplete="off" spellcheck="false">',
                    '<tpl if="filter">',
                        '<button type="button" data-action="clear" title="{clearText}" aria-label="{clearText}"',
                            ' class="opendxp_tree_folder_bar_clear"></button>',
                    '</tpl>',
                '</label>',
                '<div class="opendxp_tree_folder_bar_pager">',
                    '<button type="button" data-action="previous" title="{previousText}"',
                        ' aria-label="{previousText}" class="opendxp_tree_folder_bar_previous"',
                        '<tpl if="isFirstPage"> disabled</tpl>></button>',
                    '<button type="button" data-action="jump" title="{pageText}"',
                        ' class="opendxp_tree_folder_bar_page">{page} / {pageCount}</button>',
                    '<button type="button" data-action="next" title="{nextText}" aria-label="{nextText}"',
                        ' class="opendxp_tree_folder_bar_next"<tpl if="isLastPage"> disabled</tpl>></button>',
                '</div>',
            '</div>',
        '</div>',
        '<tpl if="isEmpty">',
            '<div class="opendxp_tree_folder_bar_empty">',
                '<div class="opendxp_tree_folder_bar_indent">{indent}<div class="x-tree-elbow-img"></div></div>',
                '<span>{emptyText}</span>',
            '</div>',
        '</tpl>'
    ),

    isShown: function (node) {
        const pagingData = node.pagingData;

        return node.isExpanded() && !!pagingData && (pagingData.total > pagingData.limit || !!node.filter);
    },

    getHtml: function (node, indent) {
        const pagingData = node.pagingData;
        const pageCount = Math.ceil(pagingData.total / pagingData.limit);
        const page = pageCount === 0 ? 0 : pagingData.offset / pagingData.limit + 1;

        return this.tpl.apply({
            nodeId: node.getId(),
            indent: Ext.String.repeat('<div class="x-tree-elbow-img x-tree-elbow-empty"></div>', indent),
            filter: node.filter,
            page: page,
            pageCount: pageCount,
            isFirstPage: page <= 1,
            isLastPage: page >= pageCount,
            isEmpty: pagingData.total === 0,
            emptyText: t('no_items_found'),
            searchText: t('search'),
            clearText: t('clear_filters'),
            previousText: t('previous_page'),
            nextText: t('next_page'),
            pageText: t('page'),
            widenText: t('widen_tree_to_search')
        });
    }
});

Ext.define('opendxp.tree.View', {
    extend: 'Ext.tree.View',
    alias: 'widget.opendxptreeview',

    initComponent: function () {
        this.callParent();

        this.searchTask = new Ext.util.DelayedTask();
        this.on({
            beforeitemkeydown: this.onTreeKeyDown,
            render: this.onFolderBarRender,
            refresh: this.updateFolderBars,
            itemupdate: this.updateFolderBars,
            itemadd: this.updateFolderBars,
            itemremove: this.updateFolderBars,
            resize: this.updateFolderBars,
            scope: this
        });
    },

    renderRow: function (record, rowIdx, out) {
        this.callParent(arguments);
        this.fireEvent('itemafterrender', record, rowIdx, out);
    },

    processUIEvent: function (event) {
        // A folder bar lives in the row of its folder, but its clicks and keys do not act on the folder.
        if (event.getTarget('.opendxp_tree_folder_bar, .opendxp_tree_folder_widen', this.el)) {
            return false;
        }

        return this.callParent([event]);
    },

    onFolderBarRender: function () {
        this.stickyFolderEl = this.el.parent().createChild({
            cls: 'opendxp_tree_sticky_folder x-tree-view',
            hidden: true
        });
        this.getScrollable().on('scroll', this.onFolderScroll, this);

        [this.el, this.stickyFolderEl].forEach(function (el) {
            el.on({
                click: {fn: this.onFolderBarClick, delegate: '[data-action]', scope: this},
                input: {fn: this.onFolderBarInput, delegate: '.opendxp_tree_folder_bar_search input', scope: this},
                keydown: {fn: this.onFolderBarKeyDown, delegate: '.opendxp_tree_folder_bar input', scope: this}
            });
            // The event system of Ext does not deliver focusout to a delegate.
            el.dom.addEventListener('focusout', this.onFolderSearchBlur.bind(this));
        }, this);
        this.stickyFolderEl.on('click', this.onStickyFolderClick, this);
    },

    getFolderBarNode: function (event) {
        const bar = event.getTarget('.opendxp_tree_folder_bar');

        return this.getStore().getNodeById(bar.getAttribute('data-node-id'));
    },

    onFolderBarClick: function (event, button) {
        const action = button.getAttribute('data-action');

        if (action === 'widen') {
            this.widenTree(button.parentNode.querySelector('.opendxp_tree_folder_bar_controls'));
            return;
        }

        const node = this.getFolderBarNode(event);
        const pagingData = node.pagingData;
        const focus = {event: event, selector: '[data-action=' + action + ']'};

        if (action === 'clear') {
            this.loadFolderPage(node, 0, '', {event: event, selector: '.opendxp_tree_folder_bar_search input'});
        } else if (action === 'previous') {
            this.loadFolderPage(node, pagingData.offset - pagingData.limit, node.filter, focus);
        } else if (action === 'next') {
            this.loadFolderPage(node, pagingData.offset + pagingData.limit, node.filter, focus);
        } else if (action === 'jump') {
            this.showPageInput(node, button);
        } else if (action === 'open-search') {
            this.openFolderSearch(button.closest('.opendxp_tree_folder_bar'));
        }
    },

    showPageInput: function (node, button) {
        const input = Ext.dom.Helper.insertAfter(button, {
            tag: 'input',
            type: 'text',
            inputmode: 'numeric',
            cls: 'opendxp_tree_folder_bar_page',
            value: node.pagingData.offset / node.pagingData.limit + 1,
            'aria-label': t('page')
        });

        button.hidden = true;
        input.focus();
        input.select();
        input.addEventListener('blur', function () {
            Ext.removeNode(input);
            button.hidden = false;
        });
    },

    openFolderSearch: function (bar) {
        const input = bar.querySelector('.opendxp_tree_folder_bar_search input');

        Ext.fly(bar).addCls('opendxp_tree_folder_bar_searching');
        input.focus();

        return input;
    },

    onFolderSearchBlur: function (event) {
        const input = event.target;
        const bar = input.closest('.opendxp_tree_folder_bar');

        if (!input.matches('.opendxp_tree_folder_bar_search input') || input.value !== '') {
            return;
        }

        if (!this.getStore().getNodeById(bar.getAttribute('data-node-id')).filter) {
            bar.classList.remove('opendxp_tree_folder_bar_searching');
        }
    },

    widenTree: function (controls) {
        const panel = this.ownerGrid.up('[region]');

        if (panel) {
            panel.setWidth(panel.getWidth() + opendxp.tree.FolderBar.fullWidth - controls.offsetWidth);
        }
    },

    onFolderBarInput: function (event, input) {
        const node = this.getFolderBarNode(event);

        this.searchTask.delay(300, this.loadFolderPage, this, [node, 0, input.value, {
            event: event,
            selector: '.opendxp_tree_folder_bar_search input'
        }]);
    },

    onFolderBarKeyDown: function (event, input) {
        const node = this.getFolderBarNode(event);
        const key = event.getKey();

        if (Ext.fly(input).hasCls('opendxp_tree_folder_bar_page')) {
            this.onPageInputKeyDown(node, event, input);
            return;
        }

        if (key === event.ENTER) {
            this.searchTask.cancel();
            this.loadFolderPage(node, 0, input.value, {
                event: event,
                selector: '.opendxp_tree_folder_bar_search input'
            });
        } else if (key === event.ESC) {
            this.searchTask.cancel();
            this.loadFolderPage(node, 0, '', {focusNode: true});
        } else if (key === event.DOWN && node.firstChild) {
            event.preventDefault();
            this.getNavigationModel().setPosition(node.firstChild);
        }
    },

    onTreeKeyDown: function (view, record, item, index, event) {
        const node = opendxp.tree.FolderBar.isShown(record) ? record : record.parentNode;

        if (!node || !opendxp.tree.FolderBar.isShown(node)) {
            return;
        }

        const character = event.browserEvent.key;
        if (character.length === 1 && !event.ctrlKey && !event.altKey && !event.metaKey && character !== ' ') {
            event.preventDefault();
            const input = this.openFolderSearch(this.findFolderBar(node, true));
            input.value += character;
            input.dispatchEvent(new Event('input', {bubbles: true}));
            return false;
        }
    },

    onPageInputKeyDown: function (node, event, input) {
        const pagingData = node.pagingData;
        const pageCount = Math.ceil(pagingData.total / pagingData.limit);
        const currentPage = pagingData.offset / pagingData.limit + 1;
        const step = event.shiftKey ? 10 : 1;
        const pages = {
            [event.ENTER]: parseInt(input.value, 10) || currentPage,
            [event.UP]: currentPage + step,
            [event.PAGE_UP]: currentPage + step,
            [event.DOWN]: currentPage - step,
            [event.PAGE_DOWN]: currentPage - step,
            [event.HOME]: 1,
            [event.END]: pageCount
        };

        if (event.getKey() === event.ESC) {
            input.previousSibling.hidden = false;
            input.previousSibling.focus();
            return;
        }

        if (pages[event.getKey()] === undefined) {
            return;
        }

        event.preventDefault();
        const page = Ext.Number.constrain(pages[event.getKey()], 1, pageCount);
        this.loadFolderPage(node, (page - 1) * pagingData.limit, node.filter, {event: event, pageInput: true});
    },

    /**
     * Loads one page of the children of a folder. The folder row renders anew, so the focus option names what gets
     * the focus afterwards:
     * - selector: an element of the folder bar, in the sticky copy when the event came from there
     * - pageInput: the page field
     * - focusNode: the folder row
     */
    loadFolderPage: function (node, offset, filter, focus) {
        const search = Ext.String.trim(filter || '');
        const inSticky = !!(focus && focus.event && focus.event.getTarget('.opendxp_tree_sticky_folder'));
        const caret = focus && focus.event && focus.event.target.selectionStart;
        const barTop = focus && focus.event ? this.getFolderTop(node, inSticky) : null;

        this.setFolderBarLoading(node, true);
        this.getStore().loadPage(
            node,
            Math.max(0, offset),
            search === '' ? {inSearch: 0} : {filter: search, inSearch: 1},
            function (success) {
                this.refreshNode(node);
                this.setFolderBarLoading(node, false);

                if (!success) {
                    return;
                }

                if (barTop !== null) {
                    this.keepFolderAt(node, barTop);
                }

                if (focus && focus.focusNode) {
                    this.getNavigationModel().setPosition(node);
                } else if (focus && focus.pageInput) {
                    this.showPageInput(node, this.findFolderBar(node, inSticky).querySelector('[data-action=jump]'));
                } else if (focus && focus.selector) {
                    this.focusFolderBar(node, focus.selector, inSticky, caret);
                }
            },
            this
        );
    },

    setFolderBarLoading: function (node, loading) {
        [this.findFolderBar(node, false), this.findFolderBar(node, true)].forEach(function (bar) {
            if (bar) {
                Ext.fly(bar).toggleCls('opendxp_tree_folder_bar_loading', loading);
                bar.querySelector('.opendxp_tree_folder_bar_search input').readOnly = loading;
            }
        });
    },

    getFolderTop: function (node, inSticky) {
        return inSticky ? 0 : Ext.fly(this.getNode(node)).getY() - this.el.getY();
    },

    /**
     * Scrolls the folder row back to where it was before the load. A short page cannot scroll that far, so a spacer
     * below the rows makes the room.
     */
    keepFolderAt: function (node, top) {
        const dom = this.el.dom;
        const scroller = this.getScrollable();
        const container = this.getNodeContainer();
        const target = dom.scrollTop + Ext.fly(this.getNode(node)).getY() - this.el.getY() - top;
        const neededHeight = target + dom.clientHeight - container.offsetTop;

        if (container.offsetHeight < neededHeight) {
            this.folderSpacer = neededHeight + 'px';
            container.style.minHeight = this.folderSpacer;
        }

        scroller.scrollTo(null, target);
        // The scroller restores this position after every layout. Only a scroll of the user updates it otherwise.
        scroller.trackingScrollTop = target;
        this.updateStickyFolder();
    },

    onFolderScroll: function () {
        this.releaseFolderSpacer();
        this.updateStickyFolder();
    },

    releaseFolderSpacer: function () {
        const container = this.getNodeContainer();
        const lastRow = container && container.lastElementChild;

        if (!container || !this.folderSpacer) {
            return;
        }

        // A refresh of the view renders a new container without the spacer.
        container.style.minHeight = this.folderSpacer;

        const rowsBottom = lastRow ? lastRow.offsetTop + lastRow.offsetHeight : 0;
        if (Math.max(0, rowsBottom - this.el.dom.clientHeight) >= this.el.dom.scrollTop) {
            this.folderSpacer = null;
            container.style.minHeight = '';
        }
    },

    findFolderBar: function (node, inSticky) {
        if (inSticky && this.stickyFolderNode === node) {
            return this.stickyFolderEl.down('.opendxp_tree_folder_bar', true);
        }

        const row = this.getNode(node);

        return row && row.querySelector('.opendxp_tree_folder_bar');
    },

    focusFolderBar: function (node, selector, inSticky, caret) {
        const bar = this.findFolderBar(node, inSticky);
        let target = bar && bar.querySelector(selector);

        if (target && target.disabled) {
            target = bar.querySelector('[data-action=jump]');
        }

        if (!target) {
            return;
        }

        target.focus({preventScroll: true});
        if (typeof caret === 'number' && target.setSelectionRange) {
            target.setSelectionRange(caret, caret);
        }
    },

    updateFolderBars: function () {
        if (!this.rendered) {
            return;
        }

        this.releaseFolderSpacer();

        // The tree table grows with its widest row, and a theme can move the tree column left of the view.
        const cell = this.el.down('.x-grid-cell-inner-treecolumn');
        const width = this.el.dom.clientWidth + (cell ? this.el.getX() - cell.getX() : 0);
        [this.el, this.stickyFolderEl].forEach(function (el) {
            el.dom.style.setProperty('--opendxp-tree-width', width + 'px');
        });
        this.updateFolderBarStages(this.el.dom);
        this.updateStickyFolder();
    },

    updateFolderBarStages: function (root) {
        const folderBar = opendxp.tree.FolderBar;

        root.querySelectorAll('.opendxp_tree_folder_bar').forEach(function (bar) {
            const width = bar.querySelector('.opendxp_tree_folder_bar_controls').offsetWidth;
            const narrow = width < folderBar.compactWidth;

            bar.classList.toggle('opendxp_tree_folder_bar_compact', !narrow && width < folderBar.fullWidth);
            bar.classList.toggle('opendxp_tree_folder_bar_narrow', narrow);
            bar.parentNode.classList.toggle('opendxp_tree_folder_narrow', narrow);
        });
    },

    updateStickyFolder: function () {
        if (!this.stickyFolderEl) {
            return;
        }

        const node = this.findStickyFolderNode();

        if (!node) {
            this.stickyFolderNode = null;
            this.stickyFolderEl.setHtml('');
            this.stickyFolderEl.hide();
            return;
        }

        // The tree column can start left of the view.
        const parent = this.el.parent();
        this.stickyFolderEl.setStyle({
            left: (Ext.fly(this.getNode(node)).getX() - parent.getX()) + 'px',
            top: (this.el.getY() - parent.getY()) + 'px',
            width: this.el.dom.clientWidth + 'px'
        });

        const row = this.getStickyFolderRow(node);
        if (this.stickyFolderNode === node && this.stickyFolderMarkup === row.outerHTML) {
            return;
        }

        this.stickyFolderNode = node;
        this.stickyFolderMarkup = row.outerHTML;
        this.stickyFolderEl.setHtml('');
        this.stickyFolderEl.appendChild(row);
        this.stickyFolderEl.show();
    },

    findStickyFolderNode: function () {
        const viewTop = this.el.getY();
        let found = null;

        Ext.each(this.el.query('.opendxp_tree_paged_node'), function (cell) {
            const row = Ext.fly(cell).up(this.itemSelector, null, true);
            const node = this.getRecord(row);

            // A row on its way out of the view has no record any more.
            if (!node) {
                return;
            }

            let last = node.lastChild;

            while (last && last.isExpanded() && last.lastChild) {
                last = last.lastChild;
            }

            const lastRow = last && this.getNode(last);
            if (Ext.fly(row).getY() < viewTop && lastRow && Ext.fly(lastRow).getY() > viewTop) {
                found = node;
            }
        }, this);

        return found;
    },

    getStickyFolderRow: function (node) {
        const row = this.getNode(node).cloneNode(true);

        [row].concat(Array.from(row.querySelectorAll('[id]'))).forEach(function (element) {
            element.removeAttribute('id');
        });
        row.removeAttribute('data-recordid');
        row.removeAttribute('data-boundview');
        Ext.fly(row).removeCls(['x-grid-item-over', 'x-grid-item-selected', 'x-grid-item-focused']);

        return row;
    },

    onStickyFolderClick: function (event) {
        if (event.getTarget('.opendxp_tree_folder_bar, .opendxp_tree_folder_widen')) {
            return;
        }

        this.getNavigationModel().setPosition(this.stickyFolderNode);
    }
});

Ext.define('opendxp.data.PagingTreeStore', {

    extend: 'Ext.data.TreeStore',

    onProxyLoad: function(operation) {
        try {
            var me = this;
            var options = operation.initialConfig
            var node = options.node;
            var proxy = me.getProxy();

            me.setPagingData(node, operation.getResponse().responseJson);

            //to hide or show the expanding icon depending if children are available or not
            node.addListener('remove', function (node, removedNode, isMove) {
                if (!node.hasChildNodes()) {
                    node.set('expandable', false);
                }
            });
            node.addListener('append', function (node) {
                node.set('expandable', true);
            });

            me.superclass.onProxyLoad.call(this, operation);
            proxy.setExtraParam("start", 0);
        } catch (e) {
            console.log(e);
        }
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


/**
 * Fixes ID validation to include more characters as we need the colon for nested editable names
 *
 * See:
 *
 * - http://www.sencha.com/forum/showthread.php?296173-validIdRe-throwing-Invalid-Element-quot-id-quot-for-valid-ids-containing-colons
 * - https://github.com/JarvusInnovations/sencha-hotfixes/blob/ext/5/0/1/1255/overrides/dom/Element/ValidId.js
 */
Ext.define('EXTJS-17231.ext.dom.Element.validIdRe', {
    override: 'Ext.dom.Element',

    validIdRe: /^[a-z][a-z0-9\-_:.]*$/i,

    getObservableId: function () {
        return (this.observableId = this.callParent().replace(/([.:])/g, "\\$1"));
    }
});

//Fix - Date picker does not align to component in scrollable container and breaks view layout randomly.
Ext.override(Ext.picker.Date, {
        afterComponentLayout: function (width, height, oldWidth, oldHeight) {
        var field = this.pickerField;
        this.callParent([
            width,
            height,
            oldWidth,
            oldHeight
        ]);
        // Bound list may change size, so realign on layout
        // **if the field is an Ext.form.field.Picker which has alignPicker!**
        if (field && field.alignPicker) {
            field.alignPicker();
        }
    }
});


/** workaround for [DataObject] Advanced Image Dropzone only works once #9115
 * Issue: on node drop the component gets destroyed. On mouse up it then tries to focus an already destroyed element.
 */
Ext.override(Ext.dom.Element, {
    focus: function (defer, dom) {

        var me = this;

        dom = dom || me.dom;

        if (Number(defer)) {
            Ext.defer(me.focus, defer, me, [null, dom]);
        } else {
            Ext.fireEvent('beforefocus', dom);
            if (dom) {
                dom.focus();
            }
        }

        return me;
    }
});

/**
 * A specialized {@link Ext.view.BoundListKeyNav} implementation for navigating in the quicksearch.
 * This is needed because in the default implementation the Crtl+A combination is disabled, but this is needed
 * for the purpose of the quicksearch
 */
Ext.define('opendxp.view.BoundListKeyNav', {
    extend: 'Ext.view.BoundListKeyNav',

    alias: 'view.navigation.quicksearch.boundlist',

    initKeyNav: function(view) {
        var me = this,
            field = view.pickerField;

        // Add the regular KeyNav to the view.
        // Unless it's already been done (we may have to defer a call until the field is rendered.
        if (!me.keyNav) {
            me.callParent([view]);

            // Add ESC handling to the View's KeyMap to collapse the field
            me.keyNav.map.addBinding({
                key: Ext.event.Event.ESC,
                fn: me.onKeyEsc,
                scope: me
            });
        }

        // BoundLists must be able to function standalone with no bound field
        if (!field) {
            return;
        }

        if (!field.rendered) {
            field.on('render', Ext.Function.bind(me.initKeyNav, me, [view], 0), me, {single: true});
            return;
        }

        // BoundListKeyNav also listens for key events from the field to which it is bound.
        me.fieldKeyNav = new Ext.util.KeyNav({
            disabled: true,
            target: field.inputEl,
            forceKeyDown: true,
            up: me.onKeyUp,
            down: me.onKeyDown,
            right: me.onKeyRight,
            left: me.onKeyLeft,
            pageDown: me.onKeyPageDown,
            pageUp: me.onKeyPageUp,
            home: me.onKeyHome,
            end: me.onKeyEnd,
            tab: me.onKeyTab,
            space: me.onKeySpace,
            enter: me.onKeyEnter,
            // This object has to get its key processing in first.
            // Specifically, before any Editor's key hyandling.
            priority: 1001,
            scope: me
        });
    }
});

/**
 * Workaround to fix the rowEditing not fully showing the buttons (Update/Cancel) when there are 2 rows.
 *
 * See:
 * - https://forum.sencha.com/forum/showthread.php?305665-RowEditing-Buttons-not-visible&p=1317756&viewfull=1#post1317756
 */
Ext.define('Ext.overrides.grid.RowEditor', {
    override: 'Ext.grid.RowEditor',

    showTipBelowRow: true,

    syncButtonPosition: function (context) {
        var me = this,
            scrollDelta = me.getScrollDelta(),
            floatingButtons = me.getFloatingButtons(),
            scrollingView = me.scrollingView,
        // If this is negative, it means we're not scrolling so lets just ignore it
            scrollHeight = Math.max(0, me.scroller.getSize().y - me.scroller.getClientSize().y),
            overflow = scrollDelta - (scrollHeight - me.scroller.getPosition().y);
        floatingButtons.show();
        // If that's the last visible row, buttons should be at the top regardless of scrolling,
        // but not if there is just one row which is both first and last.
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
        } else // Ensure button Y position is synced with Editor height even if button
            // orientation doesn't change
        {
            floatingButtons.setButtonPosition(floatingButtons.position);
        }
        return scrollDelta;
    },
});

Ext.define('Ext.local.grid.filters.filter.TriFilter', {
    extend: 'Ext.grid.filters.filter.TriFilter',
    menuItems: [
        'lt',
        'gt',
        '-',
        'eq',
        'in'
    ],
    constructor: function(config) {
        var me = this,
            stateful = false,
            filter = {},
            filterGt, filterLt, filterEq, filterIn, value, operator;
        me.callParent([
            config
        ]);
        value = me.value;
        filterLt = me.getStoreFilter('lt');
        filterGt = me.getStoreFilter('gt');
        filterEq = me.getStoreFilter('eq');
        filterIn = me.getStoreFilter('in');

        if (filterLt || filterGt || filterEq || filterIn) {
            stateful = me.active = true;
            if (filterLt) {
                me.onStateRestore(filterLt);
            }
            if (filterGt) {
                me.onStateRestore(filterGt);
            }
            if (filterEq) {
                me.onStateRestore(filterEq);
            }
            if (filterIn) {
                me.onStateRestore(filterIn);
            }
        } else {
            if (me.grid.stateful && me.getGridStore().saveStatefulFilters) {
                value = undefined;
            }
            me.active = me.getActiveState(config, value);
        }
        filter.lt = filterLt || me.createFilter({
            operator: 'lt',
            value: (!stateful && value && value.lt) || null
        }, 'lt');
        filter.gt = filterGt || me.createFilter({
            operator: 'gt',
            value: (!stateful && value && value.gt) || null
        }, 'gt');
        filter.eq = filterEq || me.createFilter({
            operator: 'eq',
            value: (!stateful && value && value.eq) || null
        }, 'eq');
        filter.in = filterIn || me.createFilter({
            operator: 'in',
            type: 'numeric',
            value: (!stateful && value && value.in) || null
        }, 'in');
        me.filter = filter;
        if (me.active) {
            me.setColumnActive(true);
            if (!stateful) {
                for (operator in value) {
                    me.addStoreFilter(me.filter[operator]);
                }
            }
        }
    },
    setValue: function(value) {
        var me = this,
            filters = me.filter,
            add = [],
            remove = [],
            active = false,
            filterCollection = me.getGridStore().getFilters(),
            field, filter, v, i, len, rLen, aLen;
        if (me.preventFilterRemoval) {
            return;
        }
        me.preventFilterRemoval = true;
        if ('eq' in value) {
            v = filters.lt.getValue();
            if (v || v === 0) {
                remove.push(filters.lt);
            }
            v = filters.gt.getValue();
            if (v || v === 0) {
                remove.push(filters.gt);
            }
            v = filters.in.getValue();
            if (v || v === 0) {
                remove.push(filters.in);
            }
            v = value.eq;
            if (v || v === 0) {
                add.push(filters.eq);
                filters.eq.setValue(v);
            } else {
                remove.push(filters.eq);
            }
        } else {
            v = filters.eq.getValue();
            if (v || v === 0) {
                remove.push(filters.eq);
            }
            if ('lt' in value) {
                v = value.lt;
                if (v || v === 0) {
                    add.push(filters.lt);
                    filters.lt.setValue(v);
                } else {
                    remove.push(filters.lt);
                }
            }
            if ('gt' in value) {
                v = value.gt;
                if (v || v === 0) {
                    add.push(filters.gt);
                    filters.gt.setValue(v);
                } else {
                    remove.push(filters.gt);
                }
            }
            if ('in' in value) {
                v = value.in;
                if (typeof v === "object" && v[0][0] === '') {
                    remove.push(filters.in);
                } else if (v || v === 0) {
                    add.push(filters.in);
                    filters.in.setValue(v);
                } else {
                    remove.push(filters.in);
                }
            }
        }
        rLen = remove.length;
        aLen = add.length;
        active = !!(me.countActiveFilters() + aLen - rLen);
        if (rLen || aLen || active !== me.active) {
            filterCollection.beginUpdate();
            if (rLen) {
                for (i = 0; i < rLen; i++) {
                    filter = remove[i];
                    me.fields[filter.getOperator()].setValue(null);
                    filter.setValue(null);
                    me.removeStoreFilter(filter);
                }
            }
            if (aLen) {
                for (i = 0; i < aLen; i++) {
                    me.addStoreFilter(add[i]);
                }
            }
            me.setActive(active);
            filterCollection.endUpdate();
        }
        me.preventFilterRemoval = false;
    }
});

Ext.define('Ext.grid.filters.filter.Number', {
    extend: 'Ext.local.grid.filters.filter.TriFilter',
    alias: ['grid.filter.number', 'grid.filter.numeric'],

    uses: ['Ext.form.field.Number'],

    type: 'number',

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
                iconCls: Ext.baseCSSPrefix + 'grid-filters-find',
                margin: 0
            }
        }
    },

    itemDefaults: {
        enableKeyEvents: true,
        hideEmptyLabel: false,
        labelSeparator: '',
        labelWidth: 29,
        selectOnFocus: false
    },

    menuDefaults: {
        bodyPadding: 3,
        showSeparator: false
    },

    createMenu: function() {
        var me = this,
            listeners = {
                scope: me,
                keyup: me.onValueChange,
                spin: {
                    fn: me.onInputSpin,
                    buffer: 200
                },
                el: {
                    click: me.stopFn
                }
            },
            itemDefaults = me.getItemDefaults(),
            menuItems = me.menuItems,
            fields = me.getFields(),
            field, i, len, key, item, cfg;

        me.callParent();

        me.fields = {};

        for (i = 0, len = menuItems.length; i < len; i++) {
            key = menuItems[i];

            if (key !== '-' && key !== 'in') {
                itemDefaults.xtype = 'numberfield';
                field = fields[key];

                cfg = {
                    labelClsExtra: Ext.baseCSSPrefix + 'grid-filters-icon ' + field.iconCls,
                    emptyText: 'Enter Number...'
                };

                if (itemDefaults) {
                    Ext.merge(cfg, itemDefaults);
                }

                Ext.merge(cfg, field);
                cfg.emptyText = cfg.emptyText || me.emptyText;
                delete cfg.iconCls;

                me.fields[key] = item = me.menu.add(cfg);

                item.filter = me.filter[key];
                item.filterKey = key;
                item.on(listeners);
            } else if (key === 'in') {
                itemDefaults.xtype = 'textfield';
                field = fields.in;

                cfg = {
                    labelClsExtra: Ext.baseCSSPrefix + 'grid-filters-icon ' + field.iconCls,
                    emptyText: 'Enter Numbers...'
                };

                if (itemDefaults) {
                    Ext.merge(cfg, itemDefaults);
                }

                Ext.merge(cfg, field);
                cfg.emptyText = cfg.emptyText || me.emptyText;
                delete cfg.iconCls;

                me.fields[key] = item = me.menu.add(cfg);

                item.filter = me.filter[key];
                item.filterKey = key;
                item.on(listeners);
            }
            else {
                me.menu.add(key);
            }
        }
    },
    getValue: function(field) {
        var value = {};

        value[field.filterKey] = field.getValue();

        return value;
    },
    onInputSpin: function(field, direction) {
        var value = {};

        value[field.filterKey] = field.getValue();

        this.setValue(value);
    },
    stopFn: function(e) {
        e.stopPropagation();
    }
});

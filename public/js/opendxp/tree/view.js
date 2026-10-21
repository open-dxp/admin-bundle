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

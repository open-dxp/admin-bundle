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

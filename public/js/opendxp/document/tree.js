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

Ext.define('documentreemodel', {
    extend: 'Ext.data.TreeModel',
    idProperty: 'id',
    fields: [{
        name: 'id',
        convert: undefined
    }, {
        name: 'name',
        convert: undefined
    }]
});

opendxp.registerNS('opendxp.document.tree');
/**
 * @private
 */
opendxp.document.tree = Class.create({

    treeDataUrl: null,

    initialize: function (config, perspectiveCfg) {
        this.treeDataUrl = Routing.generate('opendxp_admin_document_document_treegetchildrenbyid');
        this.perspectiveCfg = perspectiveCfg;
        if (!perspectiveCfg) {
            this.perspectiveCfg = {
                position: 'left'
            };
        }

        this.perspectiveCfg = new opendxp.perspective(this.perspectiveCfg);
        this.position = this.perspectiveCfg.position ? this.perspectiveCfg.position : 'left';

        if (!config) {
            this.config = {
                rootId: 1,
                rootVisible: true,
                loaderBaseParams: {},
                treeId: 'opendxp_panel_tree_documents',
                treeIconCls: 'opendxp_icon_main_tree_document opendxp_icon_material',
                treeTitle: t('documents'),
                parentPanel: Ext.getCmp('opendxp_panel_tree_' + this.position)
            };
        } else {
            this.config = config;
        }

        opendxp.layout.treepanelmanager.register(this.config.treeId);

        // get root node config
        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_document_document_treegetroot'),
            params: {
                id: this.config.rootId,
                view: this.config.customViewId,
                elementType: 'document'
            },
            success: function (response) {
                var res = Ext.decode(response.responseText);
                var callback = function () {};
                if (res['id']) {
                    callback = this.init.bind(this, res);
                }
                opendxp.layout.treepanelmanager.initPanel(this.config.treeId, callback);
            }.bind(this)
        });

    },

    init: function (rootNodeConfig) {

        var itemsPerPage = opendxp.settings['document_tree_paging_limit'];


        let rootNodeConfigText = t('home');
        let rootNodeConfigIconCls = 'opendxp_icon_home';
        if (this.config.customViewId !== undefined && rootNodeConfig.id !== 1) {
            rootNodeConfigText = rootNodeConfig.key;
            rootNodeConfigIconCls = rootNodeConfig.iconCls;
        }

        rootNodeConfig.text = rootNodeConfigText;
        rootNodeConfig.allowDrag = true;
        rootNodeConfig.iconCls = rootNodeConfigIconCls;
        rootNodeConfig.cls = 'opendxp_tree_node_root';
        rootNodeConfig.expanded = true;

        var store = Ext.create('opendxp.data.PagingTreeStore', {
            autoLoad: false,
            autoSync: false,
            proxy: {
                type: 'ajax',
                url: this.treeDataUrl,
                reader: {
                    type: 'json',
                    totalProperty: 'total',
                    rootProperty: 'nodes'

                },
                extraParams: {
                    limit: itemsPerPage,
                    view: this.config.customViewId
                }
            },
            pageSize: itemsPerPage,
            root: rootNodeConfig
        });


        // documents
        this.tree = Ext.create('opendxp.tree.Panel', {
            selModel: {
                mode: 'MULTI'
            },
            region: 'center',
            id: this.config.treeId,
            title: this.config.treeTitle,
            iconCls: this.config.treeIconCls,
            cls: this.config['rootVisible'] ? '' : 'opendxp_tree_no_root_node',
            autoScroll: true,
            autoLoad: false,
            animate: false,
            containerScroll: true,
            rootVisible: this.config.rootVisible,
            bufferedRenderer: false,
            border: false,
            viewConfig: {
                plugins: {
                    ptype: 'treeviewdragdrop',
                    appendOnly: false,
                    ddGroup: 'element'
                },
                listeners: {
                    nodedragover: this.onTreeNodeOver.bind(this)
                },
                xtype: 'opendxptreeview'
            },
            tools: [{
                type: 'right',
                handler: opendxp.layout.treepanelmanager.toRight.bind(this),
                hidden: this.position === 'right'
            }, {
                type: 'left',
                handler: opendxp.layout.treepanelmanager.toLeft.bind(this),
                hidden: this.position === 'left'
            }],
            // root: rootNodeConfig,
            store: store,
            listeners: this.getTreeNodeListeners()
        });

        this.tree.loadMask = new Ext.LoadMask({
            target: this.tree,
            msg: t('please_wait')
        });

        this.tree.on('itemmouseenter', opendxp.helpers.treeNodeThumbnailPreview.bind(this));
        this.tree.on('itemmouseleave', opendxp.helpers.treeNodeThumbnailPreviewHide.bind(this));

        store.on('nodebeforeexpand', function (node) {
            opendxp.helpers.addTreeNodeLoadingIndicator('document', node.data.id, false);
        });

        store.on('nodeexpand', function (node, index, item, eOpts) {
            opendxp.helpers.removeTreeNodeLoadingIndicator('document', node.data.id);
        });

        this.config.parentPanel.insert(this.config.index, this.tree);
        this.config.parentPanel.updateLayout();

        if (!this.config.parentPanel.alreadyExpanded && this.perspectiveCfg.expanded) {
            this.config.parentPanel.alreadyExpanded = true;
            this.tree.expand();
        }
    },

    getTreeNodeListeners: function () {
        return {
            itemclick: this.onTreeNodeClick,
            itemcontextmenu: this.onTreeNodeContextmenu.bind(this),
            itemmove: this.onTreeNodeMove.bind(this),
            beforeitemmove: this.onTreeNodeBeforeMove.bind(this),
            itemmouseenter: function (el, record, item, index, e, eOpts) {
                opendxp.helpers.treeToolTipShow(el, record, item);
            },
            itemmouseleave: function () {
                opendxp.helpers.treeToolTipHide();
            }
        };
    },

    onTreeNodeClick: function (tree, record, item, index, event, eOpts) {
        if (event.ctrlKey === false && event.shiftKey === false && event.altKey === false) {
            if (record.data.permissions && record.data.permissions.view) {
                opendxp.helpers.treeNodeThumbnailPreviewHide();
                opendxp.helpers.openDocument(record.data.id, record.data.type);
            }
        }
    },

    onTreeNodeOver: function (targetNode, position, dragData, e, eOpts) {
        var node = dragData.records[0];
        // check for permission
        try {
            if (node.data.permissions.settings) {
                return true;
            }
        } catch (e) {
            console.log(e);
        }

        return false;
    },


    onTreeNodeMove: function (node, oldParent, newParent, index, eOpts) {
        var tree = node.getOwnerTree();

        if (newParent.pagingData) {
            index += newParent.pagingData.offset;
        }

        var moveCallback = function (newParent, oldParent, tree, response) {
            try {
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new paths
                    var newBasePath = newParent.data.path;
                    if (newBasePath === '/') {
                        newBasePath = '';
                    }
                    node.data.basePath = newBasePath;
                    node.data.path = node.data.basePath + '/' + node.data.text;

                    if (!node.data.published) {
                        node.data.cls = 'opendxp_unpublished';
                        var view = tree.getView();
                        var nodeEl = Ext.fly(view.getNodeByRecord(node));
                        var nodeElInner = nodeEl.down('.x-grid-td');
                        if (nodeElInner) {
                            nodeElInner.addCls('opendxp_unpublished');
                        }
                    } else {
                        delete node.data.cls;
                    }
                    this.updateOpenDocumentPaths(node);

                } else {
                    tree.loadMask.hide();
                    opendxp.helpers.showNotification(t('error'), t('cant_move_node_to_target'),
                        'error', t(rdata.message));
                    // we have to delay refresh between two nodes,
                    // as there could be parent child relationship leading to race condition
                    window.setTimeout(function () {
                        opendxp.elementservice.refreshNode(oldParent);
                    }, 500);
                    opendxp.elementservice.refreshNode(newParent);
                }
            } catch (e) {
                tree.loadMask.hide();
                opendxp.helpers.showNotification(t('error'), t('cant_move_node_to_target'), 'error');
                // we have to delay refresh between two nodes,
                // as there could be parent child relationship leading to race condition
                window.setTimeout(function () {
                    opendxp.elementservice.refreshNode(oldParent);
                }, 500);
                opendxp.elementservice.refreshNode(newParent);
            }
            tree.loadMask.hide();

        }.bind(this, newParent, oldParent, tree);

        var params = {
            parentId: newParent.data.id,
            index: index
        };

        opendxp.elementservice.updateDocument(node.data.id, params, moveCallback);
    },

    onTreeNodeBeforeMove: function (node, oldParent, newParent, index, eOpts) {
        var tree = node.getOwnerTree();

        if (oldParent.getOwnerTree().getId() != newParent.getOwnerTree().getId()) {
            Ext.MessageBox.alert(t('error'), t('cross_tree_moves_not_supported'));
            return false;
        }

        // check for locks
        if (node.data.locked && oldParent.data.id != newParent.data.id) {
            Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
            return false;
        }

        // check new parent's permission
        if (!newParent.data.permissions.create) {
            Ext.MessageBox.alert(' ', t('element_cannot_be_moved'));
            return false;
        }

        if (opendxp.elementservice.isDisallowedDocumentKey(newParent.id, node.data.text)) {
            return false;
        }

        // check permissions
        if (node.data.permissions.settings) {
            tree.loadMask.show();
            return true;
        }

        return false;
    },

    onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts) {
        e.stopEvent();

        if (opendxp.helpers.hasTreeNodeLoadingIndicator('document', record.data.id)) {
            return;
        }

        var menu = new Ext.menu.Menu();
        var perspectiveCfg = this.perspectiveCfg;

        if (tree.getSelectionModel().getSelected().length > 1) {
            var selectedIds = [];
            tree.getSelectionModel().getSelected().each(function (item) {
                selectedIds.push(item.id);
            });

            if (record.data.permissions && record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu('document.delete')) {
                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: 'opendxp_icon_delete',
                    handler: this.deleteDocument.bind(this, selectedIds.join(','))
                }));
            }
        } else {
            var pasteMenu = [];
            var pasteInheritanceMenu = [];
            var childSupportedDocument = (record.data.type) ? opendxp.helpers.documentTypeHasSpecificRole(record.data.type, 'children_supported') : false;
            if (childSupportedDocument && record.data.permissions && record.data.permissions.create) {


                var addDocuments = perspectiveCfg.inTreeContextMenu('document.add');
                var addEmail = perspectiveCfg.inTreeContextMenu('document.addEmail');
                var addSnippet = perspectiveCfg.inTreeContextMenu('document.addSnippet');
                var addLink = perspectiveCfg.inTreeContextMenu('document.addLink');
                var addHardlink = perspectiveCfg.inTreeContextMenu('document.addHardlink');
                var addBlankDocument = perspectiveCfg.inTreeContextMenu('document.addBlankDocument');
                var addBlankEmail = perspectiveCfg.inTreeContextMenu('document.addBlankEmail');
                var addBlankSnippet = perspectiveCfg.inTreeContextMenu('document.addBlankSnippet');

                if (addDocuments) {
                    var documentMenu = {
                        page: [],
                        snippet: [],
                        email: [],
                        ref: this
                    };

                    documentMenu = this.populatePredefinedDocumentTypes(documentMenu, tree, record);

                    if (addBlankDocument) {
                        // empty page
                        documentMenu.page.push({
                            text: '&gt; ' + t('blank'),
                            iconCls: 'opendxp_icon_page opendxp_icon_overlay_add',
                            handler: this.addDocument.bind(this, tree, record, 'page')
                        });
                    }

                    if (addBlankSnippet) {
                        // empty snippet
                        documentMenu.snippet.push({
                            text: '&gt; ' + t('blank'),
                            iconCls: 'opendxp_icon_snippet opendxp_icon_overlay_add',
                            handler: this.addDocument.bind(this, tree, record, 'snippet')
                        });
                    }

                    if (addBlankEmail) {
                        // empty email
                        documentMenu.email.push({
                            text: '&gt; ' + t('blank'),
                            iconCls: 'opendxp_icon_email opendxp_icon_overlay_add',
                            handler: this.addDocument.bind(this, tree, record, 'email')
                        });
                    }

                    //don't add pages below print containers - makes no sense
                    if (addDocuments && !opendxp.helpers.documentTypeHasSpecificRole(record.data.type, 'only_printable_childrens')) {
                        menu.add(new Ext.menu.Item({
                            text: t('add_page'),
                            iconCls: 'opendxp_icon_page opendxp_icon_overlay_add',
                            menu: documentMenu.page,
                            hideOnClick: false
                        }));
                    }

                    if (addSnippet) {
                        menu.add(new Ext.menu.Item({
                            text: t('add_snippet'),
                            iconCls: 'opendxp_icon_snippet opendxp_icon_overlay_add',
                            menu: documentMenu.snippet,
                            hideOnClick: false
                        }));
                    }

                    //don't add emails and links below print containers - makes no sense
                    if (addDocuments && !opendxp.helpers.documentTypeHasSpecificRole(record.data.type, 'only_printable_childrens')) {
                        if (addLink) {
                            menu.add(new Ext.menu.Item({
                                text: t('add_link'),
                                iconCls: 'opendxp_icon_link opendxp_icon_overlay_add',
                                handler: this.addDocument.bind(this, tree, record, 'link')
                            }));
                        }

                        if (addEmail) {
                            menu.add(new Ext.menu.Item({
                                text: t('add_email'),
                                iconCls: 'opendxp_icon_email opendxp_icon_overlay_add',
                                menu: documentMenu.email,
                                hideOnClick: false
                            }));
                        }
                    }

                    if (addHardlink) {
                        menu.add(new Ext.menu.Item({
                            text: t('add_hardlink'),
                            iconCls: 'opendxp_icon_hardlink opendxp_icon_overlay_add',
                            handler: this.addDocument.bind(this, tree, record, 'hardlink')
                        }));
                    }
                }

                if (perspectiveCfg.inTreeContextMenu('document.addFolder')) {

                    menu.add(new Ext.menu.Item({
                        text: t('create_folder'),
                        iconCls: 'opendxp_icon_folder opendxp_icon_overlay_add',
                        handler: this.addDocument.bind(this, tree, record, 'folder')
                    }));
                }

                menu.add('-');

                //paste
                if (opendxp.cachedDocumentId && record.data.permissions && record.data.permissions.create && perspectiveCfg.inTreeContextMenu('document.paste')) {
                    pasteMenu.push({
                        text: t('paste_recursive_as_child'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteInfo.bind(this, tree, record, 'recursive')
                    });
                    pasteMenu.push({
                        text: t('paste_recursive_updating_references'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteInfo.bind(this, tree, record, 'recursive-update-references')
                    });
                    pasteMenu.push({
                        text: t('paste_as_child'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteInfo.bind(this, tree, record, 'child')
                    });

                    pasteMenu.push({
                        text: t('paste_as_language_variant'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteLanguageDocument.bind(this, tree, record, 'child')
                    });

                    pasteMenu.push({
                        text: t('paste_recursive_as_language_variant'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteLanguageDocument.bind(this, tree, record, 'recursive')
                    });

                    pasteMenu.push({
                        text: t('paste_recursive_as_language_variant_updating_references'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteLanguageDocument.bind(this, tree, record, 'recursive-update-references')
                    });

                    pasteInheritanceMenu.push({
                        text: t('paste_recursive_as_child'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteInfo.bind(this, tree, record, 'recursive', true)
                    });
                    pasteInheritanceMenu.push({
                        text: t('paste_recursive_updating_references'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteInfo.bind(this, tree, record, 'recursive-update-references', true)
                    });
                    pasteInheritanceMenu.push({
                        text: t('paste_as_child'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteInfo.bind(this, tree, record, 'child', true)
                    });

                    pasteInheritanceMenu.push({
                        text: t('paste_as_language_variant'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteLanguageDocument.bind(this, tree, record, 'child', true)
                    });

                    pasteInheritanceMenu.push({
                        text: t('paste_recursive_as_language_variant'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteLanguageDocument.bind(this, tree, record, 'recursive', true)
                    });

                    pasteInheritanceMenu.push({
                        text: t('paste_recursive_as_language_variant_updating_references'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteLanguageDocument.bind(this, tree, record, 'recursive-update-references', true)
                    });
                }
            }

            //paste
            if (childSupportedDocument && opendxp.cutDocument && record.data.permissions && record.data.permissions.create && perspectiveCfg.inTreeContextMenu('document.pasteCut')) {
                pasteMenu.push({
                    text: t('paste_cut_element'),
                    iconCls: 'opendxp_icon_paste',
                    handler: function () {
                        this.pasteCutDocument(opendxp.cutDocument,
                            opendxp.cutDocumentParentNode, record, this.tree);
                        opendxp.cutDocumentParentNode = null;
                        opendxp.cutDocument = null;
                    }.bind(this)
                });
            }

            if (opendxp.cachedDocumentId && record.data.permissions && record.data.permissions.create && perspectiveCfg.inTreeContextMenu('document.paste')) {

                if (record.data.type !== 'folder') {
                    pasteMenu.push({
                        text: t('paste_contents'),
                        iconCls: 'opendxp_icon_paste',
                        handler: this.pasteInfo.bind(this, tree, record, 'replace')
                    });
                }
            }

            if (pasteMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('paste'),
                    iconCls: 'opendxp_icon_paste',
                    hideOnClick: false,
                    menu: pasteMenu
                }));
            }

            if (pasteInheritanceMenu.length > 0) {
                menu.add(new Ext.menu.Item({
                    text: t('paste_inheritance'),
                    iconCls: 'opendxp_icon_paste',
                    hideOnClick: false,
                    menu: pasteInheritanceMenu
                }));
            }

            if (record.data.permissions && record.data.permissions.view && perspectiveCfg.inTreeContextMenu('document.copy')) {
                menu.add(new Ext.menu.Item({
                    text: t('copy'),
                    iconCls: 'opendxp_icon_copy',
                    handler: this.copy.bind(this, tree, record)
                }));
            }

            if (record.data.id != 1 && !record.data.locked && record.data.permissions && record.data.permissions.rename && perspectiveCfg.inTreeContextMenu('document.cut')) {
                menu.add(new Ext.menu.Item({
                    text: t('cut'),
                    iconCls: 'opendxp_icon_cut',
                    handler: this.cut.bind(this, tree, record)
                }));
            }

            if (record.data.permissions && record.data.permissions.rename && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu('document.rename')) {
                menu.add(new Ext.menu.Item({
                    text: t('rename'),
                    iconCls: 'opendxp_icon_key opendxp_icon_overlay_go',
                    handler: this.editDocumentKey.bind(this, tree, record)
                }));
            }

            //publish
            if (record.data.type !== 'folder' && !record.data.locked) {
                if (record.data.published && record.data.permissions && record.data.permissions.unpublish && perspectiveCfg.inTreeContextMenu('document.unpublish')) {
                    menu.add(new Ext.menu.Item({
                        text: t('unpublish'),
                        iconCls: 'opendxp_icon_unpublish',
                        handler: this.publishDocument.bind(this, tree, record, 'unpublish')
                    }));
                } else if (!record.data.published && record.data.permissions && record.data.permissions.publish && perspectiveCfg.inTreeContextMenu('document.publish')) {
                    menu.add(new Ext.menu.Item({
                        text: t('publish'),
                        iconCls: 'opendxp_icon_publish',
                        handler: this.publishDocument.bind(this, tree, record, 'publish')
                    }));
                }
            }

            if (record.data.permissions && record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu('document.delete')) {
                menu.add(new Ext.menu.Item({
                    text: t('delete'),
                    iconCls: 'opendxp_icon_delete',
                    handler: this.deleteDocument.bind(this, record.data.id)
                }));
            }

            if ((record.data.type === 'page' || record.data.type === 'hardlink') && record.data.permissions && record.data.permissions.view && perspectiveCfg.inTreeContextMenu('document.open')) {
                menu.add(new Ext.menu.Item({
                    text: t('open_in_new_window'),
                    iconCls: 'opendxp_icon_open_window',
                    handler: function () {
                        window.open(record.data.url);
                    }.bind(this)
                }));
            }

            // advanced menu
            var advancedMenuItems = [];
            var user = opendxp.globalmanager.get('user');

            if (record.data.id != 1 && record.data.permissions && record.data.permissions.publish && !record.data.locked && perspectiveCfg.inTreeContextMenu('document.convert')) {

                let conversionTargets = [];
                if (addDocuments) {
                    conversionTargets.push({
                        text: t('page'),
                        iconCls: 'opendxp_icon_page',
                        handler: this.convert.bind(this, tree, record, 'page'),
                        hidden: record.data.type === 'page'
                    });
                }
                if (addSnippet) {
                    conversionTargets.push({
                        text: t('snippet'),
                        iconCls: 'opendxp_icon_snippet',
                        handler: this.convert.bind(this, tree, record, 'snippet'),
                        hidden: record.data.type === 'snippet' || !addSnippet
                    });
                }
                if (addEmail) {
                    conversionTargets.push({
                        text: t('email'),
                        iconCls: 'opendxp_icon_email',
                        handler: this.convert.bind(this, tree, record, 'email'),
                        hidden: record.data.type === 'email' || !addEmail
                    });
                }
                if (addLink) {
                    conversionTargets.push({
                        text: t('link'),
                        iconCls: 'opendxp_icon_link',
                        handler: this.convert.bind(this, tree, record, 'link'),
                        hidden: record.data.type === 'link' || !addLink
                    });
                }
                if (addHardlink) {
                    conversionTargets.push({
                        text: t('hardlink'),
                        iconCls: 'opendxp_icon_hardlink',
                        handler: this.convert.bind(this, tree, record, 'hardlink'),
                        hidden: record.data.type === 'hardlink' || !addHardlink
                    });
                }

                if (conversionTargets.length > 0) {
                    advancedMenuItems.push(new Ext.menu.Item({
                        text: t('convert_to'),
                        iconCls: 'opendxp_icon_convert',
                        hideOnClick: false,
                        menu: conversionTargets
                    }));
                }
            }

            if (childSupportedDocument &&
                record.data.permissions &&
                record.data.permissions.create &&
                perspectiveCfg.inTreeContextMenu('document.searchAndMove') &&
                opendxp.helpers.hasSearchImplementation()) {
                advancedMenuItems.push({
                    text: t('search_and_move'),
                    iconCls: 'opendxp_icon_search opendxp_icon_overlay_go',
                    handler: this.searchAndMove.bind(this, tree, record)
                });
            }

            if (record.data.id != 1 && record.data.type === 'page' && (user.admin || user.isAllowed('sites'))) {
                if (!record.data.site) {
                    if (perspectiveCfg.inTreeContextMenu('document.useAsSite')) {
                        advancedMenuItems.push({
                            iconCls: 'opendxp_icon_site',
                            text: t('use_as_site'),
                            handler: this.addUpdateSite.bind(this, tree, record)
                        });
                    }
                } else {
                    if (perspectiveCfg.inTreeContextMenu('document.editSite')) {
                        advancedMenuItems.push({
                            text: t('edit_site'),
                            handler: this.addUpdateSite.bind(this, tree, record),
                            iconCls: 'opendxp_icon_edit',
                        });
                    }

                    if (perspectiveCfg.inTreeContextMenu('document.removeSite')) {
                        advancedMenuItems.push({
                            text: t('remove_site'),
                            handler: this.removeSite.bind(this, tree, record),
                            iconCls: 'opendxp_icon_delete',
                        });
                    }
                }

            }

            if (record.data.id != 1 && user.admin) { // only admins are allowed to change locks in frontend
                var lockMenu = [];
                if (record.data.lockOwner) { // add unlock
                    if (perspectiveCfg.inTreeContextMenu('document.unlock')) {
                        lockMenu.push({
                            text: t('unlock'),
                            iconCls: 'opendxp_icon_lock opendxp_icon_overlay_delete',
                            handler: function () {
                                opendxp.elementservice.lockElement({
                                    elementType: 'document',
                                    id: record.data.id,
                                    mode: null
                                });
                            }.bind(this)
                        });
                    }
                } else {
                    if (perspectiveCfg.inTreeContextMenu('document.lock')) {
                        lockMenu.push({
                            text: t('lock'),
                            iconCls: 'opendxp_icon_lock opendxp_icon_overlay_add',
                            handler: function () {
                                opendxp.elementservice.lockElement({
                                    elementType: 'document',
                                    id: record.data.id,
                                    mode: 'self'
                                });
                            }.bind(this)
                        });
                    }

                    if (perspectiveCfg.inTreeContextMenu('document.lockAndPropagate')) {
                        if (record.data.type !== 'snippet') {
                            lockMenu.push({
                                text: t('lock_and_propagate_to_children'),
                                iconCls: 'opendxp_icon_lock opendxp_icon_overlay_go',
                                handler: function () {
                                    opendxp.elementservice.lockElement({
                                        elementType: 'document',
                                        id: record.data.id,
                                        mode: 'propagate'
                                    });
                                }.bind(this)
                            });
                        }
                    }
                }

                if (record.data['locked'] && perspectiveCfg.inTreeContextMenu('document.unlockAndPropagate')) {
                    // add unlock and propagate to children functionality
                    lockMenu.push({
                        text: t('unlock_and_propagate_to_children'),
                        iconCls: 'opendxp_icon_lock opendxp_icon_overlay_delete',
                        handler: function () {
                            opendxp.elementservice.unlockElement({
                                elementType: 'document',
                                id: record.data.id
                            });
                        }.bind(this)
                    });
                }

                if (lockMenu.length > 0) {
                    advancedMenuItems.push({
                        text: t('lock'),
                        iconCls: 'opendxp_icon_lock',
                        hideOnClick: false,
                        menu: lockMenu
                    });
                }
            }

            // expand and collapse complete tree
            if (!record.data.leaf) {
                if (record.data.expanded) {
                    advancedMenuItems.push({
                        text: t('collapse_children'),
                        iconCls: 'opendxp_icon_collapse_children',
                        handler: function () {
                            record.collapse(true);
                        }.bind(this, record)
                    });
                } else {
                    advancedMenuItems.push({
                        text: t('expand_children'),
                        iconCls: 'opendxp_icon_expand_children',
                        handler: function () {
                            record.expand(true);
                        }.bind(this, record)
                    });
                }
            }

            menu.add('-');

            if (advancedMenuItems.length) {
                menu.add(new Ext.menu.Item({
                    text: t('advanced'),
                    iconCls: 'opendxp_icon_more',
                    hideOnClick: false,
                    menu: advancedMenuItems
                }));
            }

            if (!record.data.leaf && perspectiveCfg.inTreeContextMenu('document.reload')) {
                menu.add(new Ext.menu.Item({
                    text: t('refresh'),
                    iconCls: 'opendxp_icon_reload',
                    handler: opendxp.elementservice.refreshNode.bind(this, record)
                }));
            }
        }

        opendxp.helpers.hideRedundantSeparators(menu);

        const prepareDocumentTreeContextMenu = new CustomEvent(opendxp.events.prepareDocumentTreeContextMenu, {
            detail: {
                menu: menu,
                tree: this,
                document: record
            }
        });

        document.dispatchEvent(prepareDocumentTreeContextMenu);

        menu.showAt(e.pageX + 1, e.pageY + 1);
    },

    pasteLanguageDocument: function (tree, record, type, enableInheritance) {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_document_document_translationchecklanguage'),
            params: {
                path: opendxp.cachedDocument.data.path
            },
            success: function (response) {
                var data = Ext.decode(response.responseText);

                if (data.language === '') {
                    opendxp.helpers.showNotification(t('error'), t('source_document_language_missing'), 'error');
                    return false;
                }

                var languagestore = [];
                var websiteLanguages = opendxp.settings.websiteLanguages;
                var selectContent = '';

                for (var i = 0; i < websiteLanguages.length; i++) {
                    if (data.language != websiteLanguages[i] && !in_array(websiteLanguages[i], data.translationLinks)) {
                        selectContent = opendxp.available_languages[websiteLanguages[i]] + ' [' + websiteLanguages[i] + ']';
                        languagestore.push([websiteLanguages[i], selectContent]);
                    }
                }

                if (languagestore.length < 1) {
                    opendxp.helpers.showNotification(t('error'), t('paste_no_new_language_error'), 'error');
                    return false;
                }

                var pageForm = new Ext.form.FormPanel({
                    title: t('select_language_for_new_document'),
                    border: false,
                    bodyStyle: 'padding: 10px;',
                    defaults: {
                        labelWidth: 100
                    },
                    items: [{
                        xtype: 'combo',
                        name: 'language',
                        fieldLabel: t('language'),
                        store: languagestore,
                        editable: false,
                        triggerAction: 'all',
                        mode: 'local',
                    }]
                });

                var win = new Ext.Window({
                    width: 350,
                    bodyStyle: 'padding: 0px 0px 10px 0px',
                    items: [pageForm],
                    title: t('paste_as_language_variant'),
                    buttons: [{
                        text: t('cancel'),
                        iconCls: 'opendxp_icon_cancel',
                        handler: function () {
                            win.close();
                        }
                    }, {
                        text: t('apply'),
                        iconCls: 'opendxp_icon_apply',
                        handler: function () {
                            var params = pageForm.getForm().getFieldValues();

                            win.close();

                            this.pasteInfo(tree, record, type, enableInheritance, params.language);
                        }.bind(this)
                    }]
                });

                win.show();
            }.bind(this)
        });

    },

    populatePredefinedDocumentTypes: function (documentMenu, tree, record) {
        var document_types = opendxp.globalmanager.get('document_types_store');

        var groups = {
            page: {},
            snippet: {},
            email: {},
        };

        document_types.sort([
            {property: 'priority', direction: 'ASC'},
            {property: 'translatedGroup', direction: 'ASC'},
            {property: 'translatedName', direction: 'ASC'}
        ]);

        document_types.each(function (documentMenu, typeRecord) {
            menuOption = undefined;
            var text = Ext.util.Format.htmlEncode(typeRecord.get('translatedName'));
            if (typeRecord.get('type') === 'page') {
                docTypeMenu = {
                    text: text,
                    iconCls: 'opendxp_icon_page opendxp_icon_overlay_add',
                    handler: this.addDocument.bind(this, tree, record, 'page', typeRecord.get('id'))
                };
                menuOption = 'page';
            } else if (typeRecord.get('type') === 'snippet') {
                docTypeMenu = {
                    text: text,
                    iconCls: 'opendxp_icon_snippet opendxp_icon_overlay_add',
                    handler: this.addDocument.bind(this, tree, record, 'snippet', typeRecord.get('id'))
                };
                menuOption = 'snippet';
            } else if (typeRecord.get('type') === 'email') {
                docTypeMenu = {
                    text: text,
                    iconCls: 'opendxp_icon_email opendxp_icon_overlay_add',
                    handler: this.addDocument.bind(this, tree, record, 'email', typeRecord.get('id'))
                };
                menuOption = 'email';
            }

            if (menuOption === undefined) {
                return;
            }

            // check if the class is within a group
            if (typeRecord.get('group')) {

                if (!groups[menuOption][typeRecord.get('group')]) {
                    groups[menuOption][typeRecord.get('group')] = {
                        text: Ext.util.Format.htmlEncode(typeRecord.get('translatedGroup')),
                        iconCls: 'opendxp_icon_folder',
                        hideOnClick: false,
                        menu: {
                            items: []
                        }
                    };
                    documentMenu[menuOption].push(groups[menuOption][typeRecord.get('group')]);
                }
                groups[menuOption][typeRecord.get('group')]['menu']['items'].push(docTypeMenu);
            } else {
                documentMenu[menuOption].push(docTypeMenu);
            }

        }.bind(this, documentMenu), documentMenu);

        return documentMenu;
    },

    copy: function (tree, record) {
        opendxp.cachedDocument = record;
        opendxp.cachedDocumentId = record.data.id;
    },

    cut: function (tree, record) {
        opendxp.cutDocument = record;
        opendxp.cutDocumentParentNode = record.parentNode;
    },

    pasteCutDocument: function (document, oldParent, newParent, tree) {
        opendxp.elementservice.updateDocument(document.id, {
            parentId: newParent.id
        }, function (document, newParent, oldParent, tree, response) {
            try {
                var rdata = Ext.decode(response.responseText);
                if (rdata && rdata.success) {
                    // set new pathes
                    var newBasePath = newParent.data.path;
                    if (newBasePath === '/') {
                        newBasePath = '';
                    }
                    document.data.basePath = newBasePath;
                    document.data.path = document.data.basePath + '/' + document.data.text;
                } else {
                    tree.loadMask.hide();
                    opendxp.helpers.showNotification(t('error'), t('cant_move_node_to_target'), 'error', t(rdata.message));

                }
            } catch (e) {
                opendxp.helpers.showNotification(t('error'), t('cant_move_node_to_target'), 'error');
            }

            opendxp.elementservice.refreshNodeAllTrees('document', oldParent.id);
            opendxp.elementservice.refreshNodeAllTrees('document', newParent.id);
            newParent.expand();
            this.tree.loadMask.hide();
            this.updateOpenDocumentPaths(document);

        }.bind(this, document, newParent, oldParent, tree));

    },

    pasteInfo: function (tree, record, type, enableInheritance, language) {
        opendxp.helpers.addTreeNodeLoadingIndicator('document', record.get('id'));
        if (enableInheritance !== true) {
            enableInheritance = false;
        }

        const params = {
            targetId: record.data.id,
            sourceId: opendxp.cachedDocumentId,
            type: type,
            enableInheritance: enableInheritance
        };

        if (typeof language === 'string') {
            params.language = language;
        }

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_document_document_copyinfo'),
            params: params,
            success: this.paste.bind(this, tree, record)
        });
    },

    paste: function (tree, record, response) {

        try {
            var res = Ext.decode(response.responseText);

            if (res.pastejobs) {

                record.pasteProgressBar = new Ext.ProgressBar({
                    text: t('initializing')
                });

                record.pasteWindow = new Ext.Window({
                    title: t('paste'),
                    layout: 'fit',
                    width: 200,
                    bodyStyle: 'padding: 10px;',
                    closable: false,
                    plain: true,
                    items: [record.pasteProgressBar],
                    listeners: opendxp.helpers.getProgressWindowListeners()
                });

                record.pasteWindow.show();


                var pj = new opendxp.tool.paralleljobs({
                    success: function () {

                        try {
                            this.pasteComplete(record);
                        } catch (e) {
                            console.log(e);
                            opendxp.helpers.showNotification(t('error'), t('error_pasting_item'), 'error');
                            opendxp.elementservice.refreshNodeAllTrees('document', record.id);
                        }
                    }.bind(this),
                    update: function (currentStep, steps, percent) {
                        if (record.pasteProgressBar) {
                            var status = currentStep / steps;
                            record.pasteProgressBar.updateProgress(status, percent + '%');
                        }
                    }.bind(this),
                    failure: function (message) {
                        record.pasteWindow.close();
                        record.pasteProgressBar = null;

                        opendxp.helpers.showNotification(t('error'), t('error_pasting_item'), 'error', t(message));
                        opendxp.elementservice.refreshNodeAllTrees('document', record.id);
                    }.bind(this),
                    jobs: res.pastejobs
                });
            } else {
                throw 'There are no pasting jobs';
            }
        } catch (e) {
            Ext.MessageBox.alert(t('error'), e);
            this.pasteComplete(this);
        }
    },

    pasteComplete: function (node) {
        if (node.pasteWindow) {
            node.pasteWindow.close();
        }

        node.pasteProgressBar = null;
        node.pasteWindow = null;

        //this.tree.loadMask.hide();
        opendxp.helpers.removeTreeNodeLoadingIndicator('document', node.id);
        opendxp.elementservice.refreshNodeAllTrees('document', node.id);
    },

    removeSite: function (tree, record) {
        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_document_document_removesite'),
            method: 'DELETE',
            params: {
                id: record.data.id
            },
            success: function () {
                opendxp.globalmanager.get('sites').reload();
                opendxp.elementservice.refreshNode(record.parentNode);
            }.bind(this)
        });

        delete record.data.site;
    },

    addUpdateSite: function (tree, record) {

        var win,
            windowCfg,
            siteItems,
            title = '',
            data = {
                domains: [],
                mainDomain: '',
                errorDocument: '',
                localizedErrorDocuments: [],
                redirectToMainDomain: false,
                customSettings: []
            };

        if (record.get('site')) {
            data = record.get('site');
            title = t('site_id') + ': ' + data['id'];
        }

        siteItems = [
            {
                xtype: 'textfield',
                name: 'mainDomain',
                fieldLabel: t('main_domain'),
                value: data['mainDomain']
            },
            {
                xtype: 'textarea',
                name: 'domains',
                height: 150,
                style: 'word-wrap: normal;',
                fieldLabel: t('additional_domains') + '<br /><br />' + t('wildcards_are_supported') + ' (*example.com)',
                value: data.domains.join("\n")
            },
            {
                xtype: 'textfield',
                name: 'errorDocument',
                fieldCls: 'input_drop_target',
                fieldLabel: t('error_page') + ' (' + t('default') + ')',
                value: data['errorDocument'],
                listeners: {
                    render: function (el) {
                        new Ext.dd.DropZone(el.getEl(), {
                            reference: this,
                            ddGroup: 'element',
                            getTargetFromEvent: function (e) {
                                return this.getEl();
                            }.bind(el),

                            onNodeOver: function (target, dd, e, data) {
                                if (
                                    data.records.length === 1 &&
                                    data.records[0].data.elementType === 'document' &&
                                    in_array(data.records[0].data.type, ['page', 'link', 'hardlink'])
                                ) {
                                    return Ext.dd.DropZone.prototype.dropAllowed;
                                }
                            },

                            onNodeDrop: function (target, dd, e, data) {

                                if (!opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                                    return false;
                                }

                                data = data.records[0].data;

                                if (
                                    data.elementType === 'document' &&
                                    in_array(data.type, ['page', 'link', 'hardlink'])
                                ) {
                                    this.setValue(data.path);
                                    return true;
                                }

                                return false;
                            }.bind(el)
                        });
                    }
                }
            },
            {
                xtype: 'container',
                style: 'margin-top: 20px;',
                items: this.renderErrorDocuments(data['localizedErrorDocuments']),
            },
            {
                xtype: 'checkbox',
                name: 'redirectToMainDomain',
                fieldLabel: t('redirect_to_main_domain'),
                checked: data['redirectToMainDomain']
            },
            {
                xtype: 'form',
                style: 'margin-top: 20px;',
                title: t('site_custom_settings'),
                hidden: true,
                border: false,
                items: [],
                listeners: {
                    render: function (el) {
                        this.renderSiteCustomSettings(el, data)
                    }.bind(this)
                }
            }
        ];

        windowCfg = {
            width: 600,
            height: 600,
            layout: 'fit',
            closeAction: 'close',
            items: [{
                autoScroll: true,
                xtype: 'form',
                bodyStyle: 'padding: 10px;',
                defaults: {
                    labelWidth: 250,
                    width: 560
                },
                itemId: 'form',
                items: siteItems
            }],
            buttons: [{
                text: t('cancel'),
                iconCls: 'opendxp_icon_cancel',
                handler: function () {
                    win.close();
                }
            }, {
                text: t('apply'),
                iconCls: 'opendxp_icon_apply',
                handler: function () {

                    const form = win.getComponent('form').getForm();
                    const data = form.getFieldValues();

                    if (!form.isValid()) {
                        return;
                    }

                    data['id'] = record.id;

                    Ext.Ajax.request({
                        url: Routing.generate('opendxp_admin_document_document_updatesite'),
                        method: 'PUT',
                        params: data,
                        success: function (tree, record, response) {
                            record.data.site = Ext.decode(response.responseText);
                            tree.getStore().load({
                                node: record.parentNode
                            });
                            opendxp.globalmanager.get('sites').reload();
                        }.bind(this, tree, record)
                    });

                    win.close();
                }.bind(this)
            }]
        };

        if (title) {
            windowCfg.title = title;
        }

        win = new Ext.Window(windowCfg);

        win.show();
    },

    addDocument: function (tree, record, type, docTypeId) {
        var textKeyTitle;
        var textKeyMessage;

        if (type === 'page') {

            textKeyTitle = t('add_page');
            textKeyMessage = t('enter_the_name_of_the_new_item');

            //create a custom form
            var pageForm = new Ext.form.FormPanel({
                title: textKeyMessage,
                border: false,
                bodyStyle: 'padding: 10px;',
                items: [{
                    xtype: 'textfield',
                    itemId: 'title',
                    fieldLabel: t('title'),
                    name: 'title',
                    width: '100%',
                    enableKeyEvents: true,
                    listeners: {
                        afterrender: function () {
                            window.setTimeout(function () {
                                this.focus(true);
                            }.bind(this), 100);
                        },
                        keyup: function (el) {
                            pageForm.getComponent('name').setValue(el.getValue());
                            pageForm.getComponent('key').setValue(el.getValue());
                        }.bind(this)
                    }
                }, {
                    xtype: 'textfield',
                    itemId: 'name',
                    fieldLabel: t('navigation'),
                    name: 'name',
                    width: '100%'
                }, {
                    xtype: 'textfield',
                    width: '100%',
                    fieldLabel: t('key'),
                    itemId: 'key',
                    name: 'key'
                }]
            });

            var submitFunction = function () {
                var params = pageForm.getForm().getFieldValues();
                messageBox.close();
                if (params['key'].length >= 1) {
                    params['type'] = type;
                    params['docTypeId'] = docTypeId;
                    this.addDocumentCreate(tree, record, params);
                } else {
                    return; //ignore
                }
            };

            //create a custom MessageBox
            var messageBox = new Ext.Window({
                modal: true,
                width: 400,
                title: textKeyTitle,
                items: pageForm,
                buttons: [{
                    text: t('OK'),
                    handler: submitFunction.bind(this, tree, record)
                }, {
                    text: t('cancel'),
                    handler: function () {
                        messageBox.close();
                    }
                }]
            });

            messageBox.show();

            var map = new Ext.util.KeyMap({
                target: messageBox.getEl(),
                key: Ext.event.Event.ENTER,
                fn: submitFunction.bind(this)
            });

        } else {

            if (type === 'folder') {
                textKeyTitle = t('create_folder');
                textKeyMessage = t('enter_the_name_of_the_new_item');
            } else {
                textKeyTitle = t('add_' + type);
                textKeyMessage = t('enter_the_name_of_the_new_item');
            }

            Ext.MessageBox.prompt(textKeyTitle, textKeyMessage, function (tree, record, type, docTypeId, button, value, object) {
                if (button === 'ok') {

                    this.addDocumentCreate(
                        tree, record,
                        {
                            key: value,
                            type: type,
                            docTypeId: docTypeId
                        });
                }
            }.bind(this, tree, record, type, docTypeId));
        }
    },

    publishDocument: function (tree, record, task) {
        var id = record.data.id;
        var type = record.data.type;

        var parameters = {};
        parameters.id = id;

        var doc = opendxp.globalmanager.get('document_' + id);

        if (doc) {
            if (task === 'publish') {
                doc.publish(false);
            } else {
                doc.unpublish(false);
            }
        } else {
            Ext.Ajax.request({
                url: Routing.generate('opendxp_admin_document_' + type + '_save', {task: task}),
                method: 'PUT',
                params: parameters,
                success: function (task, response) {
                    try {
                        var rdata = Ext.decode(response.responseText);
                        if (rdata && rdata.success) {
                            var options = {
                                elementType: 'document',
                                id: record.data.id,
                                published: task !== 'unpublish'
                            };
                            opendxp.elementservice.setElementPublishedState(options);
                            opendxp.elementservice.setElementToolbarButtons(options);
                            opendxp.elementservice.reloadVersions(options);

                            opendxp.helpers.showNotification(t('success'), t('successful_' + task + '_document'),
                                'success');
                        } else {
                            opendxp.helpers.showNotification(t('error'), t('error_' + task + '_document'),
                                'error', t(rdata.message));
                        }
                    } catch (e) {
                        opendxp.helpers.showNotification(t('error'), t('error_' + task + '_document'), 'error');
                    }

                }.bind(this, task)
            });
        }
    },

    addDocumentCreate: function (tree, record, params) {

        if (params['key']) {
            // check for ident filename in current level
            if (opendxp.elementservice.isKeyExistingInLevel(record, params['key'])) {
                return;
            }

            if (opendxp.elementservice.isDisallowedDocumentKey(record.id, params['key'])) {
                return;
            }

            params['sourceTree'] = tree;
            params['elementType'] = 'document';
            params['key'] = opendxp.helpers.getValidFilename(params['key'], 'document');
            params['index'] = record.childNodes.length;
            params['parentId'] = record.id;
            params['url'] = Routing.generate('opendxp_admin_document_document_add');
            opendxp.elementservice.addDocument(params);
        }
    },

    editDocumentKey: function (tree, record) {
        var options = {
            sourceTree: tree,
            elementType: 'document',
            elementSubType: record.data.type,
            id: record.data.id,
            default: record.data.key
        };
        opendxp.elementservice.editElementKey(options);
    },

    deleteDocument: function (ids) {
        var options = {
            'elementType': 'document',
            'id': ids
        };
        opendxp.elementservice.deleteElement(options);
    },

    convert: function (tree, record, type) {
        Ext.MessageBox.show({
            title: t('are_you_sure'),
            msg: t('all_content_will_be_lost'),
            buttons: Ext.Msg.OKCANCEL,
            icon: Ext.MessageBox.INFO,
            fn: function (type, button) {
                if (button === 'ok') {

                    if (opendxp.globalmanager.exists('document_' + record.data.id)) {
                        var tabPanel = Ext.getCmp('opendxp_panel_tabs');
                        tabPanel.remove('document_' + record.data.id);
                    }

                    Ext.Ajax.request({
                        url: Routing.generate('opendxp_admin_document_document_convert'),
                        method: 'PUT',
                        params: {
                            id: record.data.id,
                            type: type
                        },
                        success: function () {
                            opendxp.elementservice.refreshNodeAllTrees('document', record.parentNode.id);
                        }.bind(this)
                    });
                }
            }.bind(this, type)
        });
    },

    searchAndMove: function (tree, record) {
        var parentId = record.data.id;
        opendxp.helpers.searchAndMove(parentId, function () {
            opendxp.elementservice.refreshNode(record);
        }.bind(this), 'document');
    },

    isKeyValid: function (key) {

        // key must be at least one character, an maximum 30 characters
        if (key.length < 1 && key.length > 30) {
            return false;
        }
    },

    updateOpenDocumentPaths: function (node) {
        try {
            var openTabs = opendxp.helpers.getOpenTab();
            for (var i = 0; i < openTabs.length; i++) {
                if (openTabs[i].indexOf('document_') === 0 && (openTabs[i].indexOf('_page') || openTabs[i].indexOf('_snippet') || openTabs[i].indexOf('_email'))) {
                    var documentElement = opendxp.globalmanager.get(openTabs[i].replace(/_page|_snippet|_email/gi, ''));
                    if (typeof documentElement.data !== 'undefined' && documentElement.data.idPath.indexOf('/' + node.data.id) > 0) {
                        documentElement.resetPath();
                    }
                }
            }
        } catch (e) {
            console.log(e);
        }
    },

    renderErrorDocuments: function (localizedErrorDocumentsData) {

        var localizedErrorDocumentFields = [],
            availableLanguages = opendxp.available_languages,
            websiteLanguages = opendxp.settings.websiteLanguages;

        if (websiteLanguages && websiteLanguages.length > 0) {
            Ext.each(websiteLanguages, function (language) {
                if (empty(language)) {
                    return;
                }

                localizedErrorDocumentFields.push({
                    fieldLabel: t('error_page') + ' - ' + availableLanguages[language],
                    name: 'errorDocument.localized.' + language,
                    fieldCls: 'input_drop_target',
                    value: (localizedErrorDocumentsData && localizedErrorDocumentsData[language]) ? localizedErrorDocumentsData[language] : '',
                    labelWidth: 250,
                    width: 560,
                    xtype: 'textfield',
                    listeners: {
                        render: function (el) {
                            new Ext.dd.DropZone(el.getEl(), {
                                reference: this,
                                ddGroup: 'element',
                                getTargetFromEvent: function (e) {
                                    return this.getEl();
                                }.bind(el),
                                onNodeOver: function (target, dd, e, data) {
                                    if (data.records.length === 1 && data.records[0].data.elementType === 'document') {
                                        return Ext.dd.DropZone.prototype.dropAllowed;
                                    }
                                },
                                onNodeDrop: function (target, dd, e, data) {
                                    if (opendxp.helpers.dragAndDropValidateSingleItem(data)) {
                                        var record = data.records[0];
                                        if (record.data.elementType === 'document') {
                                            this.setValue(record.data.path);
                                            return true;
                                        }
                                    }
                                    return false;
                                }.bind(el)
                            });
                        }
                    }
                });
            });
        }

        return localizedErrorDocumentFields;
    },

    renderSiteCustomSettings: function (container, site) {

        const additionalConfigFactory = {
            input: (node, nodeValue) => ({
                value: nodeValue,
                allowBlank: (node.config.required ?? false) !== true,
            }),
            text: (node, nodeValue) => ({
                xtype: 'textarea',
                grow: true,
                value: nodeValue,
                allowBlank: (node.config.required ?? false) !== true,
            }),
            checkbox: (node, nodeValue) => ({
                checked: nodeValue ?? false,
                inputValue: node.config.checkedValue ?? true,
                uncheckedValue: node.config.uncheckedValue ?? false,
            }),
            combobox: (node, nodeValue) => ({
                store: node.config.store ?? [],
                queryMode: 'local',
                displayField: node.config.displayField ?? 'label',
                valueField: node.config.valueField ?? 'value',
                allowBlank: (node.config.required ?? false) !== true,
                editable: false,
                forceSelection: true,
                value: nodeValue,
            })
        };

        Ext.Ajax.request({
            url: Routing.generate('opendxp_admin_document_document_get_site_custom_settings'),
            method: 'POST',
            params: {
                id: site.id,
            },
            success: function (response) {

                const r = Ext.decode(response.responseText);

                if (!Ext.isObject(r.data)) {
                    return;
                }

                Ext.Object.each(r.data, function (scope, settingsBlock) {

                    const fieldset = {
                        xtype: 'fieldset',
                        title: scope,
                        items: []
                    };

                    Ext.Array.each(settingsBlock, function (configNode) {

                        const nodeValue = site.customSettings?.[scope]?.[configNode.name] ?? null;;

                        const baseConfig = {
                            xtype: configNode.type,
                            fieldLabel: configNode.label,
                            name: 'customSettings.' + scope + '.' + configNode.name,
                            labelWidth: 100,
                            anchor: '100%',
                        };

                        const additionalConfig =
                            additionalConfigFactory[configNode.type]
                                ? additionalConfigFactory[configNode.type](configNode, nodeValue)
                                : {};

                        const nodeConfig = Ext.apply({}, baseConfig, additionalConfig);

                        fieldset.items.push(nodeConfig);
                    });

                    container.add(fieldset)
                });

                container.setHidden(false);

            }.bind(this)
        });

    }
});

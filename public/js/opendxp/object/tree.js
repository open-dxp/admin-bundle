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

opendxp.registerNS("opendxp.object.tree");
/**
 * @private
 */
 opendxp.object.tree = Class.create({

     treeDataUrl: null,
     treeNodeMoveParameter: {
         nodes: [],
         oldParent: {},
         newParent: {},
         indices: []
     },

     initialize: function (config, perspectiveCfg) {
         this.treeDataUrl = Routing.generate('opendxp_admin_dataobject_dataobject_treegetchildrenbyid');
         this.perspectiveCfg = perspectiveCfg;
         if (!perspectiveCfg) {
             this.perspectiveCfg = {
                 position: "left"
             };
         }

         this.perspectiveCfg = new opendxp.perspective(this.perspectiveCfg);
         this.position = this.perspectiveCfg.position ? this.perspectiveCfg.position : "left";

         var parentPanel = Ext.getCmp("opendxp_panel_tree_" + this.position);

         if (!config) {
             this.config = {
                 rootVisible: true,
                 allowedClasses: null,
                 loaderBaseParams: {},
                 treeId: "opendxp_panel_tree_objects",
                 treeIconCls: "opendxp_icon_main_tree_object opendxp_icon_material",
                 treeTitle: t('data_objects'),
                 parentPanel: parentPanel
             };
         }
         else {
             this.config = config;
         }

         opendxp.layout.treepanelmanager.register(this.config.treeId);

         // get root node config
         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_dataobject_dataobject_treegetroot'),
             params: {
                 id: this.config.rootId,
                 view: this.config.customViewId,
                 elementType: "object"
             },
             success: function (response) {
                 var res = Ext.decode(response.responseText);
                 var callback = function () {
                 };
                 if (res["id"]) {
                     callback = this.init.bind(this, res);
                 }
                 opendxp.layout.treepanelmanager.initPanel(this.config.treeId, callback);
             }.bind(this)
         });
     },

     init: function (rootNodeConfig) {

         var itemsPerPage = opendxp.settings['object_tree_paging_limit'];

         let rootNodeConfigText = t('home');
         let rootNodeConfigIconCls = "opendxp_icon_home";
         if(this.config.customViewId !== undefined && rootNodeConfig.id !== 1) {
             rootNodeConfigText = rootNodeConfig.key;
             rootNodeConfigIconCls = rootNodeConfig.iconCls;
         }

         rootNodeConfig.text = rootNodeConfigText;
         rootNodeConfig.allowDrag = true;
         rootNodeConfig.iconCls = rootNodeConfigIconCls;
         rootNodeConfig.cls = "opendxp_tree_node_root";
         rootNodeConfig.expanded = true;

         var store = Ext.create('opendxp.data.PagingTreeStore', {
             autoLoad: true,
             autoSync: false,
             proxy: {
                 type: 'ajax',
                 url: this.treeDataUrl,
                 reader: {
                     type: 'json',
                     totalProperty : 'total',
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


         // objects
         this.tree = Ext.create('opendxp.tree.Panel', {
             selModel : {
                 mode : 'MULTI'
             },
             store: store,
             region: "center",
             autoLoad: false,
             iconCls: this.config.treeIconCls,
             cls: this.config['rootVisible'] ? '' : 'opendxp_tree_no_root_node',
             id: this.config.treeId,
             title: this.config.treeTitle,
             autoScroll: true,
             animate: false,
             rootVisible: this.config.rootVisible,
             bufferedRenderer: false,
             border: false,
             listeners: this.getTreeNodeListeners(),
             scrollable: true,
             viewConfig: {
                 plugins: {
                     ptype: 'treeviewdragdrop',
                     appendOnly: false,
                     ddGroup: "element",
                     scrollable: true
                 },
                 listeners: {
                     nodedragover: this.onTreeNodeOver.bind(this)
                 },
                 xtype: 'opendxptreeview'
             },
             tools: [{
                 type: "right",
                 handler: opendxp.layout.treepanelmanager.toRight.bind(this),
                 hidden: this.position == "right"
             },{
                 type: "left",
                 handler: opendxp.layout.treepanelmanager.toLeft.bind(this),
                 hidden: this.position == "left"
             }]
             // ,
             // root: rootNodeConfig
         });

         store.on("nodebeforeexpand", function (node) {
             opendxp.helpers.addTreeNodeLoadingIndicator("object", node.data.id, false);
         });

         store.on("nodeexpand", function (node, index, item, eOpts) {
             opendxp.helpers.removeTreeNodeLoadingIndicator("object", node.data.id);
         });


         this.tree.on("afterrender", function () {
             this.tree.loadMask = new Ext.LoadMask(
                 {
                     target: Ext.getCmp(this.config.treeId),
                     msg:t("please_wait")
                 });
         }.bind(this));

         this.config.parentPanel.insert(this.config.index, this.tree);
         this.config.parentPanel.updateLayout();


         if (!this.config.parentPanel.alreadyExpanded && this.perspectiveCfg.expanded) {
             this.config.parentPanel.alreadyExpanded = true;
             this.tree.expand();
         }

     },

     getTreeNodeListeners: function () {
         var treeNodeListeners = {
             'itemclick': this.onTreeNodeClick,
             "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
             "itemmove": this.onTreeNodeMove.bind(this),
             "beforeitemmove": this.onTreeNodeBeforeMove.bind(this),
             "itemmouseenter": function (el, record, item, index, e, eOpts) {
                 opendxp.helpers.treeToolTipShow(el, record, item);
             },
             "itemmouseleave": function () {
                 opendxp.helpers.treeToolTipHide();
             },
             "beforeload": function (store, operation, options) {
                 // add the parent path as an additional diagnostic parameter
                 // can be used by bundles that work with dynamic children nodes
                 store.proxy.setExtraParam('parentPath', operation.node.data.path)
             },
             "drop": this.onTreeNodesDrop.bind(this)
         };

         return treeNodeListeners;
     },

     onTreeNodeClick: function (tree, record, item, index, event, eOpts ) {
         if (event.ctrlKey === false && event.shiftKey === false && event.altKey === false) {
             try {

                 var eventData =  {record: record, preventDefault: false};

                 const prepareOnObjectTreeNodeClick = new CustomEvent(opendxp.events.prepareOnObjectTreeNodeClick, {
                     detail: {
                         eventData: eventData
                     }
                 });

                 document.dispatchEvent(prepareOnObjectTreeNodeClick);

                 if (eventData.preventDefault) {
                     return;
                 }

                 if (record.data.permissions && record.data.permissions.view) {
                     opendxp.helpers.openObject(record.data.id, record.data.type);
                 }
             } catch (e) {
                 console.log(e);
             }
         }
     },

     onTreeNodeOver: function (targetNode, position, dragData, e, eOpts ) {
         var node = dragData.records[0];

         //dropping variants not allowed on folder
         if(node.data.type == 'variant' && targetNode.data.type == 'folder'){
             return false;
         }

         // check for permission
         try {
             if (node.data.permissions.settings) {
                 return true;
             }
         }
         catch (e) {
             console.log(e);
         }

         return false;
     },

     onTreeNodeMove: function (node, oldParent, newParent, index, eOpts ) {
         this.treeNodeMoveParameter.nodes.push(node);
         this.treeNodeMoveParameter.oldParent = oldParent;
         this.treeNodeMoveParameter.newParent = newParent;
         this.treeNodeMoveParameter.indices.push(index);
     },

     onTreeNodesDrop: function (node, data, overModel, dropPosition, eOpts) {
         if (typeof this.treeNodeMoveParameter.oldParent.getOwnerTree !== "function") {
             if (dropPosition == "before" || dropPosition == "after") {
                 return;
             }

             Ext.Array.each(data.records, function (record) {
                 if (this.onTreeNodeBeforeMove(record, record.parentNode, overModel)) {
                     this.onTreeNodeMove(record, record.parentNode, overModel, 0);
                 }
             }.bind(this));
         }

         // recheck wheter the getOwnerTree is a function after reassignment onTreeNodeMove()
         if (typeof this.treeNodeMoveParameter.oldParent.getOwnerTree !== "function") {
             return;
         }

         let tree = this.treeNodeMoveParameter.oldParent.getOwnerTree();

         let pageOffset = 0;
         let ids = [];
         let indices = {};

         for (let i = 0; i < this.treeNodeMoveParameter.nodes.length; i++) {
             pageOffset = 0

             if (this.treeNodeMoveParameter.nodes[i].parentNode.pagingData) {
                 pageOffset = this.treeNodeMoveParameter.nodes[i].parentNode.pagingData.offset;
             }

             ids.push(this.treeNodeMoveParameter.nodes[i].data.id);

             indices[ids[i]] = this.treeNodeMoveParameter.indices[i] + pageOffset;
         }

         if(ids.length === 1) {
             ids = ids[0];
             indices = indices[ids];
         }

         opendxp.elementservice.updateObject(ids, {
             parentId: this.treeNodeMoveParameter.newParent.data.id,
             indices: indices,
         }, function (nodes, newParent, oldParent, tree, response) {
             try {
                 const rdata = Ext.decode(response.responseText);
                 if (rdata && rdata.success) {
                     // set new pathes
                     let newBasePath = newParent.data.path;
                     if (newBasePath == "/") {
                         newBasePath = "";
                     }
                     nodes.map(node => {
                         node.data.basePath = newBasePath;
                         node.data.path = node.data.basePath + "/" + node.data.text;
                     });
                 } else {
                     tree.loadMask.hide();
                     opendxp.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                         "error", t(rdata.message));
                     // we have to delay refresh between two nodes,
                     // as there could be parent child relationship leading to race condition
                     window.setTimeout(function () {
                         opendxp.elementservice.refreshNode(oldParent);
                     }, 500);
                     opendxp.elementservice.refreshNode(newParent);
                 }
             } catch (e) {
                 tree.loadMask.hide();
                 opendxp.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                 // we have to delay refresh between two nodes,
                 // as there could be parent child relationship leading to race condition
                 window.setTimeout(function () {
                     opendxp.elementservice.refreshNode(oldParent);
                 }, 500);
                 opendxp.elementservice.refreshNode(newParent);
             }
             tree.loadMask.hide();

             this.treeNodeMoveParameter =  {
                 nodes: [],
                 oldParent: {},
                 newParent: {},
                 indices: []
             };

         }.bind(this, this.treeNodeMoveParameter.nodes, this.treeNodeMoveParameter.newParent, this.treeNodeMoveParameter.oldParent, tree));
     },

     onTreeNodeBeforeMove: function (node, oldParent, newParent, index, eOpts ) {
         var tree = node.getOwnerTree();

         //dropping variants only allowed in the same parent
         if(node.data.type == 'variant' && oldParent.data.id != newParent.data.id){
             opendxp.helpers.showNotification(t("error"), t("element_cannot_be_moved"), "error");
             return false;
         }

         // dropping objects not allowed if the tree/folder is paginated and sort by index (manual indexes) is enabled
         if(((!newParent.pagingData?.canSortManually) || (newParent.childNodes.length > opendxp.settings['object_tree_paging_limit'])) && (newParent.data.sortBy == "index")){
             opendxp.helpers.showNotification(t("error"), t("element_cannot_be_moved_because_target_is_paginated"), "error");
             return false;
         }

         if(newParent.data.id == oldParent.data.id && oldParent.data.sortBy != 'index') {
             opendxp.helpers.showNotification(t("error"), t("element_cannot_be_moved"), "error");
             return false;
         }

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
         if(!newParent.data.permissions.create){
             Ext.MessageBox.alert(' ', t('element_cannot_be_moved'));
             return false;
         }

         // check permissions
         if (node.data.permissions.settings) {
             tree.loadMask.show();
             return true;
         }
         return false;
     },

     onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
         e.stopEvent();

         if(opendxp.helpers.hasTreeNodeLoadingIndicator("object", record.data.id)) {
             return;
         }

         tree.select();

         var menu = new Ext.menu.Menu();
         var perspectiveCfg = this.perspectiveCfg;

         if(tree.getSelectionModel().getSelected().length > 1) {
             var selectedIds = [];
             tree.getSelectionModel().getSelected().each(function (item) {
                 selectedIds.push(item.id);
             });

             if (record.data.permissions && record.data.permissions.delete && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("object.delete")) {
                 menu.add(new Ext.menu.Item({
                     text: t('delete'),
                     iconCls: "opendxp_icon_delete",
                     handler: this.remove.bind(this, selectedIds.join(','))
                 }));
             }
         } else {
             var object_types = opendxp.globalmanager.get("object_types_store_create");

             var objectMenu = {
                 objects: [],
                 importer: [],
                 ref: this
             };

             var groups = {
                 importer: {},
                 objects: {}
             };

             var tmpMenuEntry;
             var tmpMenuEntryImport;
             var $this = this;

             object_types.sort([
                 {property: 'translatedGroup', direction: 'ASC'},
                 {property: 'translatedText', direction: 'ASC'}
             ]);

             object_types.each(function (classRecord) {
                 if($this.config.allowedClasses && Object.keys($this.config.allowedClasses).length > 0) {
                     if (!in_array(classRecord.get("id"), Object.keys($this.config.allowedClasses))) {
                         return;
                     }

                     if ($this.config.allowedClasses[classRecord.get("id")] !== null) {
                         if (record.data.depth >= $this.config.allowedClasses[classRecord.get("id")]) {
                             return;
                         }
                     }
                 }

                 tmpMenuEntry = {
                     text: classRecord.get("translatedText"),
                     iconCls: "opendxp_icon_object opendxp_icon_overlay_add",
                     handler: $this.addObject.bind($this, classRecord.get("id"), classRecord.get("text"), tree, record)
                 };

                 // add special icon
                 if (classRecord.get("icon") != "/bundles/opendxpadmin/img/flat-color-icons/class.svg") {
                     tmpMenuEntry.icon = classRecord.get("icon");
                     tmpMenuEntry.iconCls = "opendxp_class_icon";
                 }

                 tmpMenuEntryImport = {
                     text: classRecord.get("translatedText"),
                     iconCls: "opendxp_icon_object opendxp_icon_overlay_add",
                     handler: $this.importObjects.bind($this, classRecord.get("id"), classRecord.get("text"), tree, record)
                 };

                 // add special icon
                 if (classRecord.get("icon") != "/bundles/opendxpadmin/img/flat-color-icons/class.svg") {
                     tmpMenuEntryImport.icon = classRecord.get("icon");
                     tmpMenuEntryImport.iconCls = "opendxp_class_icon";
                 }

                 // check if the class is within a group
                 if (classRecord.get("group")) {
                     if (!groups["objects"][classRecord.get("group")]) {
                         groups["objects"][classRecord.get("group")] = {
                             text: classRecord.get("translatedGroup"),
                             iconCls: "opendxp_icon_folder",
                             hideOnClick: false,
                             menu: {
                                 items: []
                             }
                         };
                         groups["importer"][classRecord.get("group")] = {
                             text: classRecord.get("translatedGroup"),
                             iconCls: "opendxp_icon_folder",
                             hideOnClick: false,
                             menu: {
                                 items: []
                             }
                         };
                         objectMenu["objects"].push(groups["objects"][classRecord.get("group")]);
                         objectMenu["importer"].push(groups["importer"][classRecord.get("group")]);
                     }

                     groups["objects"][classRecord.get("group")]["menu"]["items"].push(tmpMenuEntry);
                     groups["importer"][classRecord.get("group")]["menu"]["items"].push(tmpMenuEntryImport);
                 } else {
                     objectMenu["objects"].push(tmpMenuEntry);
                     objectMenu["importer"].push(tmpMenuEntryImport);
                 }
             });


             var isVariant = record.data.type == "variant";

             if (record.data.permissions && record.data.permissions.create) {
                 if (!isVariant) {
                     if (perspectiveCfg.inTreeContextMenu("object.add")) {
                         menu.add(new Ext.menu.Item({
                             text: t('add_object'),
                             iconCls: "opendxp_icon_object opendxp_icon_overlay_add",
                             hideOnClick: false,
                             menu: objectMenu.objects
                         }));
                     }
                 }

                 if (record.data.allowVariants && perspectiveCfg.inTreeContextMenu("object.add")) {
                     menu.add(new Ext.menu.Item({
                         text: t("add_variant"),
                         iconCls: "opendxp_icon_variant",
                         handler: this.createVariant.bind(this, tree, record)
                     }));
                 }

                 if (!isVariant) {

                     if (perspectiveCfg.inTreeContextMenu("object.addFolder")) {
                         menu.add(new Ext.menu.Item({
                             text: t('create_folder'),
                             iconCls: "opendxp_icon_folder opendxp_icon_overlay_add",
                             handler: this.addFolder.bind(this, tree, record)
                         }));
                     }

                     menu.add("-");

                     //paste
                     var pasteMenu = [];

                     if (perspectiveCfg.inTreeContextMenu("object.paste")) {
                         let classId = null;

                         // Determine ClassId if copiedObject exists
                         if (typeof opendxp.copiedObject !== 'undefined' && opendxp.copiedObject !== null) {
                             if (typeof opendxp.copiedObject.get('className') !== "undefined") {
                                 let className = opendxp.copiedObject.get('className');
                                 if (className) {
                                     let objectTypesStore = opendxp.globalmanager.get("object_types_store");
                                     let classRecord = objectTypesStore.findRecord('text', className);
                                     if (classRecord) {
                                         classId = classRecord.get('id');
                                     }
                                 }
                             }
                         }

                         if (opendxp.cachedObjectId && (typeof perspectiveCfg.classes === "undefined" || classId === null || classId in perspectiveCfg.classes)) {
                             pasteMenu.push({
                                 text: t("paste_recursive_as_child"),
                                 iconCls: "opendxp_icon_paste",
                                 handler: this.pasteInfo.bind(this, tree, record, "recursive")
                             });
                             pasteMenu.push({
                                 text: t("paste_recursive_updating_references"),
                                 iconCls: "opendxp_icon_paste",
                                 handler: this.pasteInfo.bind(this, tree, record, "recursive-update-references")
                             });
                             pasteMenu.push({
                                 text: t("paste_as_child"),
                                 iconCls: "opendxp_icon_paste",
                                 handler: this.pasteInfo.bind(this, tree, record, "child")
                             });


                             if (record.data.type != "folder") {
                                 pasteMenu.push({
                                     text: t("paste_contents"),
                                     iconCls: "opendxp_icon_paste",
                                     handler: this.pasteInfo.bind(this, tree, record, "replace")
                                 });
                             }
                         }
                     }

                     if (opendxp.cutObject && (typeof perspectiveCfg.classes === "undefined" || typeof opendxp.cutObject.get('className') === "undefined" || opendxp.cutObject.get('className') in perspectiveCfg.classes)) {
                         pasteMenu.push({
                             text: t("paste_cut_element"),
                             iconCls: "opendxp_icon_paste",
                             handler: function () {
                                 this.pasteCutObject(opendxp.cutObject,
                                     opendxp.cutObjectParentNode, record, this.tree);
                                 opendxp.cutObjectParentNode = null;
                                 opendxp.cutObject = null;
                             }.bind(this)
                         });
                     }

                     if (pasteMenu.length > 0) {
                         menu.add(new Ext.menu.Item({
                             text: t('paste'),
                             iconCls: "opendxp_icon_paste",
                             hideOnClick: false,
                             menu: pasteMenu
                         }));
                     }
                 }
             }

             if (!isVariant) {
                 if (record.data.id != 1 && record.data.permissions && record.data.permissions.view && perspectiveCfg.inTreeContextMenu("object.copy")) {
                     menu.add(new Ext.menu.Item({
                         text: t('copy'),
                         iconCls: "opendxp_icon_copy",
                         handler: this.copy.bind(this, tree, record)
                     }));
                 }

                 //cut
                 if (record.data.id != 1 && !record.data.locked && record.data.permissions && record.data.permissions.rename && perspectiveCfg.inTreeContextMenu("object.cut")) {
                     menu.add(new Ext.menu.Item({
                         text: t('cut'),
                         iconCls: "opendxp_icon_cut",
                         handler: this.cut.bind(this, tree, record)
                     }));
                 }
             }

             //publish
             if (record.data.type != "folder" && !record.data.locked && record.data.permissions) {
                 if (record.data.published && record.data.permissions.unpublish && perspectiveCfg.inTreeContextMenu("object.unpublish")) {
                     menu.add(new Ext.menu.Item({
                         text: t('unpublish'),
                         iconCls: "opendxp_icon_unpublish",
                         handler: this.publishObject.bind(this, tree, record, 'unpublish')
                     }));
                 } else if (!record.data.published && record.data.permissions.publish && perspectiveCfg.inTreeContextMenu("object.publish")) {
                     menu.add(new Ext.menu.Item({
                         text: t('publish'),
                         iconCls: "opendxp_icon_publish",
                         handler: this.publishObject.bind(this, tree, record, 'publish')
                     }));
                 }
             }


             if (record.data.permissions && record.data.permissions["delete"] && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("object.delete")) {
                 menu.add(new Ext.menu.Item({
                     text: t('delete'),
                     iconCls: "opendxp_icon_delete",
                     handler: this.remove.bind(this, record.data.id)
                 }));
             }

             if (record.data.permissions && record.data.permissions.rename && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("object.rename")) {
                 menu.add(new Ext.menu.Item({
                     text: t('rename'),
                     iconCls: "opendxp_icon_key opendxp_icon_overlay_go",
                     handler: this.editObjectKey.bind(this, tree, record)
                 }));
             }


             // advanced menu
             var advancedMenuItems = [];
             var user = opendxp.globalmanager.get("user");

             if (record.data.permissions && record.data.permissions.create &&
                 perspectiveCfg.inTreeContextMenu("object.searchAndMove") &&
                 opendxp.helpers.hasSearchImplementation()) {
                 advancedMenuItems.push({
                     text: t('search_and_move'),
                     iconCls: "opendxp_icon_search opendxp_icon_overlay_go",
                     handler: this.searchAndMove.bind(this, tree, record)
                 });
             }

             if (record.data.id != 1 && user.admin) {
                 var lockMenu = [];
                 if (record.data.lockOwner && perspectiveCfg.inTreeContextMenu("object.unlock")) { // add unlock
                     lockMenu.push({
                         text: t('unlock'),
                         iconCls: "opendxp_icon_lock opendxp_icon_overlay_delete",
                         handler: function () {
                             opendxp.elementservice.lockElement({
                                 elementType: "object",
                                 id: record.data.id,
                                 mode: "null"
                             });
                         }.bind(this)
                     });
                 } else {
                     if (perspectiveCfg.inTreeContextMenu("object.lock")) {
                         lockMenu.push({
                             text: t('lock'),
                             iconCls: "opendxp_icon_lock opendxp_icon_overlay_add",
                             handler: function () {
                                 opendxp.elementservice.lockElement({
                                     elementType: "object",
                                     id: record.data.id,
                                     mode: "self"
                                 });
                             }.bind(this)
                         });
                     }

                     if (perspectiveCfg.inTreeContextMenu("object.lockAndPropagate")) {
                         lockMenu.push({
                             text: t('lock_and_propagate_to_children'),
                             iconCls: "opendxp_icon_lock opendxp_icon_overlay_go",
                             handler: function () {
                                 opendxp.elementservice.lockElement({
                                     elementType: "object",
                                     id: record.data.id,
                                     mode: "propagate"
                                 });
                             }.bind(this)
                         });
                     }
                 }

                 if (record.data.locked) {
                     // add unlock and propagate to children functionality
                     if (perspectiveCfg.inTreeContextMenu("object.unlockAndPropagate")) {
                         lockMenu.push({
                             text: t('unlock_and_propagate_to_children'),
                             iconCls: "opendxp_icon_lock opendxp_icon_overlay_delete",
                             handler: function () {
                                 opendxp.elementservice.unlockElement({
                                     elementType: "object",
                                     id: record.data.id
                                 });
                             }.bind(this)
                         });
                     }
                 }

                 if (lockMenu.length > 0 && perspectiveCfg.inTreeContextMenu("object.unlock")) {
                     advancedMenuItems.push({
                         text: t('lock'),
                         iconCls: "opendxp_icon_lock",
                         hideOnClick: false,
                         menu: lockMenu
                     });
                 }
             }

             // expand and collapse complete tree
             if (record.data.expandable) {
                 if (record.data.expanded) {
                     advancedMenuItems.push({
                         text: t('collapse_children'),
                         iconCls: "opendxp_icon_collapse_children",
                         handler: function () {
                             record.collapse(true);
                         }.bind(this, record)
                     });
                 } else {
                     advancedMenuItems.push({
                         text: t('expand_children'),
                         iconCls: "opendxp_icon_expand_children",
                         handler: function () {
                             record.expand(true);
                         }.bind(this, record)
                     });
                 }
             }

             menu.add("-");

             if (advancedMenuItems.length) {
                 menu.add({
                     text: t('advanced'),
                     iconCls: "opendxp_icon_more",
                     hideOnClick: false,
                     menu: advancedMenuItems
                 });
             }

             // Sort Children By
             var sortByItems = [];

             if (user.admin || !record.data.locked) {

                 if (record.data.permissions && record.data.permissions.settings && perspectiveCfg.inTreeContextMenu("object.changeChildrenSortBy")) {
                     // only the admin is allowed to change the sort method.

                     let currentSortMethod = record.data.sortBy;

                     if (currentSortMethod === "key" || user.admin || user.isAllowed("objects_sort_method")) {
                         sortByItems.push({
                             text: t('by_key'),
                             iconCls: "opendxp_icon_alphabetical_sorting_az",
                             handler: this.changeObjectChildrenSortBy.bind(this, tree, record, 'key', 'ASC')
                         });
                         sortByItems.push({
                             text: t('by_key_reverse'),
                             iconCls: "opendxp_icon_alphabetical_sorting_za",
                             handler: this.changeObjectChildrenSortBy.bind(this, tree, record, 'key', 'DESC')
                         });
                     }

                     if (currentSortMethod === "index" || user.admin || user.isAllowed("objects_sort_method")) {
                         sortByItems.push({
                             text: t('by_index'),
                             iconCls: "opendxp_icon_index_sorting",
                             handler: this.changeObjectChildrenSortBy.bind(this, tree, record, 'index', record.data.sortOrder)
                         });
                     }
                 }
             }

             if (sortByItems.length) {
                 menu.add("-");
                 menu.add({
                     text: t('sort_children_by'),
                     iconCls: "opendxp_icon_folder",
                     hideOnClick: false,
                     menu: sortByItems
                 });
             }

             if (perspectiveCfg.inTreeContextMenu("object.reload")) {
                 menu.add({
                     text: t('refresh'),
                     iconCls: "opendxp_icon_reload",
                     handler: this.reloadNode.bind(this, tree, record)
                 });
             }
         }

         opendxp.helpers.hideRedundantSeparators(menu);

         const prepareObjectTreeContextMenu = new CustomEvent(opendxp.events.prepareObjectTreeContextMenu, {
             detail: {
                 menu: menu,
                 tree: this,
                 object: record
             }
         });

         document.dispatchEvent(prepareObjectTreeContextMenu);


         menu.showAt(e.pageX+1, e.pageY+1);
     },

     reloadNode: function(tree, record) {
         opendxp.elementservice.refreshNode(record);
     },

     copy: function (tree, record) {
         opendxp.cachedObjectId = record.data.id;
         opendxp.copiedObject = record;
     },

     cut: function (tree, record) {
         opendxp.cutObject = record;
         opendxp.cutObjectParentNode = record.parentNode;
     },

     createVariant: function (tree, record) {
         Ext.MessageBox.prompt(t('add_variant'), t('enter_the_name_of_the_new_item'),
             this.addVariantCreate.bind(this, tree, record));
     },

     addFolderCreate: function (tree, record, button, value, object) {

         // check for ident filename in current level
         if (opendxp.elementservice.isKeyExistingInLevel(record, value)) {
             return;
         }

         if (button == "ok") {
             var options =  {
                 url: Routing.generate('opendxp_admin_dataobject_dataobject_addfolder'),
                 elementType : "object",
                 sourceTree: tree,
                 parentId: record.data.id,
                 key: opendxp.helpers.getValidFilename(value, "object")
             };
             opendxp.elementservice.addObject(options);
         }
     },

     addObjectCreate: function (classId, className, tree, record, button, value, object) {

         if (button == "ok") {
             // check for identical filename in current level
             if (opendxp.elementservice.isKeyExistingInLevel(record, value)) {
                 return;
             }

             var options = {
                 url: Routing.generate('opendxp_admin_dataobject_dataobject_add'),
                 elementType: "object",
                 sourceTree: tree,
                 parentId: record.data.id,
                 className: className,
                 classId: classId,
                 key: opendxp.helpers.getValidFilename(value, "object")
             };
             opendxp.elementservice.addObject(options);
         }

     },

     addVariantCreate: function (tree, record, button, value, object) {

         if (button == "ok") {
             // check for identical filename in current level

             if (opendxp.elementservice.isKeyExistingInLevel(record, value)) {
                 return;
             }

             var options = {
                 url: Routing.generate('opendxp_admin_dataobject_dataobject_add'),
                 elementType: "object",
                 sourceTree: tree,
                 className: record.data.className,
                 parentId: record.data.id,
                 variantViaTree: true,
                 objecttype: "variant",
                 key: opendxp.helpers.getValidFilename(value, "object")
             };
             opendxp.elementservice.addObject(options);
         }
     },

     addVariantComplete: function (tree, record, response) {
         try {
             var rdata = Ext.decode(response.responseText);
             if (rdata && rdata.success) {
                 record.data.leaf = false;
                 record.expand();

                 if (rdata.id && rdata.type) {
                     if (rdata.type == "variant") {
                         opendxp.helpers.openObject(rdata.id, rdata.type);
                     }
                 }
             }
             else {
                 opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error", t(rdata.message));
             }
         } catch (e) {
             opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error");
         }
         opendxp.elementservice.refreshNode(record);
     },


     pasteCutObject: function (record, oldParent, newParent, tree) {
         opendxp.elementservice.updateObject(record.data.id, {
             parentId: newParent.id
         }, function (record, newParent, oldParent, tree, response) {
             try {
                 var rdata = Ext.decode(response.responseText);
                 if (rdata && rdata.success) {
                     // set new pathes
                     var newBasePath = newParent.data.path;
                     if (newBasePath == "/") {
                         newBasePath = "";
                     }
                     record.basePath = newBasePath;
                     record.path = record.data.basePath + "/" + record.data.text;
                 }
                 else {
                     tree.loadMask.hide();
                     opendxp.helpers.showNotification(t("error"), t("error_moving_object"), "error", t(rdata.message));
                 }
             } catch (e) {
                 tree.loadMask.hide();
                 opendxp.helpers.showNotification(t("error"), t("error_moving_object"), "error");
             }
             opendxp.elementservice.refreshNodeAllTrees("object", oldParent.id);
             opendxp.elementservice.refreshNodeAllTrees("object", newParent.id);
             newParent.expand();
             tree.loadMask.hide();
         }.bind(this, record, newParent, oldParent, tree));
     },

     pasteInfo: function (tree, record, type) {
         //this.attributes.reference.tree.loadMask.show();

         opendxp.helpers.addTreeNodeLoadingIndicator("object", record.data.id);

         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_dataobject_dataobject_copyinfo'),
             params: {
                 targetId: record.data.id,
                 sourceId: opendxp.cachedObjectId,
                 type: type
             },
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
                     title: t("paste"),
                     layout: 'fit',
                     width: 200,
                     bodyStyle: "padding: 10px;",
                     closable: false,
                     plain: true,
                     items: [record.pasteProgressBar],
                     listeners: opendxp.helpers.getProgressWindowListeners()
                 });

                 record.pasteWindow.show();


                 var pj = new opendxp.tool.paralleljobs({
                     success: function () {

                         try {
                             this.pasteComplete(tree, record);
                         } catch (e) {
                             console.log(e);
                             opendxp.helpers.showNotification(t("error"), t("error_pasting_item"), "error");
                             opendxp.elementservice.refreshNodeAllTrees("object", record.id);
                         }
                     }.bind(this),
                     update: function (currentStep, steps, percent) {
                         if (record.pasteProgressBar) {
                             var status = currentStep / steps;
                             record.pasteProgressBar.updateProgress(status, percent + "%");
                         }
                     }.bind(this),
                     failure: function (message, rdata) {
                         record.pasteWindow.close();
                         record.pasteProgressBar = null;

                         opendxp.helpers.showPrettyError(rdata.type, t("error"), t("error_pasting_item"),
                             rdata.message, rdata.stack, rdata.code);

                         opendxp.elementservice.refreshNodeAllTrees("object", record.parentNode.id);
                     }.bind(this),
                     jobs: res.pastejobs
                 });
             } else {
                 throw "There are no pasting jobs";
             }
         } catch (e) {
             console.log(e);
             Ext.MessageBox.alert(t('error'), e);
             this.pasteComplete(tree, record);
         }
     },

     pasteComplete: function (tree, record) {
         if (record.pasteWindow) {
             record.pasteWindow.close();
         }

         record.pasteProgressBar = null;
         record.pasteWindow = null;

         //this.tree.loadMask.hide();
         opendxp.helpers.removeTreeNodeLoadingIndicator("object", record.id);
         opendxp.elementservice.refreshNodeAllTrees("object", record.id);
     },

     importObjects: function (classId, className, tree, record) {
         var importer = new opendxp.object.helpers.import.configDialog(
             {
                 tree: tree,
                 classId: classId,
                 className: className,
                 parentNode: record
             });

     },

     addObject: function (classId, className, tree, record) {
         var dialogText = t("object_add_dialog_custom_text" + "." + className);

         if (dialogText == "object_add_dialog_custom_text" + "." + className) {
             dialogText =  t('enter_the_name_of_the_new_item');
         }

         var dialogTitle = t("object_add_dialog_custom_title" + "." + className);

         if (dialogTitle == "object_add_dialog_custom_title" + "." + className) {
             dialogTitle =  sprintf(t('add_object_mbx_title'), t(className));
         }

         Ext.MessageBox.prompt(dialogTitle, dialogText,
             this.addObjectCreate.bind(this, classId, className, tree, record));
     },


     addFolder: function (tree, record) {
         Ext.MessageBox.prompt(t('create_folder'), t('enter_the_name_of_the_new_item'),
             this.addFolderCreate.bind(this, tree, record));
     },

     remove: function (ids) {
         var options = {
             "elementType" : "object",
             "id": ids
         };
         opendxp.elementservice.deleteElement(options);
     },

     editObjectKey: function (tree, record) {
         var options = {
             sourceTree: tree,
             elementType: "object",
             elementSubType: record.data.type,
             id: record.data.id,
             default: Ext.util.Format.htmlDecode(record.data.key)
         };
         opendxp.elementservice.editElementKey(options);
     },

     publishObject: function (tree, record, task) {

         var parameters = {};
         parameters.id = record.data.id;

         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_dataobject_dataobject_save', {task: task}),
             method: "PUT",
             params: parameters,
             success: function (tree, record, task, response) {
                 try {
                     var rdata = Ext.decode(response.responseText);

                     if (rdata && rdata.success) {
                         var options = {
                             elementType: "object",
                             id: record.data.id,
                             published: task != "unpublish"
                         };

                         opendxp.elementservice.setElementPublishedState(options);
                         opendxp.elementservice.setElementToolbarButtons(options);
                         opendxp.elementservice.reloadVersions(options);

                         opendxp.helpers.showNotification(t("success"), t("successful_" + task + "_object"), "success");
                     }  else {
                         opendxp.helpers.showNotification(t("error"), t("error_" + task + "_object"), "error",
                             t(rdata.message));
                     }
                 } catch (e) {
                     console.log(e);
                     opendxp.helpers.showNotification(t("error"), t("error_" + task + "_object"), "error");
                 }

                 //todo if open reload

             }.bind(this, tree, record, task)
         });

     },


     doChangeObjectChildrenSortBy: function (tree, record, sortBy, childrenSortOrder = 'ASC') {
         var parameters = {
             id: record.data.id,
             sortBy: sortBy,
             childrenSortOrder: childrenSortOrder
         };

         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_dataobject_dataobject_changechildrensortby'),
             method: "PUT",
             params: parameters,
             success: function (tree, record, sortBy, response) {
                 try {
                     var rdata = Ext.decode(response.responseText);

                     if (rdata && rdata.success) {
                         opendxp.helpers.showNotification(
                             t("success"),
                             t("successful_object_change_children_sort_to_" + sortBy),
                             "success"
                         );
                         record.data.sortBy = sortBy;
                         this.reloadNode(tree, record);
                     } else {
                         opendxp.helpers.showNotification(
                             t("error"),
                             t("error_object_change_children_sort_to_" + sortBy),
                             "error",
                             t(rdata.message)
                         );
                     }
                 } catch (e) {
                     opendxp.helpers.showNotification(
                         t("error"),
                         t("error_object_change_children_sort_to_" + sortBy),
                         "error"
                     );
                 }

             }.bind(this, tree, record, sortBy)
         });
     },

     changeObjectChildrenSortBy: function (tree, record, sortBy, childrenSortOrder = 'ASC') {

         let currentSortMethod = record.data.sortBy;

         if (currentSortMethod != sortBy && sortBy == "index") {

             // Do not allow sort by index(Manual Indexes) for a paginated tree/folder
             if(!record.pagingData.canSortManually) {
                 Ext.MessageBox.alert(
                     t("error"),
                     t("error_object_change_children_sort_to_index"));
             }
             else {
                 Ext.MessageBox.confirm(t("warning"), t("reindex_warning"),
                     function (tree, record, sortBy, childrenSortOrder, buttonValue) {
                         if (buttonValue == "yes") {
                             this.doChangeObjectChildrenSortBy(tree, record, sortBy, childrenSortOrder);
                         }
                     }.bind(this, tree, record, sortBy, childrenSortOrder));
             }
         } else {
             this.doChangeObjectChildrenSortBy(tree, record, sortBy, childrenSortOrder);
         }
     },

     searchAndMove: function(tree, record) {
         opendxp.helpers.searchAndMove(record.data.id, function() {
             opendxp.elementservice.refreshNode(record);
         }.bind(this), "object");
     }
 });

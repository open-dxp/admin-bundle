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

 opendxp.registerNS("opendxp.asset.tree");
/**
 * @private
 */
 opendxp.asset.tree = Class.create({
 
     treeDataUrl: null,
 
     initialize: function(config, perspectiveCfg) {
         this.treeDataUrl = Routing.generate('opendxp_admin_asset_treegetchildrenbyid');
         this.perspectiveCfg = perspectiveCfg;
         if (!perspectiveCfg) {
             this.perspectiveCfg = {
                 position: "left"
             };
         }
 
         this.perspectiveCfg = new opendxp.perspective(this.perspectiveCfg);
         this.position = this.perspectiveCfg.position ? this.perspectiveCfg.position : "left";
 
         if (!config) {
             this.config = {
                 rootId: 1,
                 rootVisible: true,
                 loaderBaseParams: {},
                 treeId: "opendxp_panel_tree_assets",
                 treeIconCls: "opendxp_icon_main_tree_asset opendxp_icon_material",
                 treeTitle: t('assets'),
                 parentPanel: Ext.getCmp("opendxp_panel_tree_" + this.position),
             };
         }
         else {
             this.config = config;
         }
 
         opendxp.layout.treepanelmanager.register(this.config.treeId);
 
         // get root node config
         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_asset_treegetroot'),
             params: {
                 id: this.config.rootId,
                 view: this.config.customViewId,
                 elementType: "asset"
             },
             success: function (response) {
                 var res = Ext.decode(response.responseText);
                 var callback = function () {};
                 if(res["id"]) {
                     callback = this.init.bind(this, res);
                 }
                 opendxp.layout.treepanelmanager.initPanel(this.config.treeId, callback);
             }.bind(this)
         });
     },
 
     init: function(rootNodeConfig) {
 
         var itemsPerPage = opendxp.settings['asset_tree_paging_limit'];

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
             autoLoad: false,
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
                 },
                 timeout: 60000
             },
             pageSize: itemsPerPage,
             root: rootNodeConfig
         });
 
         // assets
         this.tree = Ext.create('opendxp.tree.Panel', {
             selModel : {
                 mode : 'MULTI'
             },
             store: store,
             autoLoad: false,
             id: this.config.treeId,
             title: this.config.treeTitle,
             iconCls: this.config.treeIconCls,
             cls: this.config['rootVisible'] ? '' : 'opendxp_tree_no_root_node',
             autoScroll:true,
             animate:false,
             containerScroll: true,
             ddAppendOnly: true,
             rootVisible: this.config.rootVisible,
             forceLayout: true,
             bufferedRenderer: false,
             border: false,
             viewConfig: {
                 plugins: {
                     ptype: 'treeviewdragdrop',
                     appendOnly: true,
                     ddGroup: "element"
                 },
                 listeners: {
                     beforedrop: function (node, data) {
                     },
                     nodedragover: this.onTreeNodeOver.bind(this),
                     startdrag: function() {
                     }
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
             }],
             // root: rootNodeConfig,
             listeners: this.getTreeNodeListeners()
         });
 
         //TODO
         this.tree.getView().on("itemafterrender",this.enableHtml5Upload.bind(this));
         this.tree.on("render", function () {
             this.getRootNode().expand();
         });
         this.tree.on("afterrender", function () {
             try {
                 this.tree.loadMask = new Ext.LoadMask({
                     target: this.tree,
                     msg: t("please_wait"),
                     hidden: true
                 });
 
                 // add listener to root node -> other nodes are added om the "append" event -> see this.enableHtml5Upload()
                 this.addHtml5DragListener(this.tree.getRootNode());
 
                 // html5 upload
                 if (window["FileList"]) {
                     this.tree.getEl().dom.addEventListener("drop", function (e) {
 
                         e.stopPropagation();
                         e.preventDefault();
 
                         opendxp.helpers.treeNodeThumbnailPreviewHide();
 
                         try {
                             var selection = this.tree.getSelection();
                             if (!selection) {
                                 return true;
                             }
                             if (selection.length < 1) {
                                 return true;
                             }
                         } catch (e2) {
                             return true;
                         }
 
                         var node = selection[0];
                         this.uploadFileList(e.dataTransfer, node);
 
                     }.bind(this), true);
                 }
             } catch (e) {
                 console.log(e);
             }
         }.bind(this));
 
         if(!opendxp.settings.asset_disable_tree_preview) {
             this.tree.on("itemmouseenter", opendxp.helpers.treeNodeThumbnailPreview.bind(this));
             this.tree.on("itemmouseleave", opendxp.helpers.treeNodeThumbnailPreviewHide.bind(this));
         }
 
         store.on("nodebeforeexpand", function (node) {
             opendxp.helpers.addTreeNodeLoadingIndicator("asset", node.data.id, false);
         });
 
         store.on("nodeexpand", function (node, index, item, eOpts) {
             opendxp.helpers.removeTreeNodeLoadingIndicator("asset", node.data.id);
         });
 
         this.config.parentPanel.insert(this.config.index, this.tree);
         this.config.parentPanel.updateLayout();
 
         if (!this.config.parentPanel.alreadyExpanded && this.perspectiveCfg.expanded) {
             this.config.parentPanel.alreadyExpanded = true;
             this.tree.expand();
         }
     },
 
     uploadFileList: function (dataTransfer, parentNode) {
 
         var file;
         this.activeUploads = 0;
         var overwriteConfirmMessageBoxes = [];
 
         var win = new Ext.Window({
             items: [],
             modal: true,
             closable: false,
             bodyStyle: "padding:10px;",
             width: 500,
             autoHeight: true,
             autoScroll: true
         });
         win.show();
 
         var doFileUpload = function (file, path) {
 
             if(typeof path == "undefined") {
                 path = "";
             }
 
             this.activeUploads++;
 
             var pbar = new Ext.ProgressBar({
                 width:465,
                 text: file.name,
                 style: "margin-bottom: 5px"
             });
 
             win.add(pbar);
             win.updateLayout();
 
             var finishedErrorHandler = function (e) {
                 this.activeUploads--;
                 win.remove(pbar);
 
                 if(this.activeUploads < 1) {
                     win.close();
                     opendxp.elementservice.refreshNodeAllTrees("asset", parentNode.get("id"));
                 }
             }.bind(this);
 
             var errorHandler = function (e) {
                 var res = Ext.decode(e["responseText"]);
                 opendxp.helpers.showNotification(t("error"), res.message ? res.message : t("error"), "error", e["responseText"]);
                 finishedErrorHandler();
             }.bind(this);
 
             Ext.Ajax.request({
                 url: Routing.generate('opendxp_admin_asset_exists'),
                 params: {
                     parentId: parentNode.id,
                     filename: file.name,
                     dir: path
                 },
                 async: false,
                 success: function (response) {
                     var res = Ext.decode(response.responseText);
 
                     var uploadFunction = function(allowOverwrite) {
                         opendxp.helpers.uploadAssetFromFileObject(file,
                             Routing.generate('opendxp_admin_asset_addasset', { parentId: parentNode.id, dir: path, allowOverwrite: allowOverwrite ? 1 : 0 }),
                             finishedErrorHandler,
                             function (evt) {
                                 //progress
                                 if (evt.lengthComputable) {
                                     var percentComplete = evt.loaded / evt.total;
                                     var progressText = file.name + " ( " + Math.floor(percentComplete * 100) + "% )";
                                     if (percentComplete == 1) {
                                         progressText = file.name + " " + t("please_wait");
                                     }
 
                                     pbar.updateProgress(percentComplete, progressText);
                                 }
                             },
                             errorHandler
                         );
                     };
 
                     if (res.exists) {
                         let applyToAllCheckbox = Ext.create('Ext.form.field.Checkbox', {
                             boxLabel: t('asset_upload_apply_to_all')
                         });
                         let messageBox = new Ext.window.MessageBox({
                             layout: {
                                 type: 'vbox',
                                 align: 'center'
                             }
                         });
                         overwriteConfirmMessageBoxes.push(messageBox);
                         messageBox.show({
                             title: t('file_exists'),
                             msg: t('asset_upload_want_to_overwrite').replace('%s', file.name),
                             buttons: Ext.Msg.OK & Ext.Msg.YES & Ext.Msg.NO,
                             buttonText: {
                                 ok: t('asset_upload_overwrite'),
                                 yes: t('asset_upload_keep_both'),
                                 no: t('asset_upload_skip')
                             },
                             prompt: false,
                             icon: Ext.MessageBox.QUESTION,
                             fn: function (action) {
                                 if (applyToAllCheckbox.getValue()) {
                                     applyToAllCheckbox.setValue(false); // prevent endless loop
                                     Ext.each(overwriteConfirmMessageBoxes, function (messageBox) {
                                         if (messageBox.isVisible()) {
                                             messageBox.down('button[itemId=' + action + ']').fireHandler();
                                         }
                                     });
                                 }

                                 if (action === 'ok' || action === 'yes') {
                                     uploadFunction(action === 'ok'); // currently visible message box if not visible anymore after clicking a button -> action for current message box gets executed here instead of in above loop
                                 } else {
                                     finishedErrorHandler();
                                 }
                             }
                         });

                         messageBox.add(applyToAllCheckbox);
                     } else {
                         uploadFunction();
                     }
                 }
             });
         }.bind(this);
 
         if(dataTransfer["items"] && dataTransfer.items[0] && dataTransfer.items[0].webkitGetAsEntry) {
             // chrome
             var traverseFileTree = function (item, path) {
                 path = path || "";
                 if (item.isFile) {
                     // Get file
                     item.file(function (file) {
                         doFileUpload(file, path);
                     }.bind(this));
                 } else if (item.isDirectory) {
                     // Get folder contents
                     var dirReader = item.createReader();
                     dirReader.readEntries(function (entries) {
                         for (var i = 0; i < entries.length; i++) {
                             traverseFileTree(entries[i], path + item.name + "/");
                         }
                     });
                 }
             }.bind(this);
 
             for (var i = 0; i < dataTransfer.items.length; i++) {
                 // webkitGetAsEntry is where the magic happens
                 var item = dataTransfer.items[i].webkitGetAsEntry();
                 if (item) {
                     traverseFileTree(item);
                 }
             }
         } else if(dataTransfer["files"]) {
             // default filelist upload
             for (var i=0; i<dataTransfer["files"].length; i++) {
                 file = dataTransfer["files"][i];
 
                 if (window.FileList && file.name && file.size) { // check for size (folder has size=0)
                     doFileUpload(file);
                 } else if (!empty(file.type) && file.size < 1) { //throw error for 0 byte file
                     Ext.MessageBox.alert(t('error'), t('error_empty_file_upload'));
                     win.close();
                 }
             }
 
             // if no files are uploaded (doesn't match criteria, ...) close the progress win immediately
             if(!this.activeUploads) {
                 win.close();
             }
         }
 
         // check in 5 sec. if there're active uploads
         // if not, close the progressbar
         // this is necessary since the folder upload is async, so we don't know if the progress is
         // necessary or not, not really perfect solution, but works as it should
         window.setTimeout(function () {
             if(!this.activeUploads) {
                 win.close();
             }
         }.bind(this), 5000);
     },
 
     getTreeNodeListeners: function () {
         var treeNodeListeners = {
             'itemclick' : this.onTreeNodeClick,
             "itemcontextmenu": this.onTreeNodeContextmenu.bind(this),
             "itemmove": this.onTreeNodeMove.bind(this),
             "beforeitemmove": this.onTreeNodeBeforeMove.bind(this),
             "itemmouseenter": function (el, record, item, index, e, eOpts) {
                 opendxp.helpers.treeToolTipShow(el, record, item);
             },
             "itemmouseleave": function () {
                 opendxp.helpers.treeToolTipHide();
             }
         };
 
         return treeNodeListeners;
     },
 
     onTreeNodeClick: function (tree, record, item, index, event, eOpts ) {
         if (event.ctrlKey === false && event.shiftKey === false && event.altKey === false) {
             if (record.data.permissions && record.data.permissions.view) {
                 opendxp.helpers.treeNodeThumbnailPreviewHide();
                 opendxp.helpers.openAsset(record.data.id, record.data.type);
             }
         }
     },
 
 
     onTreeNodeOver: function (targetNode, position, dragData, e, eOpts ) {
         var node = dragData.records[0];
         if (node.getOwnerTree() != targetNode.getOwnerTree()) {
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
 
         var tree = node.getOwnerTree();
 
         opendxp.elementservice.updateAsset(node.data.id, {
             parentId: newParent.data.id
         }, function (newParent, oldParent, tree, response) {
             try{
                 var rdata = Ext.decode(response.responseText);
                 if (rdata && rdata.success) {
                     // set new pathes
                     var newBasePath = newParent.data.path;
                     if (newBasePath == "/") {
                         newBasePath = "";
                     }
                     node.data.basePath = newBasePath;
                     node.data.path = node.data.basePath + "/" + node.data.text;
                 }
                 else {
                     this.tree.loadMask.hide();
                     opendxp.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                         "error",t(rdata.message));
                     // we have to delay refresh between two nodes,
                     // as there could be parent child relationship leading to race condition
                     window.setTimeout(function () {
                         opendxp.elementservice.refreshNode(oldParent);
                     }, 500);
                     opendxp.elementservice.refreshNode(newParent);
                 }
             } catch(e){
                 this.tree.loadMask.hide();
                 opendxp.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
                 // we have to delay refresh between two nodes,
                 // as there could be parent child relationship leading to race condition
                 window.setTimeout(function () {
                     opendxp.elementservice.refreshNode(oldParent);
                 }, 500);
                 opendxp.elementservice.refreshNode(newParent);
             }
             this.tree.loadMask.hide();
 
         }.bind(this, newParent, oldParent, tree));
     },
 
     onTreeNodeBeforeMove: function (node, oldParent, newParent, index, eOpts ) {
         if (oldParent.getOwnerTree().getId() != newParent.getOwnerTree().getId()) {
             Ext.MessageBox.alert(t('error'), t('cross_tree_moves_not_supported'));
             return false;
         }
 
         // check for locks
         if (node.data.locked) {
             Ext.MessageBox.alert(t('locked'), t('element_cannot_be_move_because_it_is_locked'));
             return false;
         }
 
         // check new parent's permission
         if(!newParent.data.permissions.create){
             Ext.MessageBox.alert(' ', t('element_cannot_be_moved'));
             return false;
         }
 
         // check for permission
         if (node.data.permissions.settings) {
             this.tree.loadMask.show();
             return true;
         }
         return false;
     },
 
     onTreeNodeContextmenu: function (tree, record, item, index, e, eOpts ) {
         e.stopEvent();
 
         if(opendxp.helpers.hasTreeNodeLoadingIndicator("asset", record.id)) {
             return;
         }
 
         var menu = new Ext.menu.Menu();
         var perspectiveCfg = this.perspectiveCfg;
 
         if(tree.getSelectionModel().getSelected().length > 1) {
             var selectedIds = [];
             tree.getSelectionModel().getSelected().each(function (item) {
                 selectedIds.push(item.id);
             });
 
             if (record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("asset.delete")) {
                 menu.add(new Ext.menu.Item({
                     text: t('delete'),
                     iconCls: "opendxp_icon_delete",
                     handler: this.deleteAsset.bind(this, selectedIds.join(','))
                 }));
             }
         } else {
             if (record.data.type == "folder") {
                 if (record.data.permissions.create) {
 
                     var menuItems = [];
 
                     if (perspectiveCfg.inTreeContextMenu("asset.add")) {
                         if (perspectiveCfg.inTreeContextMenu("asset.add.upload")) {
                             menuItems.push({
                                 text: t("upload_files"),
                                 iconCls: "opendxp_icon_upload",
                                 listeners: {
                                     "afterrender": function (el, eOpts) {
                                         // we need to do this vanilla javascript and directly after finishing rendering
                                         // otherwise this will cause issues when used with hybrid touch devices, see also:
                                         var fileElemId = 'assetMultiUploadField';
                                         if (!document.getElementById(fileElemId)) {
                                             document.body.insertAdjacentHTML('beforeend', '<input type="file" id="' + fileElemId + '" multiple>');
                                         }
 
                                         var fileSelect = el.getEl().down('a', true),
                                             fileElem = document.getElementById(fileElemId);
 
                                         if (fileElem['onChangeListener']) {
                                             fileElem.removeEventListener('change', fileElem['onChangeListener']);
                                         }
 
                                         fileElem['onChangeListener'] = function (e) {
                                             if (e.target.files.length) {
                                                 this.uploadFileList(e.target, record);
                                             }
                                         }.bind(this);
 
                                         fileElem.addEventListener("change", fileElem['onChangeListener']);
 
                                         fileSelect.addEventListener("click", function (e) {
                                             if (fileElem) {
                                                 fileElem.value = fileElem.defaultValue;
                                                 fileElem.click();
                                             }
                                             e.preventDefault();
                                         }, false);
                                     }.bind(this)
                                 }
                             });
                         }
 
                         if (perspectiveCfg.inTreeContextMenu("asset.add.uploadCompatibility")) {
                             menuItems.push({
                                 text: t("upload_compatibility_mode"),
                                 handler: this.addSingleAsset.bind(this, tree, record),
                                 iconCls: "opendxp_icon_upload"
                             });
                         }
 
                         if (perspectiveCfg.inTreeContextMenu("asset.add.uploadZip")) {
                             menuItems.push({
                                 text: t("upload_zip"),
                                 handler: this.uploadZip.bind(this, tree, record),
                                 iconCls: "opendxp_icon_zip opendxp_icon_overlay_upload"
                             });
                         }
                         
                         if (menuItems.length > 0) {
                             menu.add(new Ext.menu.Item({
                                 text: t('add_assets'),
                                 iconCls: "opendxp_icon_asset opendxp_icon_overlay_add",
                                 hideOnClick: false,
                                 menu: menuItems
                             }));
                         }
                     }
 
                     if (perspectiveCfg.inTreeContextMenu("asset.addFolder")) {
                         menu.add(new Ext.menu.Item({
                             text: t('create_folder'),
                             iconCls: "opendxp_icon_folder opendxp_icon_overlay_add",
                             handler: this.addFolder.bind(this, tree, record)
                         }));
                     }
 
                     menu.add("-");
 
                 }
             }

             if (record.data.permissions && record.data.permissions.rename && record.data.id != 1 && !record.data.locked) {
                 if (perspectiveCfg.inTreeContextMenu("asset.rename")) {
                     menu.add(new Ext.menu.Item({
                         text: t('rename'),
                         iconCls: "opendxp_icon_key opendxp_icon_overlay_go",
                         handler: this.editAssetKey.bind(this, tree, record)
                     }));
                 }
             }
 
             if (this.id != 1 && record.data.permissions && record.data.permissions.view) {
                 if (perspectiveCfg.inTreeContextMenu("asset.copy")) {
                     menu.add(new Ext.menu.Item({
                         text: t('copy'),
                         iconCls: "opendxp_icon_copy",
                         handler: this.copy.bind(this, tree, record)
                     }));
                 }
             }
 
             //cut
             if (record.data.id != 1 && !record.data.locked && record.data.permissions && record.data.permissions.rename) {
                 if (perspectiveCfg.inTreeContextMenu("asset.cut")) {
                     menu.add(new Ext.menu.Item({
                         text: t('cut'),
                         iconCls: "opendxp_icon_cut",
                         handler: this.cut.bind(this, tree, record)
                     }));
                 }
             }
 
 
             //paste
             if (opendxp.cachedAssetId
                 && record.data.permissions
                 && (record.data.permissions.create || record.data.permissions.publish)
                 && perspectiveCfg.inTreeContextMenu("asset.paste")) {
 
                 if (record.data.type == "folder") {
                     menu.add(new Ext.menu.Item({
                         text: t('paste'),
                         iconCls: "opendxp_icon_paste",
                         handler: this.pasteInfo.bind(this, tree, record, "recursive")
                     }));
                 } else {
                     menu.add(new Ext.menu.Item({
                         text: t('paste'),
                         iconCls: "opendxp_icon_paste",
                         handler: this.pasteInfo.bind(this, tree, record, "replace")
                     }));
                 }
             }
 
             if (record.data.type == "folder" && opendxp.cutAsset
                 && record.data.permissions
                 && (record.data.permissions.create || record.data.permissions.publish)
                 && perspectiveCfg.inTreeContextMenu("asset.pasteCut")) {
                 menu.add(new Ext.menu.Item({
                     text: t('paste_cut_element'),
                     iconCls: "opendxp_icon_paste",
                     handler: function () {
                         this.pasteCutAsset(opendxp.cutAsset,
                             opendxp.cutAssetParentNode, record, this.tree);
                         opendxp.cutAssetParentNode = null;
                         opendxp.cutAsset = null;
                     }.bind(this)
                 }));
             }
 
             if (record.data.permissions && record.data.permissions.remove && record.data.id != 1 && !record.data.locked && perspectiveCfg.inTreeContextMenu("asset.delete")) {
                 menu.add(new Ext.menu.Item({
                     text: t('delete'),
                     iconCls: "opendxp_icon_delete",
                     handler: this.deleteAsset.bind(this, record.data.id)
                 }));
             }
 
             // upload & download
             if (record.data.permissions && record.data.permissions.view) {
                 menu.add("-");
 
                 if (record.data.type == "folder") {
                     menu.add({
                         text: t("download_as_zip"),
                         iconCls: "opendxp_icon_zip opendxp_icon_overlay_download",
                         hideOnClick: false,
                         menu: opendxp.elementservice.getAssetZipDownloadMenu(record.data.id)
                     });
                 } else {
                     if (record.data.permissions.publish) {
                         menu.add(new Ext.menu.Item({
                             text: t('upload_new_version'),
                             iconCls: "opendxp_icon_upload",
                             handler: function () {
                                 opendxp.elementservice.replaceAsset(record.data.id, function () {
                                     opendxp.elementservice.refreshNodeAllTrees("asset", record.parentNode.id);
                                 });
                             }
                         }));
                     }
 
                     menu.add(new Ext.menu.Item({
                         text: t('download'),
                         iconCls: "opendxp_icon_download",
                         handler: function () {
                             opendxp.helpers.download(Routing.generate('opendxp_admin_asset_download', {id: record.data.id}));
                         }
                     }));
                 }
             }
 
             // advanced menu
             var advancedMenuItems = [];
             var user = opendxp.globalmanager.get("user");
 
             if (record.data.permissions && record.data.permissions.create &&
                 !record.data.locked &&
                 perspectiveCfg.inTreeContextMenu("asset.searchAndMove") &&
                 opendxp.helpers.hasSearchImplementation()) {
                 advancedMenuItems.push({
                     text: t('search_and_move'),
                     iconCls: "opendxp_icon_search opendxp_icon_overlay_go",
                     handler: this.searchAndMove.bind(this, tree, record)
                 });
             }
 
             if (record.data.id != 1 && user.admin) {
                 var lockMenu = [];
                 if (record.data.lockOwner && perspectiveCfg.inTreeContextMenu("asset.unlock")) { // add unlock
                     lockMenu.push({
                         text: t('unlock'),
                         iconCls: "opendxp_icon_lock opendxp_icon_overlay_delete",
                         handler: function () {
                             opendxp.elementservice.lockElement({
                                 elementType: "asset",
                                 id: record.data.id,
                                 mode: null
                             });
                         }.bind(this)
                     });
                 } else if (perspectiveCfg.inTreeContextMenu("asset.lock")) {
                     lockMenu.push({
                         text: t('lock'),
                         iconCls: "opendxp_icon_lock opendxp_icon_overlay_add",
                         handler: function () {
                             opendxp.elementservice.lockElement({
                                 elementType: "asset",
                                 id: record.data.id,
                                 mode: "self"
                             });
                         }.bind(this)
                     });
 
                     if (record.data.type == "folder" && perspectiveCfg.inTreeContextMenu("asset.lockAndPropagate")) {
                         lockMenu.push({
                             text: t('lock_and_propagate_to_children'),
                             iconCls: "opendxp_icon_lock opendxp_icon_overlay_go",
                             handler: function () {
                                 opendxp.elementservice.lockElement({
                                     elementType: "asset",
                                     id: record.data.id,
                                     mode: "propagate"
                                 });
                             }.bind(this)
                         });
                     }
                 }
 
                 if (record.data.locked && perspectiveCfg.inTreeContextMenu("asset.unlockAndPropagate")) {
                     // add unlock and propagate to children functionality
                     lockMenu.push({
                         text: t('unlock_and_propagate_to_children'),
                         iconCls: "opendxp_icon_lock opendxp_icon_overlay_delete",
                         handler: function () {
                             opendxp.elementservice.unlockElement({
                                 elementType: "asset",
                                 id: record.data.id
                             });
                         }.bind(this)
                     });
                 }
 
                 if (lockMenu.length > 0) {
                     advancedMenuItems.push({
                         text: t('lock'),
                         iconCls: "opendxp_icon_lock",
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
 
             if (record.data.type == "folder" && perspectiveCfg.inTreeContextMenu("asset.reload")) {
                 menu.add(new Ext.menu.Item({
                     text: t('refresh'),
                     iconCls: "opendxp_icon_reload",
                     handler: opendxp.elementservice.refreshNode.bind(this, record)
                 }));
             }
         }
 
         opendxp.helpers.hideRedundantSeparators(menu);
 
         const prepareAssetTreeContextMenu = new CustomEvent(opendxp.events.prepareAssetTreeContextMenu, {
             detail: {
                 menu: menu,
                 tree: this,
                 asset: record
             }
         });
 
         document.dispatchEvent(prepareAssetTreeContextMenu);
 
         menu.showAt(e.pageX+1, e.pageY+1);
     },
 
 
     copy: function (tree, record) {
         opendxp.cachedAssetId = record.id;
     },
 
     cut: function (tree, record) {
         opendxp.cutAsset = record;
         opendxp.cutAssetParentNode = record.parentNode;
     },
 
     pasteCutAsset: function(asset, oldParent, newParent, tree) {
         opendxp.elementservice.updateAsset(asset.id, {
             parentId: newParent.id
         }, function (asset, newParent, oldParent, tree, response) {
             try{
                 var rdata = Ext.decode(response.responseText);
                 if (rdata && rdata.success) {
                     // set new pathes
                     var newBasePath = newParent.data.path;
                     if (newBasePath == "/") {
                         newBasePath = "";
                     }
                     asset.data.basePath = newBasePath;
                     asset.data.path = asset.data.basePath + "/" + asset.data.text;
                 }
                 else {
                     this.tree.loadMask.hide();
                     opendxp.helpers.showNotification(t("error"), t("cant_move_node_to_target"),
                         "error",t(rdata.message));
                 }
             } catch(e){
                 this.tree.loadMask.hide();
                 opendxp.helpers.showNotification(t("error"), t("cant_move_node_to_target"), "error");
             }
             this.tree.loadMask.hide();
             opendxp.elementservice.refreshNodeAllTrees("asset", oldParent.id);
             opendxp.elementservice.refreshNodeAllTrees("asset", newParent.id);
             newParent.expand();
         }.bind(this, asset, newParent, oldParent, tree));
 
     },
 
     pasteInfo: function (tree, record, type) {
         opendxp.helpers.addTreeNodeLoadingIndicator("asset", record.id);
 
         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_asset_copyinfo'),
             params: {
                 targetId: record.id,
                 sourceId: opendxp.cachedAssetId,
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
                     layout:'fit',
                     width:200,
                     bodyStyle: "padding: 10px;",
                     closable:false,
                     plain: true,
                     items: [record.pasteProgressBar],
                     listeners: opendxp.helpers.getProgressWindowListeners()
                 });
 
                 record.pasteWindow.show();
 
                 var pj = new opendxp.tool.paralleljobs({
                     success: function () {
 
                         try {
                             this.pasteComplete(tree, record);
                         } catch(e) {
                             console.log(e);
                             opendxp.helpers.showNotification(t("error"), t("error_pasting_item"), "error");
                             opendxp.elementservice.refreshNodeAllTrees("asset", record.parentNode.id);
                         }
                     }.bind(this),
                     update: function (currentStep, steps, percent) {
                         if(record.pasteProgressBar) {
                             var status = currentStep / steps;
                             record.pasteProgressBar.updateProgress(status, percent + "%");
                         }
                     }.bind(this),
                     failure: function (message) {
                         this.pasteWindow.close();
                         record.pasteProgressBar = null;
 
                         opendxp.helpers.showNotification(t("error"), t("error_pasting_item"), "error", t(message));
                         opendxp.elementservice.refreshNodeAllTrees("asset", record.parentNode.id);
                     }.bind(this),
                     jobs: res.pastejobs
                 });
             } else {
                 throw "There are no pasting jobs";
             }
         } catch (e) {
             console.log(e);
             Ext.MessageBox.alert(t('error'), e);
             this.pasteComplete(this, tree, record);
         }
     },
 
     pasteComplete: function (tree, record) {
         if(record.pasteWindow) {
             record.pasteWindow.close();
         }
 
         record.pasteProgressBar = null;
         record.pasteWindow = null;
 
         opendxp.elementservice.refreshNodeAllTrees("asset", record.id);
     },
 
     addFolder : function (tree, record) {
         Ext.MessageBox.prompt(t('create_folder'), t('enter_the_name_of_the_new_item'),
             this.addFolderCreate.bind(this, tree, record));
     },
 
     addFolderCreate: function (tree, record, button, value, object) {
 
         if (button == "ok") {
 
             // check for identical folder name in current level
             if (opendxp.elementservice.isKeyExistingInLevel(record, value)) {
                 return;
             }
 
             Ext.Ajax.request({
                 url: Routing.generate('opendxp_admin_asset_addfolder'),
                 method: "POST",
                 params: {
                     parentId: record.data.id,
                     name: opendxp.helpers.getValidFilename(value, "asset")
                 },
                 success: this.addFolderComplete.bind(this, tree, record)
             });
         }
     },
 
     addFolderComplete: function (tree, record, response) {
         try{
             var rdata = Ext.decode(response.responseText);
             if (rdata && rdata.success) {
                 record.data.leaf = false;
                 //this.renderIndent();
                 record.expand();
             }
             else {
                 opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"),
                     "error",t(rdata.message));
             }
         } catch(e){
             opendxp.helpers.showNotification(t("error"), t("failed_to_create_new_item"), "error");
         }
         opendxp.elementservice.refreshNodeAllTrees("asset", record.get("id"));
     },
 
     addSingleAsset: function (tree, record) {
         opendxp.helpers.assetSingleUploadDialog(record.data.id, "id", function (res) {
             var f = this.addAssetComplete.bind(this, tree, record);
             f();
         }.bind(this), function (res) {
             var response = Ext.decode(res.response.responseText);
             if(response.success === false) {
                 opendxp.helpers.showNotification(t("error"), response.message, "error",
                     res.response.responseText);
             }
             var f = this.addAssetComplete.bind(this, tree, record);
             f();
         }.bind(this));
     },
 
     uploadZip: function (tree, record) {

         const uploadFunction = function(allowOverwrite) {
             opendxp.helpers.uploadDialog(Routing.generate('opendxp_admin_asset_importzip', {parentId: record.id, allowOverwrite: allowOverwrite ? 'true' : 'false' }), "Filedata", function (response) {
                 // this.attributes.reference
                 var res = Ext.decode(response.response.responseText);
                 opendxp.helpers.addTreeNodeLoadingIndicator("asset", record.get("id"));

                 this.downloadProgressBar = new Ext.ProgressBar({
                     text: t('initializing')
                 });

                 this.downloadProgressWin = new Ext.Window({
                     title: t("upload_zip"),
                     layout: 'fit',
                     width: 200,
                     bodyStyle: "padding: 10px;",
                     closable: false,
                     plain: true,
                     items: [this.downloadProgressBar],
                     listeners: opendxp.helpers.getProgressWindowListeners()
                 });

                 this.downloadProgressWin.show();

                 var pj = new opendxp.tool.paralleljobs({
                     success: function (jobId) {
                         if (this.downloadProgressWin) {
                             this.downloadProgressWin.close();
                         }

                         this.downloadProgressBar = null;
                         this.downloadProgressWin = null;

                         opendxp.elementservice.refreshNodeAllTrees("asset", record.get("id"));
                     }.bind(this, res.jobId),
                     update: function (currentStep, steps, percent) {
                         if (this.downloadProgressBar) {
                             var status = currentStep / steps;
                             this.downloadProgressBar.updateProgress(status, percent + "%");
                         }
                     }.bind(this),
                     failure: function (message) {
                         this.downloadProgressWin.close();
                         opendxp.elementservice.refreshNodeAllTrees("asset", record.get("id"));
                         opendxp.helpers.showNotification(t("error"), t("error"),
                             "error", t(message));
                     }.bind(this),
                     jobs: res.jobs
                 });
             }.bind(this), function (res) {
                 var response = Ext.decode(res.response.responseText);
                 if (response && response.success === false) {
                     opendxp.helpers.showNotification(t("error"), response.message, "error",
                         res.response.responseText);
                 } else {
                     opendxp.helpers.showNotification(t("error"), res, "error",
                         res.response.responseText);
                 }

                 opendxp.elementservice.refreshNodeAllTrees("asset", record.parentNode.get("id"));
             }.bind(this));
         }

         const messageBox = new Ext.window.MessageBox({
             layout: {
                 type: 'vbox',
                 align: 'center'
             }
         });

         messageBox.show({
             title: t('overwrite_zip_files'),
             msg: t('zip_upload_want_to_overwrite'),
             buttons: Ext.Msg.OK & Ext.Msg.NO,
             buttonText: {
                 yes: t('zip_upload_want_to_overwrite_yes_option'),
                 no: t('zip_upload_want_to_overwrite_no_option')
             },
             prompt: false,
             icon: Ext.MessageBox.QUESTION,
             fn: function (action) {
                 if (action === 'yes') {
                     uploadFunction(action === 'yes'); // currently visible message box if not visible anymore after clicking a button -> action for current message box gets executed here instead of in above loop
                 } else {
                     uploadFunction()
                 }
             }
         });
     },
 
     enableHtml5Upload: function (node, rowIdx, out) {
 
         if (!window["FileList"]) {
             return;
         }
 
         // only for folders
         if (node.data.type != "folder") {
             return;
         }
 
         // timeout because there is no afterrender function
         window.setTimeout(this.addHtml5DragListener.bind(this, node), 2000);
     },
 
     addHtml5DragListener: function (node) {
 
         try {
             var tree = this.tree;
             var el = Ext.fly(tree.getView().getNodeByRecord(node));
             if(el) {
                 el = el.dom;
                 var fn = function (e) {
                     //e.stopPropagation();
                     e.preventDefault();
                     tree.setSelection(node);
 
                     e.dataTransfer.dropEffect = 'copy';
 
                     return false;
                 };
 
                 el.addEventListener("dragenter", fn, true);
                 el.addEventListener("dragover", fn, true);
             }
         }
         catch (e) {
             console.log(e);
         }
     },

     addAssetComplete: function (tree, record, config, file, response) {
 
         record.data.leaf = false;
         record.expand();
         opendxp.elementservice.refreshNodeAllTrees("asset", record.get("id"));
     },
 
     editAssetKey: function (tree, record) {
         var options = {
             sourceTree: tree,
             elementType: "asset",
             elementSubType: record.data.type,
             id: record.data.id,
             default: Ext.util.Format.htmlDecode(record.data.key)
         };
         opendxp.elementservice.editElementKey(options);
     },
 
 
     searchAndMove: function(tree, record) {
         opendxp.helpers.searchAndMove(record.data.id, function() {
             opendxp.elementservice.refreshNode(record);
         }.bind(this), "asset");
     },
 
 
 
     deleteAsset : function (ids) {
         var options = {
             "elementType" : "asset",
             "id": ids
         };
 
         opendxp.elementservice.deleteElement(options);
     }
 });
 

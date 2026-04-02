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

opendxp.registerNS("opendxp.layout.toolbar");
/**
 * @private
 */
opendxp.layout.toolbar = Class.create({

     initialize: function() {

         var user = opendxp.globalmanager.get("user");
         this.toolbar = Ext.getCmp("opendxp_panel_toolbar");

         var perspectiveCfg = opendxp.globalmanager.get("perspective");

         var menu = {};

         if (perspectiveCfg.inToolbar("file")) {
             var fileItems = [];

             if (perspectiveCfg.inToolbar("file.perspectives")) {

                 if (opendxp.settings.availablePerspectives.length > 1) {

                     var items = [];
                     for (var i = 0; i < opendxp.settings.availablePerspectives.length; i++) {
                         var perspective = opendxp.settings.availablePerspectives[i];
                         var itemCfg = {
                             text: t(perspective.name),
                             disabled: perspective.active,
                             itemId: 'opendxp_menu_file_perspective_' + perspective.name.replace(/[^a-z0-9\-_]+/ig, '-'),
                             handler: this.openPerspective.bind(this, perspective.name)
                         };

                         if (perspective.icon) {
                             itemCfg.icon = perspective.icon;
                         } else if (perspective.iconCls) {
                             itemCfg.iconCls = perspective.iconCls;
                         }

                         items.push(itemCfg);
                     }

                     this.perspectivesMenu = {
                         text: t("perspectives"),
                         iconCls: "opendxp_nav_icon_perspective",
                         itemId: 'opendxp_menu_file_perspective',
                         hideOnClick: false,
                         menu: {
                             cls: "opendxp_navigation_flyout",
                             shadow: false,
                             items: items
                         }
                     };
                     fileItems.push(this.perspectivesMenu);
                 }
             }


             if (user.isAllowed("dashboards") && perspectiveCfg.inToolbar("file.dashboards")) {
                 this.dashboardMenu = {
                     text: t("dashboards"),
                     iconCls: "opendxp_nav_icon_dashboards",
                     itemId: 'opendxp_menu_file_dashboards',
                     hideOnClick: false,
                     menu: {
                         cls: "opendxp_navigation_flyout",
                         shadow: false,
                         items: [{
                             text: t("welcome"),
                             iconCls: "opendxp_nav_icon_dashboards",
                             itemId: 'opendxp_menu_file_dashboards_welcome',
                             handler: opendxp.helpers.openWelcomePage.bind(this)
                         }]
                     }
                 };

                 Ext.Ajax.request({
                     url: Routing.generate('opendxp_admin_portal_dashboardlist'),
                     success: function (response) {
                         var data = Ext.decode(response.responseText);
                         for (var i = 0; i < data.length; i++) {
                             this.dashboardMenu.menu.add({
                                 text: t(data[i]),
                                 iconCls: "opendxp_nav_icon_dashboards",
                                 itemId: 'opendxp_menu_file_dashboards_custom_' + data[i],
                                 handler: function (key) {
                                     try {
                                         opendxp.globalmanager.get("layout_portal_" + key).activate();
                                     }
                                     catch (e) {
                                         opendxp.globalmanager.add("layout_portal_" + key, new opendxp.layout.portal(key));
                                     }
                                 }.bind(this, data[i])
                             });
                         }

                         this.dashboardMenu.menu.add(new Ext.menu.Separator({}));
                         this.dashboardMenu.menu.add({
                             text: t("add"),
                             iconCls: "opendxp_nav_icon_add",
                             itemId: 'opendxp_menu_file_dashboards_add',
                             handler: function () {
                                 var prompt = Ext.MessageBox.prompt(' ', t('enter_the_name_of_the_new_item'),
                                     function (button, value, object) {
                                         if (button == "ok") {
                                             Ext.Ajax.request({
                                                 url: Routing.generate('opendxp_admin_portal_createdashboard'),
                                                 method: 'POST',
                                                 params: {
                                                     key: value
                                                 },
                                                 success: function (response) {
                                                     var response = Ext.decode(response.responseText);
                                                     if (response.success) {
                                                         Ext.MessageBox.confirm(t("info"), t("reload_opendxp_changes"), function (buttonValue) {
                                                             if (buttonValue == "yes") {
                                                                 window.location.reload();
                                                             }
                                                         });
                                                         try {
                                                             opendxp.globalmanager.get("layout_portal_" + value).activate();
                                                         }
                                                         catch (e) {
                                                             opendxp.globalmanager.add("layout_portal_" + value, new opendxp.layout.portal(value));
                                                         }
                                                     } else {
                                                         Ext.Msg.show({
                                                             title: t("error"),
                                                             msg: t(response.message),
                                                             buttons: Ext.Msg.OK,
                                                             animEl: 'elId',
                                                             icon: Ext.MessageBox.ERROR
                                                         });
                                                     }
                                                 }
                                             });
                                         }
                                     }
                                 );
                                 prompt.textField.on('keyUp', function(el){
                                     el.setValue(el.getValue().replace(/\W/g, ''));
                                 }, this);
                             }.bind(this)
                         });
                     }.bind(this)
                 });

                 fileItems.push(this.dashboardMenu);
             }


             if (user.isAllowed("documents") && perspectiveCfg.inToolbar("file.openDocument")) {
                 fileItems.push({
                     text: t("open_document_by_id"),
                     iconCls: "opendxp_nav_icon_document opendxp_icon_overlay_go",
                     itemId: 'opendxp_menu_file_open_document_by_id',
                     handler: opendxp.helpers.openElementByIdDialog.bind(this, "document")
                 });
             }

             if (user.isAllowed("assets") && perspectiveCfg.inToolbar("file.openAsset")) {
                 fileItems.push({
                     text: t("open_asset_by_id"),
                     iconCls: "opendxp_nav_icon_asset opendxp_icon_overlay_go",
                     itemId: 'opendxp_menu_file_open_asset_by_id',
                     handler: opendxp.helpers.openElementByIdDialog.bind(this, "asset")
                 });
             }

             if (user.isAllowed("objects") && perspectiveCfg.inToolbar("file.openObject")) {
                 fileItems.push({
                     text: t("open_data_object"),
                     iconCls: "opendxp_nav_icon_object opendxp_icon_overlay_go",
                     itemId: 'opendxp_menu_file_open_data_object',
                     handler: opendxp.helpers.openElementByIdDialog.bind(this, "object")
                 });
             }

             if (perspectiveCfg.inToolbar("file.searchReplace") && (user.isAllowed("objects") || user.isAllowed("documents") || user.isAllowed("assets"))) {
                 fileItems.push({
                     text: t("search_replace_assignments"),
                     iconCls: "opendxp_nav_icon_search opendxp_icon_overlay_go",
                     itemId: 'opendxp_menu_file_search_replace_assigments',
                     handler: function () {
                         new opendxp.element.replace_assignments();
                     }
                 });
             }

             if (perspectiveCfg.inToolbar("file.schedule") && (user.isAllowed("objects") || user.isAllowed("documents") || user.isAllowed("assets"))) {
                 fileItems.push({
                     text: t('element_history'),
                     iconCls: "opendxp_nav_icon_history",
                     itemId: 'opendxp_menu_file_element_history',
                     cls: "opendxp_main_menu",
                     handler: this.showElementHistory.bind(this)
                 });
             }

             if (user.isAllowed("seemode") && perspectiveCfg.inToolbar("file.seemode")) {
                 fileItems.push({
                     text: t("seemode"),
                     iconCls: "opendxp_nav_icon_seemode",
                     itemId: 'opendxp_menu_file_seemode',
                     cls: "opendxp_main_menu",
                     handler: opendxp.helpers.openSeemode
                 });
             }

             if (perspectiveCfg.inToolbar("file.closeAll")) {
                 fileItems.push({
                     text: t("close_all_tabs"),
                     iconCls: "opendxp_nav_icon_close_all",
                     itemId: 'opendxp_menu_file_close_all_tabs',
                     handler: this.closeAllTabs
                 });
             }

             if (perspectiveCfg.inToolbar("file.help")) {

                 fileItems.push({
                     text: t('help'),
                     iconCls: "opendxp_nav_icon_help",
                     itemId: 'opendxp_menu_file_help',
                     cls: "opendxp_main_menu",
                     hideOnClick: false,
                     menu: {
                         cls: "opendxp_navigation_flyout",
                         shadow: false,
                         items: [{
                             text: t("documentation"),
                             iconCls: "opendxp_nav_icon_documentation",
                             itemId: 'opendxp_menu_file_help_documentation',
                             handler: function () {
                                 window.open("https://docs.opendxp.io/docs/core-framework");
                             }
                         },
                             {
                                 text: t("report_bugs"),
                                 iconCls: "opendxp_nav_icon_github",
                                 itemId: 'opendxp_menu_file_help_report_bugs',
                                 handler: function () {
                                     window.open("https://github.com/open-dxp/opendxp/issues");
                                 }
                             }
                         ]
                     }
                 });
             }


             if (perspectiveCfg.inToolbar("file.about")) {
                 fileItems.push({
                     text: t("about_opendxp"),
                     iconCls: "opendxp_nav_icon_opendxp",
                     itemId: 'opendxp_menu_file_about_opendxp',
                     handler: function () {
                         opendxp.helpers.showAbout();
                     }
                 });
             }

             menu.file = {
                 label: t('file'),
                 iconCls: 'opendxp_main_nav_icon_file',
                 items: fileItems,
                 shadow: false,
                 cls: "opendxp_navigation_flyout"
             };
         }

         if (perspectiveCfg.inToolbar("extras")) {

             var extrasItems = [];

             let translationItems = [];

             if (user.isAllowed("translations") && perspectiveCfg.inToolbar("extras.translations")) {
                 translationItems = [{
                     text: t("translations"),
                     iconCls: "opendxp_nav_icon_translations",
                     itemId: 'opendxp_menu_extras_translations_shared_translations',
                     handler: this.editTranslations.bind(this, 'messages'),
                     priority: 10
                 }];

                 extrasItems.push({
                     text: t("translations"),
                     iconCls: "opendxp_nav_icon_translations",
                     itemId: 'opendxp_menu_extras_translations',
                     hideOnClick: false,
                     menu: {
                         cls: "opendxp_navigation_flyout",
                         shadow: false,
                         items: translationItems
                     }
                 });
             }

             if (user.isAllowed("recyclebin") && perspectiveCfg.inToolbar("extras.recyclebin")) {
                 extrasItems.push({
                     text: t("recyclebin"),
                     iconCls: "opendxp_nav_icon_recyclebin",
                     itemId: 'opendxp_menu_extras_recyclebin',
                     handler: this.recyclebin
                 });
             }

             if (user.isAllowed("notes_events") && perspectiveCfg.inToolbar("extras.notesEvents")) {
                 extrasItems.push({
                     text: t('notes_events'),
                     iconCls: "opendxp_nav_icon_notes",
                     itemId: 'opendxp_menu_extras_notes',
                     handler: this.notes
                 });
             }

             if(user.isAllowed("gdpr_data_extractor")&& perspectiveCfg.inToolbar("extras.gdpr_data_extractor")) {
                 extrasItems.push({
                     text: t("gdpr_data_extractor"),
                     iconCls: "opendxp_nav_icon_gdpr",
                     itemId: 'opendxp_menu_extras_gdpr',
                     handler: function() {
                         new opendxp.settings.gdpr.gdprPanel();
                     }
                 });
             }

             if (extrasItems.length > 0) {
                 extrasItems.push("-");
             }

             if (user.isAllowed("emails") && perspectiveCfg.inToolbar("extras.emails")) {
                 extrasItems.push({
                     text: t("email"),
                     iconCls: "opendxp_nav_icon_email",
                     itemId: 'opendxp_menu_extras_email',
                     hideOnClick: false,
                     menu: {
                         cls: "opendxp_navigation_flyout",
                         shadow: false,
                         items: [{
                             text: t("email_logs"),
                             iconCls: "opendxp_nav_icon_email",
                             itemId: 'opendxp_menu_extras_email_logs',
                             handler: this.sentEmailsLog
                         }, {
                             text: t("email_blocklist"),
                             iconCls: "opendxp_nav_icon_email",
                             itemId: 'opendxp_menu_extras_email_blocklist',
                             handler: this.emailBlocklist
                         }, {
                             text: t("send_test_email"),
                             iconCls: "opendxp_nav_icon_email",
                             itemId: 'opendxp_menu_extras_mail_send_test_mail',
                             handler: this.sendTestEmail
                         }]
                     }
                 });
             }

             if (user.admin) {
                 if (perspectiveCfg.inToolbar("extras.maintenance")) {
                     extrasItems.push({
                         text: t("maintenance_mode"),
                         iconCls: "opendxp_nav_icon_maintenance",
                         itemId: 'opendxp_menu_extras_maintenance_mode',
                         handler: this.showMaintenance
                     });
                 }

                 var systemItems = [];

                 if (perspectiveCfg.inToolbar("extras.systemtools.requirements")) {
                     systemItems.push(
                         {
                             text: t("system_requirements_check"),
                             iconCls: "opendxp_nav_icon_systemrequirements",
                             itemId: 'opendxp_menu_extras_system_info_system_requirements_check',
                             handler: this.showSystemRequirementsCheck,
                             priority: 30
                         }
                     );
                 }

                 extrasItems.push({
                     text: t("system_infos_and_tools"),
                     iconCls: "opendxp_nav_icon_info",
                     hideOnClick: false,
                     itemId: 'opendxp_menu_extras_system_info',
                     menu: {
                         cls: "opendxp_navigation_flyout",
                         shadow: false,
                         items: systemItems
                     }
                 });
             }

             // adding menu even though extraItems can be empty
             // items can be added via event later
             menu.extras = {
                 label: t('tools'),
                 iconCls: 'opendxp_main_nav_icon_build',
                 items: extrasItems,
                 shadow: false,
                 cls: "opendxp_navigation_flyout"
             };
         }

         if (perspectiveCfg.inToolbar("marketing")) {
             // marketing menu
             var marketingItems = [];

             menu.marketing = {
                 label: t('marketing'),
                 iconCls: 'opendxp_main_nav_icon_marketing',
                 items: marketingItems,
                 shadow: false,
                 cls: "opendxp_navigation_flyout"
             };
         }

         if (perspectiveCfg.inToolbar("settings")) {
             // settings menu
             var settingsItems = [];

             if (user.isAllowed("document_types") && perspectiveCfg.inToolbar("settings.documentTypes")) {
                 settingsItems.push({
                     text: t("document_types"),
                     iconCls: "opendxp_nav_icon_doctypes",
                     itemId: 'opendxp_menu_settings_document_types',
                     handler: this.editDocumentTypes
                 });
             }
             if (user.isAllowed("predefined_properties") && perspectiveCfg.inToolbar("settings.predefinedProperties")) {
                 settingsItems.push({
                     text: t("predefined_properties"),
                     iconCls: "opendxp_nav_icon_properties",
                     itemId: 'opendxp_menu_settings_predefined_properties',
                     handler: this.editProperties
                 });
             }

             if (user.isAllowed("predefined_properties") && perspectiveCfg.inToolbar("settings.predefinedMetadata")) {
                 settingsItems.push({
                     text: t("predefined_asset_metadata"),
                     iconCls: "opendxp_nav_icon_metadata",
                     itemId: 'opendxp_menu_settings_predefined_asset_metadata',
                     handler: this.editPredefinedMetadata
                 });
             }

             if (user.isAllowed("system_settings") && perspectiveCfg.inToolbar("settings.system")) {
                 settingsItems.push({
                     text: t("system_settings"),
                     iconCls: "opendxp_nav_icon_system_settings",
                     itemId: 'opendxp_menu_settings_system_settings',
                     handler: this.systemSettings
                 });
             }

             if (user.isAllowed("system_appearance_settings") && perspectiveCfg.inToolbar("settings.appearance")) {
                 settingsItems.push({
                     text: t("appearance_and_branding"),
                     iconCls: "opendxp_nav_icon_frame",
                     itemId: 'opendxp_menu_settings_system_appearance',
                     handler: this.systemAppearanceSettings
                 });
             }

             if (user.isAllowed("website_settings") && perspectiveCfg.inToolbar("settings.website")) {
                 settingsItems.push({
                     text: t("website_settings"),
                     iconCls: "opendxp_nav_icon_website_settings",
                     itemId: 'opendxp_menu_settings_website_settings',
                     handler: this.websiteSettings
                 });
             }

             if (user.isAllowed("users") && perspectiveCfg.inToolbar("settings.users")) {
                 var userItems = [];

                 if (perspectiveCfg.inToolbar("settings.users.users")) {
                     userItems.push(
                         {
                             text: t("users"),
                             handler: this.editUsers,
                             iconCls: "opendxp_nav_icon_users",
                             itemId: 'opendxp_menu_settings_users_users',
                         }
                     );
                 }

                 if (perspectiveCfg.inToolbar("settings.users.roles")) {
                     userItems.push(
                         {
                             text: t("roles"),
                             handler: this.editRoles,
                             iconCls: "opendxp_nav_icon_roles",
                             itemId: 'opendxp_menu_settings_users_roles',
                         }
                     );
                 }

                 if (user.isAllowed("users")) {
                     userItems.push(
                         {
                             text: t("analyze_permissions"),
                             handler: function() {
                                 var checker = new opendxp.element.permissionchecker();
                                 checker.show();
                             }.bind(this),
                             iconCls: "opendxp_nav_icon_analyze_permissions",
                             itemId: 'opendxp_menu_settings_users_analyse_permissions',
                         }
                     );
                 }

                 if (userItems.length > 0) {
                     settingsItems.push({
                         text: t("users") + " / " + t("roles"),
                         iconCls: "opendxp_nav_icon_users",
                         itemId: 'opendxp_menu_settings_users',
                         hideOnClick: false,
                         menu: {
                             cls: "opendxp_navigation_flyout",
                             shadow: false,
                             items: userItems
                         }
                     });
                 }
             }

             if (user.isAllowed("thumbnails") && perspectiveCfg.inToolbar("settings.thumbnails")) {
                 settingsItems.push({
                     text: t("thumbnails"),
                     iconCls: "opendxp_nav_icon_thumbnails",
                     itemId: 'opendxp_menu_settings_thumbnails',
                     hideOnClick: false,
                     menu: {
                         cls: "opendxp_navigation_flyout",
                         shadow: false,
                         items: [{
                             text: t("image_thumbnails"),
                             iconCls: "opendxp_nav_icon_thumbnails",
                             itemId: 'opendxp_menu_settings_thumbnails_image',
                             handler: this.editThumbnails
                         }, {
                             text: t("video_thumbnails"),
                             iconCls: "opendxp_nav_icon_videothumbnails",
                             itemId: 'opendxp_menu_settings_thumbnails_video',
                             handler: this.editVideoThumbnails
                         }]
                     }
                 });
             }

             if (user.isAllowed("objects") && perspectiveCfg.inToolbar("settings.objects")) {

                 var objectMenu = {
                     text: t("data_objects"),
                     iconCls: "opendxp_nav_icon_object",
                     itemId: 'opendxp_menu_settings_data_objects',
                     hideOnClick: false,
                     menu: {
                         cls: "opendxp_navigation_flyout",
                         shadow: false,
                         items: []
                     }
                 };

                 if (perspectiveCfg.inToolbar("settings.objects.classes") && user.isAllowed("classes")) {
                     objectMenu.menu.items.push({
                         text: t("classes"),
                         iconCls: "opendxp_nav_icon_class",
                         itemId: 'opendxp_menu_settings_data_objects_classes',
                         handler: this.editClasses
                     });
                 }

                 if (perspectiveCfg.inToolbar("settings.objects.fieldcollections") && user.isAllowed("fieldcollections")) {
                     objectMenu.menu.items.push({
                         text: t("field_collections"),
                         iconCls: "opendxp_nav_icon_fieldcollection",
                         itemId: 'opendxp_menu_settings_data_objects_fieldcollections',
                         handler: this.editFieldcollections
                     });
                 }

                 if (perspectiveCfg.inToolbar("settings.objects.objectbricks") && user.isAllowed("objectbricks")) {
                     objectMenu.menu.items.push({
                         text: t("objectbricks"),
                         iconCls: "opendxp_nav_icon_objectbricks",
                         itemId: 'opendxp_menu_settings_data_objects_objectbricks',
                         handler: this.editObjectBricks
                     });
                 }

                 if (perspectiveCfg.inToolbar('settings.objects.selectoptions') && user.isAllowed('selectoptions')) {
                     objectMenu.menu.items.push({
                         text: t('selectoptions'),
                         iconCls: 'opendxp_nav_icon_selectoptions',
                         itemId: 'opendxp_menu_settings_data_objects_selectoptions',
                         handler: this.editSelectOptions
                     });
                 }

                 if (perspectiveCfg.inToolbar("settings.objects.quantityValue") && user.isAllowed("quantityValueUnits")) {
                     objectMenu.menu.items.push({
                         text: t("quantityValue_field"),
                         iconCls: "opendxp_nav_icon_quantityValue",
                         itemId: 'opendxp_menu_settings_data_objects_quantity_value',
                         cls: "opendxp_main_menu",
                         handler: function () {
                             try {
                                 opendxp.globalmanager.get("quantityValue_units").activate();
                             }
                             catch (e) {
                                 opendxp.globalmanager.add("quantityValue_units", new opendxp.object.quantityValue.unitsettings());
                             }
                         }
                     });
                 }

                 if (perspectiveCfg.inToolbar("settings.objects.classificationstore") && user.isAllowed("classificationstore")) {
                     objectMenu.menu.items.push({
                         text: t("classification_store"),
                         iconCls: "opendxp_nav_icon_classificationstore",
                         itemId: 'opendxp_menu_settings_data_objects_classificationstore',
                         handler: this.editClassificationStoreConfig
                     });
                 }

                 if (perspectiveCfg.inToolbar("settings.objects.bulkExport") && user.isAllowed("classes")) {
                     objectMenu.menu.items.push({
                         text: t("bulk_export"),
                         iconCls: "opendxp_nav_icon_export",
                         itemId: 'opendxp_menu_settings_data_objects_bulk_export',
                         handler: this.bulkExport
                     });
                 }

                 if (perspectiveCfg.inToolbar("settings.objects.bulkImport") && user.isAllowed("classes")) {
                     objectMenu.menu.items.push({
                         text: t("bulk_import"),
                         iconCls: "opendxp_nav_icon_import",
                         itemId: 'opendxp_menu_settings_data_objects_bulk_import',
                         handler: this.bulkImport.bind(this)
                     });
                 }


                 if (objectMenu.menu.items.length > 0) {
                     settingsItems.push(objectMenu);
                 }
             }

             if (perspectiveCfg.inToolbar("settings.cache") && (user.isAllowed("clear_cache") || user.isAllowed("clear_temp_files") || user.isAllowed("clear_fullpage_cache"))) {

                 var cacheItems = [];
                 var cacheSubItems = [];

                 if (user.isAllowed("clear_cache")) {

                     if (perspectiveCfg.inToolbar("settings.cache.clearAll")) {
                         cacheSubItems.push({
                             text: t("all_caches") + ' (Symfony + Data)',
                             iconCls: "opendxp_nav_icon_clear_cache",
                             itemId: 'opendxp_menu_settings_cache_all_caches',
                             handler: this.clearCache.bind(this, {env: opendxp.settings['environment']})
                         });
                     }

                     if (perspectiveCfg.inToolbar("settings.cache.clearData")) {
                         cacheSubItems.push({
                             text: t("data_cache"),
                             iconCls: "opendxp_nav_icon_clear_cache",
                             itemId: 'opendxp_menu_settings_cache_data_cache',
                             handler: this.clearCache.bind(this, {'only_opendxp_cache': true})
                         });
                     }

                     if (perspectiveCfg.inToolbar("settings.cache.clearSymfony")) {
                         cacheSubItems.push({
                             text: t('symfony_cache'),
                             iconCls: "opendxp_nav_icon_clear_cache",
                             itemId: 'opendxp_menu_settings_cache_symfony',
                             handler: this.clearCache.bind(this, {only_symfony_cache: true, env: opendxp.settings['environment']})
                         });
                     }

                     cacheItems.push({
                         text: t("clear_cache"),
                         iconCls: "opendxp_nav_icon_clear_cache",
                         itemId: 'opendxp_menu_settings_cache_clear_cache',
                         hideOnClick: false,
                         menu: {
                             cls: "opendxp_navigation_flyout",
                             shadow: false,
                             items: cacheSubItems
                         }
                     });
                 }

                 if (perspectiveCfg.inToolbar("settings.cache.clearOutput")) {
                     if (user.isAllowed("clear_fullpage_cache")) {
                         cacheItems.push({
                             text: t("clear_full_page_cache"),
                             iconCls: "opendxp_nav_icon_clear_cache",
                             itemId: 'opendxp_menu_settings_cache_clear_full_page_cache',
                             handler: this.clearOutputCache
                         });
                     }
                 }

                 if (perspectiveCfg.inToolbar("settings.cache.clearTemp")) {
                     if (user.isAllowed("clear_temp_files")) {
                         cacheItems.push({
                             text: t("clear_temporary_files"),
                             iconCls: "opendxp_nav_icon_clear_cache",
                             itemId: 'opendxp_menu_settings_cache_clear_temporary_files',
                             handler: this.clearTemporaryFiles
                         });
                     }
                 }

                 if (perspectiveCfg.inToolbar("settings.cache.generatePreviews")) {
                     if (opendxp.settings.document_generatepreviews && (opendxp.settings.chromium || opendxp.settings.gotenberg)) {
                         cacheItems.push({
                             text: t("generate_page_previews"),
                             iconCls: "opendxp_nav_icon_page_previews",
                             itemId: 'opendxp_menu_settings_cache_generate_page_previews',
                             handler: this.generatePagePreviews
                         });
                     }
                 }


                 if (cacheItems.length > 0) {
                     var cacheMenu = {
                         text: t("cache"),
                         iconCls: "opendxp_nav_icon_clear_cache",
                         itemId: 'opendxp_menu_settings_cache',
                         hideOnClick: false,
                         menu: {
                             cls: "opendxp_navigation_flyout",
                             shadow: false,
                             items: cacheItems
                         }
                     };

                     settingsItems.push(cacheMenu);
                 }
             }

             // admin translations only for admins
             if (user.isAllowed('admin_translations')) {
                 if (perspectiveCfg.inToolbar("settings.adminTranslations")) {
                     settingsItems.push({
                         text: t("admin_translations"),
                         iconCls: "opendxp_nav_icon_translations",
                         itemId: 'opendxp_menu_settings_admin_translations',
                         handler: this.editTranslations.bind(this, 'admin')
                     });
                 }
             }

             // tags for elements
             if (user.isAllowed("tags_configuration") && perspectiveCfg.inToolbar("settings.tagConfiguration")) {
                 settingsItems.push({
                     text: t("element_tag_configuration"),
                     iconCls: "opendxp_nav_icon_element_tags",
                     itemId: 'opendxp_menu_settings_element_tag_configuration',
                     handler: this.showTagConfiguration
                 });
             }

             if (user.admin) {
                 settingsItems.push({
                     iconCls: "opendxp_nav_icon_icons",
                     itemId: 'opendxp_menu_settings_icon_library',
                     text: t('icon_library'),
                     handler: this.showIconLibrary.bind(this)
                 });
             }

             // help menu
            menu.settings = {
                label: t('settings'),
                iconCls: 'opendxp_main_nav_icon_settings',
                items: settingsItems,
                shadow: false,
                cls: "opendxp_navigation_flyout"
            };
         }

         // profile
         let profileItems = [
            {
                text: t("my_profile"),
                iconCls: 'opendxp_icon_profile',
                handler: () => {
                    opendxp.helpers.openProfile();
                }
            },

            {
                text: t('logout'),
                iconCls: 'opendxp_material_icon_logout',
                handler: () => {
                    document.getElementById('opendxp_logout_form').submit();
                }
            }
         ]

         // notifications
         if (user.isAllowed("notifications")) {
             var notificationItems = [{
                 text: t("notifications"),
                 iconCls: "opendxp_nav_icon_notifications",
                 itemId: 'opendxp_menu_notifications_notifications',
                 handler: this.showNotificationTab.bind(this)
             }];

             if(user.isAllowed('notifications_send')) {
                 notificationItems.push({
                     text: t("notifications_send"),
                     iconCls: "opendxp_nav_icon_notifications_sent",
                     itemId: 'opendxp_menu_notifications_notifications_send',
                     id: "notifications_new",
                     handler: this.showNotificationModal.bind(this)
                 });
             }

             notificationItems.push('-');

             // check for devmode
             if (opendxp.settings.devmode) {
                 notificationItems.push({
                     text: t("DEV MODE"),
                     iconCls: "opendxp_nav_icon_dev_mode",
                     itemId: 'opendxp_menu_notifications_dev_mode',
                 });
                 opendxp.notification.helper.incrementCount();
             }

             // check for debug
             if (opendxp.settings.debug) {
                 notificationItems.push({
                     text: t("debug_mode_on"),
                     iconCls: "opendxp_nav_icon_debug_mode",
                     itemId: 'opendxp_menu_notifications_debug_mode',
                 });
                 opendxp.notification.helper.incrementCount();
             }

             // check for maintenance
             if (!opendxp.settings.maintenance_active) {
                 notificationItems.push({
                     text: t("maintenance_not_active"),
                     iconCls: "opendxp_nav_icon_maintenance",
                     itemId: 'opendxp_menu_notifications_maintenance',
                     handler: function () {
                         window.open('https://docs.opendxp.io/docs/core-framework/Getting_Started/Installation/Webserver_Installation#5-maintenance-cron-job');
                     }
                 });

                 opendxp.notification.helper.incrementCount();
             }

             //check for mail settings
             if (!opendxp.settings.mail) {
                 notificationItems.push({
                     text: t("mail_settings_incomplete"),
                     iconCls: "opendxp_nav_icon_email",
                     itemId: 'opendxp_menu_notifications_email',
                     handler: function () {
                         window.open('https://docs.opendxp.io/docs/core-framework/Development_Tools_and_Details/Email_Framework');
                     }
                 });

                 opendxp.notification.helper.incrementCount();
             }

             profileItems = [
                ...notificationItems,
                '-',
                ...profileItems,
             ]
         }

         menu.notification = {
            items: profileItems,
            shadow: false,
            cls: "opendxp_navigation_flyout",
            exclude: true,
        };

         // Additional menu items can be added via this event
         const preMenuBuild = new CustomEvent(opendxp.events.preMenuBuild, {
             detail: {
                 menu: menu,
             }
         });

         document.dispatchEvent(preMenuBuild);

         // building the html markup for the main navigation
         opendxp.helpers.buildMainNavigationMarkup(menu);

         if(Object.keys(menu).length !== 0) {
             Object.keys(menu).filter(key => {
                 return (menu[key].items && menu[key].items.length > 0) || menu[key]['noSubmenus'];
             }).forEach(key => {
                 // Building all submenus
                 // menu[key].items can be empty
                 // menu items can be added via event after the inital setup
                 // if items are empty do not build submenus or main menu item
                 if(!menu[key]['noSubmenus']) {
                     opendxp.helpers.buildMenu(menu[key].items);
                 }

                 let menuItem = {
                     shadow: menu[key].shadow,
                     cls: "opendxp_navigation_flyout",
                 }

                 if(menu[key].listeners) {
                     menuItem.listeners = menu[key].listeners
                 }

                 if(menu[key].items) {
                     menuItem.items = menu[key].items;

                     if(!menu[key]['exclude'] && !menu[key]['noSubmenus']) {
                         menuItem.listeners =
                             {
                                 ...menuItem.listeners, ...{
                                     "show": function (e) {
                                         Ext.get('opendxp_menu_' + key).addCls('active');
                                     },
                                     "hide": function (e) {
                                         Ext.get('opendxp_menu_' + key).removeCls('active');
                                     }
                                 }
                             }
                     }
                 }

                 // Adding single main menu item
                 let menuKey = key + 'Menu';
                 this[menuKey] = Ext.create('opendxp.menu.menu', menuItem);

                 // if the main menu has its own handler use it
                 if(menu[key]['handler']) {
                     Ext.get("opendxp_menu_" + key).on("mousedown", menu[key]['handler']);
                 }

                 // only add the default show sub menu if there are items
                 if(menu[key]['items'] && !menu[key]['exclude'] && !menu[key]['handler']) {
                     // make sure the elements are clickable
                     Ext.get("opendxp_menu_" + key).on("mousedown", this.showSubMenu.bind(this[menuKey]));
                 }
             });
         }

         if (opendxp.settings.notifications_enabled && this.notificationMenu) {
             Ext.get('opendxp_notification').show();
             Ext.get("opendxp_notification").on("mousedown", this.showSubMenu.bind(this.notificationMenu));
             opendxp.notification.helper.updateFromServer();
         }

         Ext.each(Ext.query(".opendxp_menu_item"), function (el) {
             el = Ext.get(el);

             if (el) {
                 var menuVariable = el.id.replace(/opendxp_menu_/, "") + "Menu";
                 if (el.hasCls("opendxp_menu_needs_children")) {
                     if (!this[menuVariable]) {
                         el.setStyle("display", "none");
                     }
                 }

                 el.on("mouseenter", function () {
                     if (Ext.menu.MenuMgr.hideAll()) {
                         var offsets = el.getOffsetsTo(Ext.getBody());
                         offsets[0] = 60;
                         var menu = this[menuVariable];
                         if (menu) {
                             menu.showAt(offsets);
                         }
                     }
                 }.bind(this));
             } else {
                 console.error("no opendxp_menu_item");
             }
         }.bind(this));

         // Full menu can be checked here
         const postMenuBuild = new CustomEvent(opendxp.events.postMenuBuild, {
             detail: {
                 menu: menu,
             }
         });

         document.dispatchEvent(postMenuBuild);

         return;
     },

     showSubMenu: function (e) {
         if(this.hidden) {
             e.stopEvent();
             var el = Ext.get(e.currentTarget);
             var offsets = el.getOffsetsTo(Ext.getBody());
             offsets[0] = 60;
             this.showAt(offsets);
         } else {
             this.hide();
         }
     },

     closeAllTabs: function () {
         opendxp.helpers.closeAllTabs();
     },

     editDocumentTypes: function () {

         try {
             opendxp.globalmanager.get("document_types").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("document_types", new opendxp.settings.document.doctypes());
         }
     },

     editProperties: function () {

         try {
             opendxp.globalmanager.get("predefined_properties").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("predefined_properties", new opendxp.settings.properties.predefined());
         }
     },


     editPredefinedMetadata: function () {

         try {
             opendxp.globalmanager.get("predefined_metadata").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("predefined_metadata", new opendxp.settings.metadata.predefined());
         }
     },

     recyclebin: function () {
         try {
             opendxp.globalmanager.get("recyclebin").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("recyclebin", new opendxp.settings.recyclebin());
         }
     },

     editUsers: function () {
         opendxp.helpers.showUser();
     },

     editRoles: function () {

         try {
             opendxp.globalmanager.get("roles").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("roles", new opendxp.settings.user.role.panel());
         }
     },

     editThumbnails: function () {
         try {
             opendxp.globalmanager.get("thumbnails").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("thumbnails", new opendxp.settings.thumbnail.panel());
         }
     },

     editVideoThumbnails: function () {
         try {
             opendxp.globalmanager.get("videothumbnails").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("videothumbnails", new opendxp.settings.videothumbnail.panel());
         }
     },

     editTranslations: function (domain) {
         const preEditTranslations = new CustomEvent(opendxp.events.preEditTranslations, {
             detail: {
                 translation: this,
                 domain: domain ?? "website"
             },
             cancelable: true
         });

         const isAllowed = document.dispatchEvent(preEditTranslations);
         if (!isAllowed){
             return;
         }

         try {
             opendxp.globalmanager.get("translationdomainmanager").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("translationdomainmanager", new opendxp.settings.translation.domain(domain));
         }
     },

     openPerspective: function(name) {
         location.href = Routing.generate('opendxp_admin_index', {perspective: name});
     },

     generatePagePreviews: function ()  {
         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_document_page_generatepreviews'),
             success: function (res) {
                 var data = Ext.decode(res.responseText);
                 if(data && data.success) {
                     opendxp.helpers.showNotification(t("success"), t("success_generating_previews"), "success");
                 }
             },
             failure: function (message) {
                 opendxp.helpers.showNotification(t("error"), t("error_generating_previews"), "error", t(message));
             }
         });
     },

     sendTestEmail: function () {
         opendxp.helpers.sendTestEmail(opendxp.settings.mailDefaultAddress);
     },

     notes: function () {
         try {
             opendxp.globalmanager.get("notes").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("notes", new opendxp.element.notes());
         }
     },

     systemSettings: function () {

         try {
             opendxp.globalmanager.get("settings_system").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("settings_system", new opendxp.settings.system());
         }
     },

     systemAppearanceSettings: function () {
         try {
             opendxp.globalmanager.get("settings_system_appearance").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("settings_system_appearance", new opendxp.settings.appearance());
         }
     },

     websiteSettings: function () {

         try {
             opendxp.globalmanager.get("settings_website").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("settings_website", new opendxp.settings.website());
         }
     },
     editClassificationStoreConfig: function () {
         try {
             opendxp.globalmanager.get("classificationstore_config").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("classificationstore_config", new opendxp.object.classificationstore.storeTree());
         }
     },

     editClasses: function () {
         try {
             opendxp.globalmanager.get("classes").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("classes", new opendxp.object.klass());
         }
     },

     editFieldcollections: function () {
         try {
             opendxp.globalmanager.get("fieldcollections").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("fieldcollections", new opendxp.object.fieldcollection());
         }
     },

     editObjectBricks: function () {
         try {
             opendxp.globalmanager.get("objectbricks").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("objectbricks", new opendxp.object.objectbrick());
         }
     },

    editSelectOptions: function () {
        try {
            opendxp.globalmanager.get('selectoptions').activate();
        } catch (e) {
            opendxp.globalmanager.add('selectoptions', new opendxp.object.selectoptions());
        }
    },

     clearCache: function (params) {
         Ext.Msg.confirm(t('warning'), t('system_performance_stability_warning'), function(btn){
             if (btn === 'yes'){
                 Ext.Ajax.request({
                     url: Routing.generate('opendxp_admin_settings_clearcache'),
                     method: 'DELETE',
                     params: params
                 });
             }
         });
     },

     clearOutputCache: function () {
         Ext.Ajax.request({
             url: Routing.generate('opendxp_admin_settings_clearoutputcache'),
             method: 'DELETE'
         });
     },

     clearTemporaryFiles: function () {
         Ext.Msg.confirm(t('warning'), t('system_performance_stability_warning'), function(btn){
             if (btn === 'yes'){
                 Ext.Ajax.request({
                     url: Routing.generate('opendxp_admin_settings_cleartemporaryfiles'),
                     method: 'DELETE'
                 });
             }
         });
     },

     showMaintenance: function () {
         new opendxp.settings.maintenance();
     },

    showSystemRequirementsCheck: function () {
        opendxp.helpers.openGenericIframeWindow("systemrequirementscheck", Routing.generate('opendxp_admin_install_check'), "opendxp_icon_systemrequirements", "System-Requirements Check");
    },

     showElementHistory: function() {
         try {
             opendxp.globalmanager.get("element_history").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("element_history", new opendxp.element.history());
         }
     },

     sentEmailsLog: function () {
         try {
             opendxp.globalmanager.get("sent_emails").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("sent_emails", new opendxp.settings.email.log());
         }
     },

     emailBlocklist: function () {
         try {
             opendxp.globalmanager.get("email_blocklist").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("email_blocklist", new opendxp.settings.email.blocklist());
         }
     },

     showTagConfiguration: function() {
         try {
             opendxp.globalmanager.get("element_tag_configuration").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("element_tag_configuration", new opendxp.element.tag.configuration());
         }
     },


     bulkImport: function() {

         Ext.Msg.confirm(t('warning'), t('warning_bulk_import'), function(btn){
             if (btn == 'yes'){
                 this.doBulkImport();
             }
         }.bind(this));
     },


     doBulkImport: function() {
         var importer = new opendxp.object.bulkimport;
         importer.upload();
     },

     bulkExport: function() {
         var exporter = new opendxp.object.bulkexport();
         exporter.export();
     },

     showNotificationTab: function () {
         try {
             opendxp.globalmanager.get("notifications").activate();
         }
         catch (e) {
             opendxp.globalmanager.add("notifications", new opendxp.notification.panel());
         }
     },

     showNotificationModal: function () {
         if (opendxp.globalmanager.get("new_notifications")) {
             opendxp.globalmanager.get("new_notifications").getWindow().destroy();
         }

         opendxp.globalmanager.add("new_notifications", new opendxp.notification.modal());
     },

    showIconLibrary: function () {
        try {
            opendxp.globalmanager.get("iconlibrary").activate();
        }
        catch (e) {
            opendxp.globalmanager.add("iconlibrary", new opendxp.iconlibrary.panel());
            opendxp.globalmanager.get("iconlibrary").activate();
        }
    }
 });


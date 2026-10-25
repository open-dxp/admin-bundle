# Changelog

## 1.5.0
- [BUGFIX] Notes on documents and on objects offer the note types configured for their own element type [#152](https://github.com/open-dxp/admin-bundle/pull/152)
- [SECURITY] The object and asset grids open a workspace only for its own path and the paths below it. A workspace on `/shop_de` does not open `/shopXde`, and a quote in the name of a workspace does not break the query [#151](https://github.com/open-dxp/admin-bundle/pull/151)
- [BUGFIX] The object and asset grids hide a closed folder also when another closed folder holds an open one [#151](https://github.com/open-dxp/admin-bundle/pull/151)
- [ENHANCEMENT] Login links use the host of the request whenever OpenDXP trusts it, also a host from `opendxp.security.trusted_hosts`. See [Domain and Host Handling](https://github.com/open-dxp/opendxp/blob/1.x/doc/02_MVC/04_Routing_and_URLs/06_Domain_and_Host_Handling.md#trusted-hosts)
- [NEW FEATURE] A data type shows its value in several grid columns with `GridColumnsInterface`. See [Adding Object Datatypes](https://github.com/open-dxp/opendxp/blob/1.x/doc/20_Extending_OpenDxp/13_Bundle_Developers_Guide/11_Adding_Object_Datatypes.md#grid-columns)
- [BUGFIX] A grid filters by a classification store key and a slug at the same time
- [BUGFIX] A grid filters a number of a classification store key between a lower and an upper bound
- [NEW FEATURE] [HIGHLIGHT] Every grid of the admin exports through one export. See [Grid Export](docs/10_Extension_Points/08_Grid_Export.md)
  > Every grid in the admin uses the same export.
- [NEW FEATURE] `GridExports::export()` runs a grid export in a test
- [NEW FEATURE] A grid export runs in the background with `opendxp_admin.background_jobs`. See [Grid Export](docs/10_Extension_Points/08_Grid_Export.md#run-exports-in-the-background)
- [BUGFIX] Progress windows stay in the bottom right corner when the browser window is resized
- [BUGFIX] A data object closed right after opening logs no error in the console
- [DEPRECATION] The grid view `patchedgridview` is deprecated. A grid uses the default view of Ext JS.
- [DEPRECATION] The navigation model `quicksearch.boundlist` is deprecated. A bound list uses the default navigation model of Ext JS.
- [CHORE] Requests from the admin no longer send the headers `X-opendxp-extjs-version-major` and `X-opendxp-extjs-version-minor`
- [NEW FEATURE] [HIGHLIGHT] A large tree folder has a bar to search its children by any part of the key and to page through children and search results [open-dxp/opendxp#228](https://github.com/open-dxp/opendxp/issues/228)
  > Large folders in the tree get a search bar. Find children by any part of their key, and page through the children and the search results.
- [CHORE] Require `openspout/openspout` ^5.0. `phpoffice/phpspreadsheet` goes with 2.0
- [CHORE] Remove the old export routes of the object, asset, email log and translation grids
- [NEW FEATURE] The URL slug field shows the prefix of its slug generator, formats what an editor types and locks a stored slug. The domain of a site slug stands next to it [open-dxp/opendxp#159](https://github.com/open-dxp/opendxp/issues/159)
- [NEW FEATURE] [HIGHLIGHT] Download the images of a folder or a selection as a ZIP of thumbnails. "Download as ZIP" in the asset tree, the folder toolbar and the list view offers "Originals" and "Thumbnails", which asks for one of the thumbnails offered for download [#128](https://github.com/open-dxp/admin-bundle/issues/128)
  > Download the images of a folder or a selection as a ZIP, either as originals or as thumbnails.
- [BUGFIX] The thumbnail download of the image detail view hands out only thumbnails that are offered for download [#128](https://github.com/open-dxp/admin-bundle/issues/128)
- [CHORE] Replace Codeception with Pest and `open-dxp/test-foundation`
- [CHORE] Require `open-dxp/opendxp` ^1.5
- [CHORE] Stop registering the payload and result classes in `src/Handler` as services. They are data objects, and a service definition for them could not be built.
- [ENHANCEMENT] The object grid counts its search as an active filter, and "Clear filters" clears the search as well [#129](https://github.com/open-dxp/admin-bundle/issues/129)
- [ENHANCEMENT] Hide the AnyGetter grid operator from users who are not admins [#28](https://github.com/open-dxp/admin-bundle/issues/28)
- [BUGFIX] Opening a deeplink works in a long running process, for example FrankenPHP in worker mode.
- [BUGFIX] In a long running process, every session keeps its own CSRF token.
- [DEPRECATION] Printing the Twig global `csrfToken` outside the admin is deprecated. It throws an exception in 2.0.
- [DEPRECATION] Building the AnyGetter grid operator without a `user` in its context is deprecated. It throws an exception in 2.0.
- [BUGFIX] A classification store key takes only a name that is valid for a data object field [#126](https://github.com/open-dxp/admin-bundle/pull/126)
- [BUGFIX] The grids of the classification store configuration show why a change could not be saved and take the change back [#126](https://github.com/open-dxp/admin-bundle/pull/126)
- [BUGFIX] A page that inherits its content from a content main document keeps that content when it is saved [#135](https://github.com/open-dxp/admin-bundle/pull/135)

## 1.4.3
- [BUGFIX] Object grid now applies the requested grid language before the listing is loaded and before inline edits are saved (regression from #91) [#122](https://github.com/open-dxp/admin-bundle/issues/122)
- [BUGFIX] Object grid batch editing now saves localized fields in the requested grid language instead of the admin UI language. Localized classification store keys edited with grid language "default" are stored under "default" again (regression from #91). Batch append and remove on classification store keys now read the existing value in the requested language [#122](https://github.com/open-dxp/admin-bundle/issues/122)

## 1.4.2
- [ENHANCEMENT] Comply with OpenStreetMap tile and Nominatim usage policies [#118](https://github.com/open-dxp/admin-bundle/pull/118)
- [ENHANCEMENT] One-time login links (lost password, user invitation, token login) now use the requested host if it belongs to a registered site, instead of always the configured main domain. Unknown hosts fall back to the general host resolver, so the `Host` header cannot be used to poison the links. [#119](https://github.com/open-dxp/admin-bundle/pull/119)
- [ENHANCEMENT] Hide error box on login UI if error is empty or not set [#120](https://github.com/open-dxp/admin-bundle/pull/120)

## 1.4.1
- [ENHANCEMENT] Refine grid logic with strict comparisons and column auto-sizing adjustments [#112](https://github.com/open-dxp/admin-bundle/pull/112)
- [BUGFIX] Prevent passing `rid` on scratch user creation [#109](https://github.com/open-dxp/admin-bundle/pull/109)
- [BUGFIX] Clean up leftover rows of the previous document type during type conversion [#113](https://github.com/open-dxp/admin-bundle/pull/113)
- [BUGFIX] Use the document tree instead of pretty URLs for the move collision check, so a document is no longer blocked by its own pretty URL [#114](https://github.com/open-dxp/admin-bundle/pull/114)
- [BUGFIX] Tolerate empty / non-integer `oid` query parameter in `getClassDefinitionForColumnConfig` (regression from #91) [#115](https://github.com/open-dxp/admin-bundle/pull/115)

## 1.4.0
- [NEW FEATURE] Replace controller logic with dedicated handler and service classes (CQRS-Lite) [#91](https://github.com/open-dxp/admin-bundle/pull/91)
- [NEW FEATURE] Add "use original recipients" option, fix resend and forward recipients [#102](https://github.com/open-dxp/admin-bundle/pull/102)
- [NEW FEATURE] Add CSV export for email logs [#104](https://github.com/open-dxp/admin-bundle/pull/104)
- [NEW FEATURE] Add email log cleanup to the global email log panel [#105](https://github.com/open-dxp/admin-bundle/pull/105)
- [ENHANCEMENT] Introduce system_uuid & centralize system identity handling [#106](https://github.com/open-dxp/admin-bundle/pull/106)
- [BUGFIX] Notification undefined elmentId [#100](https://github.com/open-dxp/admin-bundle/pull/100)
- [BUGFIX] Handle root path in `optimizedConcatNotLike` [#101](https://github.com/open-dxp/admin-bundle/pull/101)
- [BUGFIX] Fix typo in target group editable selection [#103](https://github.com/open-dxp/admin-bundle/pull/103)

## 1.3.5
- [BUGFIX] Fix missing OpenDXP CSS vars [#99](https://github.com/open-dxp/admin-bundle/pull/99)
- [BUGFIX] Add guards for read/get operations in controllers and assets [#94](https://github.com/open-dxp/admin-bundle/pull/94)

## 1.3.4
- [CHORE] Remove unused grid configuration processing logic from DataObjectController [#89](https://github.com/open-dxp/admin-bundle/pull/89)
- [BUGFIX] Resolve persistent thumbnail spinners in asset folder preview [#90](https://github.com/open-dxp/admin-bundle/pull/90)
- [BUGFIX] Standardize Content-Disposition header formatting across controllers [#92](https://github.com/open-dxp/admin-bundle/pull/92)
- [ENHANCEMENT] Add stricter unserialize options; refactor variable naming in TranslationController [#88](https://github.com/open-dxp/admin-bundle/pull/88)
- [ENHANCEMENT] Added css color variables prefix [@cbl6499](https://github.com/open-dxp/admin-bundle/pull/87)

## 1.3.3
- [ENHANCEMENT] Add forceResize checkbox to video thumbnail settings [#82](https://github.com/open-dxp/admin-bundle/pull/82)
- [BUGFIX] Executing workflow transitions [#80](https://github.com/open-dxp/admin-bundle/pull/80)
- [BUGFIX] Array thumbnail parameter in getImageThumbnailAction [#78](https://github.com/open-dxp/admin-bundle/pull/78)
- [BUGFIX] Remove duplicate AdminExtension service definition from services.yaml [#84](https://github.com/open-dxp/admin-bundle/pull/84)

## 1.3.2
- [BUGFIX] Remove redundant translation cache clearing from TranslationController [#67](https://github.com/open-dxp/admin-bundle/pull/67)
- [BUGFIX] Ue new documentation URLs in toolbar links [#70](https://github.com/open-dxp/admin-bundle/pull/70)
- [BUGFIX] Fix invalid infinite loop detection when linking to another type of element in document link [#74](https://github.com/open-dxp/admin-bundle/pull/74)
- [ENHANCEMENT] PHP 8.5 support added [#76](https://github.com/open-dxp/admin-bundle/pull/76)
- [ENHANCEMENT] Remove POEditor support and and normalize translation files [#75](https://github.com/open-dxp/admin-bundle/pull/75)
- [ENHANCEMENT] Update admin UI styles, adjust notification and avatar elements for improved alignment [#72](https://github.com/open-dxp/admin-bundle/pull/72)

## 1.3.1
- [BUGFIX] Add guard clause to prevent rendering site settings without a valid site

## 1.3.0
- [NEW FEATURE] Site custom settings [#61](https://github.com/open-dxp/admin-bundle/pull/61)
- [ENHANCEMENT] Replace SystemSettingsConfig usage with GeneralHostResolver in LoginController and UserController [#62](https://github.com/open-dxp/admin-bundle/pull/62)

## 1.2.3
- [BUGFIX] Translations: Fix data request - streamline filtering and improve readability [#50](https://github.com/open-dxp/admin-bundle/pull/50)
- [BUGFIX] Correct languages when creating admin translation (#53) [#54](https://github.com/open-dxp/admin-bundle/pull/54)
- [ENHANCEMENT] Refactor SQL queries: enforce parameterized bindings and consistent style [#49](https://github.com/open-dxp/admin-bundle/pull/49)
- [CHORE] Strict equality checks across admin bundle [#55](https://github.com/open-dxp/admin-bundle/pull/55)
- [CHORE] Update constant declarations to use type hints and visibility modifiers [#56](https://github.com/open-dxp/admin-bundle/pull/56)

## 1.2.2
- [CHORE] Update branding names [#45](https://github.com/open-dxp/admin-bundle/pull/45)
- [CHORE] Remove outdated references to GitHub issues and comments. [#46](https://github.com/open-dxp/admin-bundle/pull/46)
- [BUGFIX] Fixed saving email documents (#47) [#48](https://github.com/open-dxp/admin-bundle/pull/48)

## 1.2.1
- [CHORE] Update Rector configuration: add ForeachItemsAssignToEmptyArrayToAssignRector and update comments [#37](https://github.com/open-dxp/admin-bundle/pull/37)
- [CHORE] Apply changes from upstream [#40](https://github.com/open-dxp/admin-bundle/pull/40)
- [ENHANCEMENT] Various Ext JS Improvements [#38](https://github.com/open-dxp/admin-bundle/pull/38)
- [CHORE] Replace `$request->get()` [#39](https://github.com/open-dxp/admin-bundle/pull/39)
- [BUGFIX] #41 Fix CSS outline property value [#42](https://github.com/open-dxp/admin-bundle/pull/42)
- [BUGFIX] Fix outline property in editmode.css [#43](https://github.com/open-dxp/admin-bundle/pull/43)
- [CHORE] Update BC layer comment to reflect OpenDxp 2.0 target [#44](https://github.com/open-dxp/admin-bundle/pull/44)

## 1.2.0
- [ENHANCEMENT] UI refactoring [#25](https://github.com/open-dxp/admin-bundle/pull/25)
- [ENHANCEMENT] Removed hover icon color styling for main navigation [#26](https://github.com/open-dxp/admin-bundle/pull/26)
- [NEW FEATURE] Video overlay: poster image, title description for every type [#24](https://github.com/open-dxp/admin-bundle/pull/24)
- [CHORE] PHPStan 2 [#30](https://github.com/open-dxp/admin-bundle/pull/30)
- [CHORE] Apply Rector Fixes with coverage level 0 [#32](https://github.com/open-dxp/admin-bundle/pull/32)
- [CHORE] Add changes from upstream (1.x <- 1.7.15) [#27](https://github.com/open-dxp/admin-bundle/pull/27)
- [CHORE] Adjust Test Setup [#34](https://github.com/open-dxp/admin-bundle/pull/34)
- [CHORE] Update ClassController & SettingsController [#36](https://github.com/open-dxp/admin-bundle/pull/36)
- [CHORE] Remove dark scheme from root [#35](https://github.com/open-dxp/admin-bundle/pull/35)
- [CHORE] Replace custom utilities with reusable helper methods across admin bu… [#33](https://github.com/open-dxp/admin-bundle/pull/33)

## 1.1.2
- [BUGFIX] Fix infinite spinner when moving objects in the tree ([#22](https://github.com/open-dxp/admin-bundle/pull/22))
- [CHORE] Retrieved changes from base: 1.7.14

## 1.1.1
- [CHORE] Retrieved changes from base: 1.7.12, 1.7.13

## 1.1.0
- [CHORE] Update package name [#9](https://github.com/open-dxp/admin-bundle/pull/9)
- [CHORE] Update core dependency constraint [#10](https://github.com/open-dxp/admin-bundle/pull/10)
- [CHORE] Update README.md [#11](https://github.com/open-dxp/admin-bundle/pull/11)
- [CHORE] Introduce composer alias [#12](https://github.com/open-dxp/admin-bundle/pull/12)

## 1.0.0
- [NEW FEATURE] Initial release
- [CHORE] Test [#1](https://github.com/open-dxp/admin-ui-classic-bundle/pull/1)
- [CHORE] Adopt latest changes from base [#2](https://github.com/open-dxp/admin-ui-classic-bundle/pull/2)
- [ENHANCEMENT] PHP 8.4 & Symfony 7.3 [#3](https://github.com/open-dxp/admin-ui-classic-bundle/pull/3)
- [ENHANCEMENT] Adopt layout changes [#4](https://github.com/open-dxp/admin-ui-classic-bundle/pull/4)
- [BUGFIX] Fixes [#5](https://github.com/open-dxp/admin-ui-classic-bundle/pull/5)
- [CHORE] Fix tests [#6](https://github.com/open-dxp/admin-ui-classic-bundle/pull/6)

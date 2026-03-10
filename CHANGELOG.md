# Changelog / Upgrade Notes

## 1.3.1
* Bugfix: Add guard clause to prevent rendering site settings without a valid site

## 1.3.0
* New Feature: Site custom settings by @solverat in https://github.com/open-dxp/admin-bundle/pull/61
* Improvement: Replace SystemSettingsConfig usage with GeneralHostResolver in LoginController and UserController by @solverat in https://github.com/open-dxp/admin-bundle/pull/62

## 1.2.3
* Translations: Fix data request - streamline filtering and improve readability by @solverat in https://github.com/open-dxp/admin-bundle/pull/50
* correct languages when creating admin translation (#53) by @benwalch in https://github.com/open-dxp/admin-bundle/pull/54
* Refactor SQL queries: enforce parameterized bindings and consistent style by @solverat in https://github.com/open-dxp/admin-bundle/pull/49
* strict equality checks across admin bundle by @solverat in https://github.com/open-dxp/admin-bundle/pull/55
* update constant declarations to use type hints and visibility modifiers by @solverat in https://github.com/open-dxp/admin-bundle/pull/56

## 1.2.2
* Update branding names by @scrummer in https://github.com/open-dxp/admin-bundle/pull/45
* Remove outdated references to GitHub issues and comments. by @scrummer in https://github.com/open-dxp/admin-bundle/pull/46
* fixed saving email documents (#47) by @benwalch in https://github.com/open-dxp/admin-bundle/pull/48

## 1.2.1
* update Rector configuration: add ForeachItemsAssignToEmptyArrayToAssignRector and update comments by @solverat in https://github.com/open-dxp/admin-bundle/pull/37
* Apply changes from upstream by @scrummer in https://github.com/open-dxp/admin-bundle/pull/40
* Various Ext JS Improvements by @solverat in https://github.com/open-dxp/admin-bundle/pull/38
* Replace `$request->get()` by @solverat in https://github.com/open-dxp/admin-bundle/pull/39
* #41 Fix CSS outline property value by @scrummer in https://github.com/open-dxp/admin-bundle/pull/42
* Fix outline property in editmode.css by @blankse in https://github.com/open-dxp/admin-bundle/pull/43
* update BC layer comment to reflect OpenDxp 2.0 target by @solverat in https://github.com/open-dxp/admin-bundle/pull/44

## 1.2.0
* UI refactoring by @open-dxp-stack in https://github.com/open-dxp/admin-bundle/pull/25
* Removed hover icon color styling for main navigation by @open-dxp-stack in https://github.com/open-dxp/admin-bundle/pull/26
* video overlay: poster image, title description for every type by @benwalch in https://github.com/open-dxp/admin-bundle/pull/24
* PHPStan 2 by @solverat in https://github.com/open-dxp/admin-bundle/pull/30
* Apply Rector Fixes with coverage level 0 by @solverat in https://github.com/open-dxp/admin-bundle/pull/32
* Add changes from upstream (1.x <- 1.7.15) by @scrummer in https://github.com/open-dxp/admin-bundle/pull/27
* Adjust Test Setup by @solverat in https://github.com/open-dxp/admin-bundle/pull/34
* Update ClassController & SettingsController by @open-dxp-stack in https://github.com/open-dxp/admin-bundle/pull/36
* Remove dark scheme from root by @open-dxp-stack in https://github.com/open-dxp/admin-bundle/pull/35
* Replace custom utilities with reusable helper methods across admin bu… by @solverat in https://github.com/open-dxp/admin-bundle/pull/33

## 1.1.2
* Fix infinite spinner when moving objects in the tree ([#22](https://github.com/open-dxp/admin-bundle/pull/22))
* Retrieved changes from base: 1.7.14

## 1.1.1
* Retrieved changes from base: 1.7.12, 1.7.13

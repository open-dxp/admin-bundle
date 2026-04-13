# OpenDXP | Admin Bundle

The Admin Bundle provides a Backend UI for OpenDXP.
It is based on the [ExtJS](https://www.sencha.com/products/extjs/) framework.

***

## Disclaimer

> OpenDXP is a community-driven fork based on the Pimcore® Community Edition (GPLv3).  
> OpenDXP is independent and maintained by its community and contributors. 
> It is not affiliated with, endorsed by, or sponsored by Pimcore GmbH.   
> Original credits: [Pimcore GmbH](https://www.pimcore.com)

**OpenDXP Admin Bundle is based on the Pimcore® Community Edition and remains licensed under GPLv3.**

***

## Provided Functionality in a Nutshell
- Documents: Content Management System for managing and editing content for your website.
- Data Objects: Manage and edit data objects for PIM, MDM, DAM, CRM, ERP, etc.
- Assets: Data Asset Management for managing and editing files, images, videos, etc.
- Settings: Generic settings for data types, system settings, etc.
- Users & Roles: Manage users and roles for granting access to the system.
- Reports: Create and Manage reports for your data.
- Search: Search across all elements in the system.
- Workflows: Create and manage workflows for your data.
And much more ...

## Working With Admin Interface

Following topics are short-cuts into the documentation for admin interface:

### Starting with OpenDXP Core 
- [Getting Started](https://github.com/open-dxp/opendxp/blob/1.x/doc/01_Getting_Started/06_Create_a_First_Project.md)
- [User & Roles](https://github.com/open-dxp/opendxp/blob/1.x/doc/22_Administration_of_OpenDxp/07_Users_and_Roles.md)
- [Admin Translations](https://github.com/open-dxp/opendxp/blob/1.x/doc/06_Multi_Language_i18n/07_Admin_Translations.md)

### Admin Documentation
- [Architecture](docs/00_Architecture/README.md)
- [Extension_Points](docs/10_Extension_Points)
- [Deeplinks](docs/10_Extension_Points/06_Deeplinks.md)
- 🤖 [Testing with AI (Claude)](https://github.com/open-dxp/opendxp/doc/19_Development_Tools_and_Details/50_Testing_with_AI.md) - Write, run and fix tests with Claude Code

=> [Full Documentation](docs/README.md)

***

## Contributing

**Bug fixes:** open a pull request including a step-by-step description to reproduce the problem.  
**Security vulnerabilities:** see the [security policy](https://github.com/open-dxp/opendxp/security/policy).

### Translations
Admin UI translations live in the [`translations/`](translations/) directory as YAML files.

- The English source file is `translations/admin.en.yaml`
- Each language has its own file, e.g. `admin.de.yaml`, `admin.fr.yaml`
- To improve an existing translation: edit the relevant file and open a pull request
- To add a new language: copy `admin.en.yaml`, rename it to `admin.<locale>.yaml`, translate the values, and open a pull request

---

## Upstream Origin & Version Transparency 
This project is a fork of the [Pimcore admin-ui-classic-bundle (95b1838 / v1.7.15)](https://github.com/pimcore/admin-ui-classic-bundle/tree/95b18389ad0678361d64fbbb5a1ba8db0bb4b54e), which is © Pimcore GmbH and licensed under GPLv3. 

## License 
Licensed under the GNU General Public License v3.0 (GPLv3). For details, please see [LICENSE.md](LICENSE.md). 

## Copyright 
© Pimcore GmbH  
© 2025 OpenDXP Contributors — GPLv3 

## Trademarks 
Pimcore® is a registered [trademark](https://www.trademarkelite.com/europe/trademark/trademark-detail/009309841/PIMCORE) of Pimcore GmbH. 
Any use of the Pimcore® mark in this repository is purely descriptive to identify the original upstream project. 

***

## Contact
For inquiries, suggestions, or contributions, feel free to reach us at contact@opendxp.io.

## About
OpenDXP is a community-driven project initiated by [DACHCOM.DIGITAL](https://www.dachcom.com/de-ch) (Rheineck, Switzerland) and maintained by its community and contributors. 
OpenDXP is independent and not affiliated with Pimcore GmbH. 

The project’s purpose is to preserve and maintain a GPLv3‑licensed codebase for community use.   

It is **not positioned as a competitor** to products or services of Pimcore GmbH and does **not** purport to replace or supersede any Pimcore offering.   

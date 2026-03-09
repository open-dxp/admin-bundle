<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Event;

class AdminEvents
{
    /**
     * The LOGIN_REDIRECT event is triggered before user is redirected to login page.
     *
     * This event allows you to influence path of the login page, e.g. for SSO integrations.
     *
     * @Event("OpenDxp\Bundle\AdminBundle\Event\Login\LoginRedirectEvent")
     *
     * @var string
     */
    public const string LOGIN_REDIRECT = 'opendxp.admin.login.redirect';

    /**
     * The LOGIN_LOSTPASSWORD event is triggered before the lost password email
     * is sent.
     *
     * This event allows you to alter the lost password mail or to prevent
     * mail sending at all. For full control, it allows you to set the response
     * to be returned.
     *
     * @Event("OpenDxp\Bundle\AdminBundle\Event\Login\LostPasswordEvent")
     *
     * @var string
     */
    public const string LOGIN_LOSTPASSWORD = 'opendxp.admin.login.lostpassword';

    /**
     * The LOGIN_LOGOUT event is triggered before the user is logged out.
     *
     * By setting a response on the event, you're able to control the response
     * returned after logout.
     *
     * @Event("OpenDxp\Bundle\AdminBundle\Event\Login\LogoutEvent")
     *
     * @var string
     */
    public const string LOGIN_LOGOUT = 'opendxp.admin.login.logout';

    /**
     * The LOGIN_BEFORE_RENDER event is triggered before the login view is rendered.
     *
     * Allows overriding the parameters and including templates.
     * ```php
     * public function getContent(GenericEvent $event): void
     * {
     *     $parameters = $event->getArgument('parameters');
     *     $parameters['includeTemplates']['VendorBundleName'] = '@VendorBundleName/path/to/template.html.twig';
     *     $event->setArgument('parameters', $parameters);
     * }
     * ```
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string LOGIN_BEFORE_RENDER = 'opendxp.admin.login.beforeRender';

    /**
     * The INDEX_SETTINGS event is triggered when the settings object is built for the index page.
     *
     * @Event("OpenDxp\Bundle\AdminBundle\Event\IndexActionSettingsEvent")
     *
     * @var string
     */
    public const string INDEX_ACTION_SETTINGS = 'opendxp.admin.indexAction.settings';

    /**
     * Fired before the request params are parsed. This event apply to the grid list.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - requestParams | contains the request parameters
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_LIST_BEFORE_FILTER_PREPARE = 'opendxp.admin.object.list.beforeFilterPrepare';

    /**
     * Allows you to modify the object list before it is loaded. This is a global event (search list, grid list, tree list, ...).
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | the object list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_LIST_BEFORE_LIST_LOAD = 'opendxp.admin.object.list.beforeListLoad';

    /**
     * Allows you to modify the object list before it is prepared for export.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | the object list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_LIST_BEFORE_EXPORT_PREPARE = 'opendxp.admin.object.list.beforeExportPrepare';

    /**
     * Allows you to modify the object list before it is exported.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | the object list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_LIST_BEFORE_EXPORT = 'opendxp.admin.object.list.beforeExport';

    /**
     * Allows you to modify the result after the list was loaded. This event apply to the grid list.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | raw result as an array
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_LIST_AFTER_LIST_LOAD = 'opendxp.admin.object.list.afterListLoad';

    /**
     * Allows to implement an additional condition for the object list when the search field within the grid is used.
     *
     * Subject: \OpenDxp\Bundle\AdminBundle\Helper\GridHelperService
     * Arguments:
     *  - query | the fulltext query search terms
     *  - condition | set the condition for the search
     *  - list | the data object list
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_LIST_HANDLE_FULLTEXT_QUERY = 'opendxp.admin.object.list.handleFulltextQuery';

    /**
     * Fired before the request params are parsed. This event apply to both the folder content preview list and the grid list.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - requestParams | contains the request parameters
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_LIST_BEFORE_FILTER_PREPARE = 'opendxp.admin.asset.list.beforeFilterPrepare';

    /**
     * Allows you to modify the asset list before it is loaded. This is a global event (folder content preview list, grid list, tree list, ...).
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | the object list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_LIST_BEFORE_LIST_LOAD = 'opendxp.admin.asset.list.beforeListLoad';

    /**
     * Arguments:
     *  - field
     *  - result
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_GET_FIELD_GRID_CONFIG = 'opendxp.admin.asset.getFieldGridConfig';

    /**
     * Allows you to modify the result after the list was loaded. This event apply to both the folder content preview list and the grid list.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | raw result as an array
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_LIST_AFTER_LIST_LOAD = 'opendxp.admin.asset.list.afterListLoad';

    /**
     * Allows you to modify the data from the listfolder grid before it gets processed
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - data | raw data as an array
     *  - processed | true to stop processing
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_LIST_BEFORE_UPDATE = 'opendxp.admin.asset.list.beforeUpdate';

    /**
     * Allows you to modify the batch update data from the listfolder grid before it gets processed
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - params |
     *  - processed | true to stop processing
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_LIST_BEFORE_BATCH_UPDATE = 'opendxp.admin.asset.list.beforeBatchUpdate';

    /**
     * Fired before the request params are parsed. This event apply to the seo panel tree.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - requestParams | contains the request parameters
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string DOCUMENT_LIST_BEFORE_FILTER_PREPARE = 'opendxp.admin.document.list.beforeFilterPrepare';

    /**
     * Allows you to modify the document list before it is loaded. This is a global event (seo panel tree, tree list, ...).
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | the object list
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string DOCUMENT_LIST_BEFORE_LIST_LOAD = 'opendxp.admin.document.list.beforeListLoad';

    /**
     * Allows you to modify the result after the list was loaded. This event apply to the seo panel tree.
     *
     * Subject: A controller extending \OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController
     * Arguments:
     *  - list | raw result as an array
     *  - context | contains contextual information
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string DOCUMENT_LIST_AFTER_LIST_LOAD = 'opendxp.admin.document.list.afterListLoad';

    /**
     * Fired before the request params are parsed.
     *
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset\AssetController
     * Arguments:
     *  - data | array | the response data, this can be modified
     *  - asset | Asset | the current asset
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_GET_PRE_SEND_DATA = 'opendxp.admin.asset.get.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset\AssetController
     * Arguments:
     *  - assets | array | the list of asset tree nodes
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA = 'opendxp.admin.asset.treeGetChildrenById.preSendData';

    /**
     * Fired before the request params are parsed.
     *
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementControllerBase
     * Arguments:
     *  - data | array | the response data, this can be modified
     *  - document | Document | the current document
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string DOCUMENT_GET_PRE_SEND_DATA = 'opendxp.admin.document.get.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\DocumentController
     * Arguments:
     *  - documents | array | the list of document tree nodes
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string DOCUMENT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA = 'opendxp.admin.document.treeGetChildrenById.preSendData';

    /**
     * Fired before the edit lock is handled.
     *
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObjectController
     * Arguments:
     *  - data | array | editLock behaviour, this can be modified
     *  - object | AbstractObject | the current object
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_GET_IS_LOCKED = 'opendxp.admin.dataobject.get.isLocked';

    /**
     * Fired before the request params are parsed.
     *
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObjectController
     * Arguments:
     *  - data | array | the response data, this can be modified
     *  - object | AbstractObject | the current object
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_GET_PRE_SEND_DATA = 'opendxp.admin.dataobject.get.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject\DataObjectHelperController
     * Arguments:
     *  - data | array | the response data which this can be modified
     *  - request | Request | the Request object passed to the action
     *  - config | Config | the Config object passed to the action
     *  - context | string | 'get' or 'delete'
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_GRID_GET_COLUMN_CONFIG_PRE_SEND_DATA = 'opendxp.admin.dataobject.gridGetColumnConfig.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObjectController
     * Arguments:
     *  - objects | array | the list of object tree nodes
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string OBJECT_TREE_GET_CHILDREN_BY_ID_PRE_SEND_DATA = 'opendxp.admin.dataobject.treeGetChildrenById.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ClassController
     * Arguments:
     *  - list | array | the list of field collections
     *  - objectId | int | id of the origin object
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string CLASS_FIELDCOLLECTION_LIST_PRE_SEND_DATA = 'opendxp.admin.class.fieldcollectionList.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ClassController
     * Arguments:
     *  - icons | array | the list of selectable icons
     *  - classId | string | classid of class definition
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string CLASS_OBJECT_ICONS_PRE_SEND_DATA = 'opendxp.admin.class.dataobject.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ClassController
     * Arguments:
     *  - list | array | the list of object bricks
     *  - objectId | int | id of the origin object
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string CLASS_OBJECTBRICK_LIST_PRE_SEND_DATA = 'opendxp.admin.class.objectbrickList.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ClassController
     * Arguments:
     *  - brickDefinition | the brick definition
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string CLASS_OBJECTBRICK_UPDATE_DEFINITION = 'opendxp.admin.class.objectbrick.updateDefinition';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ClassController
     * Arguments:
     *  - list | array | the list of select options
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string CLASS_SELECTOPTIONS_LIST_PRE_SEND_DATA = 'opendxp.admin.class.selectoptionsList.preSendData';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ClassController
     * Arguments:
     *  - selectOptionsConfiguration | \OpenDxp\Model\DataObject\SelectOptions\Config
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string CLASS_SELECTOPTIONS_UPDATE_CONFIGURATION = 'opendxp.admin.class.selectoptions.updateDefinition';

    /**
     * Fired before an element is opened
     *
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementController
     * Arguments:
     *  - type element type
     *  - id
     *
     * @Event("OpenDxp\Event\Model\ResolveElementEvent")
     *
     * @var string
     */
    public const string RESOLVE_ELEMENT = 'opendxp.admin.resolve.element';

    /**
     * Fired before an element is opened
     *
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\ElementController
     * Arguments:
     *     none
     *
     * @Event("OpenDxp\Bundle\AdminBundle\Event\ElementAdminStyleEvent")
     *
     * @var string
     */
    public const string RESOLVE_ELEMENT_ADMIN_STYLE = 'opendxp.admin.resolve.elementAdminStyle';

    /**
     * Subject: \OpenDxp\Bundle\AdminBundle\Controller\Admin\Asset\AssetController
     * Arguments:
     *  - id | int | asset id
     *  - metadata | array | contains the data received from the editor UI
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string ASSET_METADATA_PRE_SET = 'opendxp.admin.asset.metadata.preSave';

    /**
     * This event is fired after OpenDXP generates the runtime Perspective
     *
     * Arguments:
     *  - result | The result array
     *  - configName | Name of the current perspective
     *
     * @Event("Symfony\Component\EventDispatcher\GenericEvent")
     *
     * @var string
     */
    public const string PERSPECTIVE_POST_GET_RUNTIME = 'opendxp.admin.perspective.postGetRuntime';

    /**
     * This event is fired after a site configuration panel gets rendered
     *
     * @Event("OpenDxp\Bundle\AdminBundle\Event\SiteCustomSettingsEvent")
     */
    public const string SITE_CUSTOM_SETTINGS = 'opendxp.admin.site.customSettings';
}

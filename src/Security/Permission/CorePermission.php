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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Security\Permission;

enum CorePermission: string
{
    case Assets                = 'assets';
    case Classes               = 'classes';
    case Selectoptions         = 'selectoptions';
    case ClearCache            = 'clear_cache';
    case ClearFullpageCache    = 'clear_fullpage_cache';
    case ClearTempFiles        = 'clear_temp_files';
    case Dashboards            = 'dashboards';
    case DocumentTypes         = 'document_types';
    case Documents             = 'documents';
    case Emails                = 'emails';
    case NotesEvents           = 'notes_events';
    case Objects               = 'objects';
    case PredefinedProperties  = 'predefined_properties';
    case AssetMetadata         = 'asset_metadata';
    case Recyclebin            = 'recyclebin';
    case Redirects             = 'redirects';
    case Seemode               = 'seemode';
    case ShareConfigurations   = 'share_configurations';
    case SystemSettings        = 'system_settings';
    case TagsConfiguration     = 'tags_configuration';
    case TagsAssignment        = 'tags_assignment';
    case TagsSearch            = 'tags_search';
    case Thumbnails            = 'thumbnails';
    case Translations          = 'translations';
    case Users                 = 'users';
    case WebsiteSettings       = 'website_settings';
    case WorkflowDetails       = 'workflow_details';
    case Notifications         = 'notifications';
    case NotificationsSend     = 'notifications_send';
    case Sites                 = 'sites';
    case ObjectsSortMethod     = 'objects_sort_method';
    case Objectbricks          = 'objectbricks';
    case Fieldcollections      = 'fieldcollections';
    case QuantityValueUnits    = 'quantityValueUnits';
    case Classificationstore   = 'classificationstore';
    case MaintenanceMode       = 'maintenance_mode';
}

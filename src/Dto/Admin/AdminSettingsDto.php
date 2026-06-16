<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Dto\Admin;

final readonly class AdminSettingsDto
{
    public function __construct(
        // Core identity
        public string $instanceId,
        public string $version,
        public string $build,
        public bool $debug,
        public bool $devMode,
        public bool $disableMinifyJs,
        public string $environment,
        public string $sessionId,

        // Languages
        public string $language,
        public array $websiteLanguages,
        public array $requiredLanguages,

        // Capabilities
        public bool $chromiumAvailable,
        public bool $videoConverterAvailable,

        // Config flags
        public bool $debugAdminTranslations,
        public bool $generateDocumentPreviews,
        public bool $disableAssetTreePreview,
        public bool $hideEditImage,
        public bool $dependencyEnabled,

        // URLs / paths
        public string $mainDomain,
        public ?string $customAdminEntrypointUrl,
        public string $timezone,
        public string $tileLayerUrlTemplate,
        public string $geocodingUrlTemplate,
        public string $reverseGeocodingUrlTemplate,
        public string $hostname,
        public string $assetDefaultUploadPath,

        // Paging limits
        public int $assetTreePagingLimit,
        public int $documentTreePagingLimit,
        public int $objectTreePagingLimit,

        // Auto-save intervals
        public int $documentAutoSaveInterval,
        public int $objectAutoSaveInterval,

        // Perspectives / portlets
        public array $perspective,
        public array $availablePerspectives,
        public array $disabledPortlets,

        // Writeable flags
        public bool $imageThumbnailsWriteable,
        public bool $videoThumbnailsWriteable,
        public bool $documentTypesWriteable,
        public bool $predefinedPropertiesWriteable,
        public bool $predefinedAssetMetadataWriteable,
        public bool $perspectivesWriteable,
        public bool $customViewsWriteable,
        public bool $classDefinitionWriteable,
        public bool $objectCustomLayoutWriteable,
        public bool $selectOptionsWriteable,

        // Search / type enumerations
        public array $assetSearchTypes,
        public array $documentTypesConfiguration,
        public array $documentSearchTypes,
        public array $documentValidTypes,
        public array $documentEmailSearchTypes,
        public string $selectOptionsProviderClass,

        // System vars
        public int $uploadMaxFilesize,
        public int $sessionGcMaxlifetime,

        // Maintenance
        public bool $maintenanceActive,
        public bool $maintenanceMode,

        // Mail
        public bool $mailConfigured,
        public ?string $mailDefaultAddress,

        // Custom views
        public array $customViews,

        // Notifications
        public bool $notificationsEnabled,
        public bool $checkNewNotificationEnabled,
        public int $checkNewNotificationInterval,

        // CSRF
        public string $csrfToken,
    ) {}
}

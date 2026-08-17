<?php


declare(strict_types=1);

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
    ) {
    }

    public function asSettingsArray(): array
    {
        return [
            'instanceId'      => $this->instanceId,
            'version'         => $this->version,
            'build'           => $this->build,
            'debug'           => $this->debug,
            'devmode'         => $this->devMode,
            'disableMinifyJs' => $this->disableMinifyJs,
            'environment'     => $this->environment,
            'sessionId'       => $this->sessionId,

            'language'          => $this->language,
            'websiteLanguages'  => $this->websiteLanguages,
            'requiredLanguages' => $this->requiredLanguages,

            'showCloseConfirmation'          => true,
            'debug_admin_translations'       => $this->debugAdminTranslations,
            'document_generatepreviews'      => $this->generateDocumentPreviews,
            'asset_disable_tree_preview'     => $this->disableAssetTreePreview,
            'asset_hide_edit'                => $this->hideEditImage,
            'asset_tree_paging_limit'        => $this->assetTreePagingLimit,
            'asset_default_upload_path'      => $this->assetDefaultUploadPath,
            'chromium'                       => $this->chromiumAvailable,
            'videoconverter'                 => $this->videoConverterAvailable,
            'main_domain'                    => $this->mainDomain,
            'custom_admin_entrypoint_url'    => $this->customAdminEntrypointUrl,
            'timezone'                       => $this->timezone,
            'tile_layer_url_template'        => $this->tileLayerUrlTemplate,
            'geocoding_url_template'         => $this->geocodingUrlTemplate,
            'reverse_geocoding_url_template' => $this->reverseGeocodingUrlTemplate,
            'document_tree_paging_limit'     => $this->documentTreePagingLimit,
            'object_tree_paging_limit'       => $this->objectTreePagingLimit,
            'hostname'                       => $this->hostname,
            'dependency'                     => $this->dependencyEnabled,
            'document_auto_save_interval'    => $this->documentAutoSaveInterval,
            'object_auto_save_interval'      => $this->objectAutoSaveInterval,

            'perspective'           => $this->perspective,
            'availablePerspectives' => $this->availablePerspectives,
            'disabledPortlets'      => $this->disabledPortlets,

            'image-thumbnails-writeable'          => $this->imageThumbnailsWriteable,
            'video-thumbnails-writeable'          => $this->videoThumbnailsWriteable,
            'document-types-writeable'            => $this->documentTypesWriteable,
            'predefined-properties-writeable'     => $this->predefinedPropertiesWriteable,
            'predefined-asset-metadata-writeable' => $this->predefinedAssetMetadataWriteable,
            'perspectives-writeable'              => $this->perspectivesWriteable,
            'custom-views-writeable'              => $this->customViewsWriteable,
            'class-definition-writeable'          => $this->classDefinitionWriteable,
            'object-custom-layout-writeable'      => $this->objectCustomLayoutWriteable,
            'select-options-writeable'            => $this->selectOptionsWriteable,

            'asset_search_types'            => $this->assetSearchTypes,
            'document_types_configuration'  => $this->documentTypesConfiguration,
            'document_search_types'         => $this->documentSearchTypes,
            'document_valid_types'          => $this->documentValidTypes,
            'document_email_search_types'   => $this->documentEmailSearchTypes,
            'select_options_provider_class' => $this->selectOptionsProviderClass,

            'upload_max_filesize'    => $this->uploadMaxFilesize,
            'session_gc_maxlifetime' => $this->sessionGcMaxlifetime,

            'maintenance_active' => $this->maintenanceActive,
            'maintenance_mode'   => $this->maintenanceMode,

            'mail'               => $this->mailConfigured,
            'mailDefaultAddress' => $this->mailDefaultAddress,

            'customviews' => $this->customViews,

            'notifications_enabled'         => $this->notificationsEnabled,
            'checknewnotification_enabled'  => $this->checkNewNotificationEnabled,
            'checknewnotification_interval' => $this->checkNewNotificationInterval,

            'csrfToken' => $this->csrfToken,
        ];
    }
}

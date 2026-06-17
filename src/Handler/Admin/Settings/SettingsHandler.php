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

namespace OpenDxp\Bundle\AdminBundle\Handler\Admin\Settings;

use OpenDxp\Bundle\AdminBundle\Builder\AdminSettingsAssembler;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\IndexActionSettingsEvent;
use OpenDxp\Bundle\AdminBundle\Perspective\Config as PerspectiveConfig;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Config;
use OpenDxp\Extension\Bundle\OpenDxpBundleManager;
use OpenDxp\SystemSettingsConfig;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SettingsHandler
{
    public function __construct(
        private readonly AdminSettingsAssembler $factory,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly OpenDxpBundleManager $bundleManager,
        private readonly Config $config,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(SettingsPayload $payload): SettingsResult
    {
        $user = $this->userContext->getAdminUser();
        $dto = $this->factory->createSettings($payload, $user);

        $settings = [
            'instanceId'          => $dto->instanceId,
            'version'             => $dto->version,
            'build'               => $dto->build,
            'debug'               => $dto->debug,
            'devmode'             => $dto->devMode,
            'disableMinifyJs'     => $dto->disableMinifyJs,
            'environment'         => $dto->environment,
            'sessionId'           => $dto->sessionId,

            'language'            => $dto->language,
            'websiteLanguages'    => $dto->websiteLanguages,
            'requiredLanguages'   => $dto->requiredLanguages,

            'showCloseConfirmation'          => true,
            'debug_admin_translations'       => $dto->debugAdminTranslations,
            'document_generatepreviews'      => $dto->generateDocumentPreviews,
            'asset_disable_tree_preview'     => $dto->disableAssetTreePreview,
            'asset_hide_edit'                => $dto->hideEditImage,
            'asset_tree_paging_limit'        => $dto->assetTreePagingLimit,
            'asset_default_upload_path'      => $dto->assetDefaultUploadPath,
            'chromium'                       => $dto->chromiumAvailable,
            'videoconverter'                 => $dto->videoConverterAvailable,
            'main_domain'                    => $dto->mainDomain,
            'custom_admin_entrypoint_url'    => $dto->customAdminEntrypointUrl,
            'timezone'                       => $dto->timezone,
            'tile_layer_url_template'        => $dto->tileLayerUrlTemplate,
            'geocoding_url_template'         => $dto->geocodingUrlTemplate,
            'reverse_geocoding_url_template' => $dto->reverseGeocodingUrlTemplate,
            'document_tree_paging_limit'     => $dto->documentTreePagingLimit,
            'object_tree_paging_limit'       => $dto->objectTreePagingLimit,
            'hostname'                       => $dto->hostname,
            'dependency'                     => $dto->dependencyEnabled,
            'document_auto_save_interval'    => $dto->documentAutoSaveInterval,
            'object_auto_save_interval'      => $dto->objectAutoSaveInterval,

            'perspective'           => $dto->perspective,
            'availablePerspectives' => $dto->availablePerspectives,
            'disabledPortlets'      => $dto->disabledPortlets,

            'image-thumbnails-writeable'          => $dto->imageThumbnailsWriteable,
            'video-thumbnails-writeable'          => $dto->videoThumbnailsWriteable,
            'document-types-writeable'            => $dto->documentTypesWriteable,
            'predefined-properties-writeable'     => $dto->predefinedPropertiesWriteable,
            'predefined-asset-metadata-writeable' => $dto->predefinedAssetMetadataWriteable,
            'perspectives-writeable'              => $dto->perspectivesWriteable,
            'custom-views-writeable'              => $dto->customViewsWriteable,
            'class-definition-writeable'          => $dto->classDefinitionWriteable,
            'object-custom-layout-writeable'      => $dto->objectCustomLayoutWriteable,
            'select-options-writeable'            => $dto->selectOptionsWriteable,

            'asset_search_types'           => $dto->assetSearchTypes,
            'document_types_configuration' => $dto->documentTypesConfiguration,
            'document_search_types'        => $dto->documentSearchTypes,
            'document_valid_types'         => $dto->documentValidTypes,
            'document_email_search_types'  => $dto->documentEmailSearchTypes,
            'select_options_provider_class' => $dto->selectOptionsProviderClass,

            'upload_max_filesize'    => $dto->uploadMaxFilesize,
            'session_gc_maxlifetime' => $dto->sessionGcMaxlifetime,

            'maintenance_active' => $dto->maintenanceActive,
            'maintenance_mode'   => $dto->maintenanceMode,

            'mail'               => $dto->mailConfigured,
            'mailDefaultAddress' => $dto->mailDefaultAddress,

            'customviews' => $dto->customViews,

            'notifications_enabled'          => $dto->notificationsEnabled,
            'checknewnotification_enabled'   => $dto->checkNewNotificationEnabled,
            'checknewnotification_interval'  => $dto->checkNewNotificationInterval,

            'csrfToken' => $dto->csrfToken,
        ];

        $event = new IndexActionSettingsEvent($settings);
        $this->eventDispatcher->dispatch($event, AdminEvents::INDEX_ACTION_SETTINGS);

        return new SettingsResult(
            templateParams: [
                'config'             => $this->config,
                'systemSettings'     => SystemSettingsConfig::get(),
                'adminSettings'      => AdminConfig::get(),
                'perspectiveConfig'  => new PerspectiveConfig(),
                'runtimePerspective' => $dto->perspective,
                'pluginJsPaths'      => $this->bundleManager->getJsPaths(),
                'pluginCssPaths'     => $this->bundleManager->getCssPaths(),
                'settings'           => $event->getSettings(),
            ],
            template: $event->getTemplate(),
        );
    }
}

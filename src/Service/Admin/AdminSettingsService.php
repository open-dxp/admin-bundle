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

namespace OpenDxp\Bundle\AdminBundle\Service\Admin;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Dto\Admin\AdminSettingsDto;
use OpenDxp\Bundle\AdminBundle\Perspective\Config as PerspectiveConfig;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;
use OpenDxp\Bundle\AdminBundle\Session\SessionIdentityInterface;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use OpenDxp\Config;
use OpenDxp\Image\HtmlToImage;
use OpenDxp\Maintenance\Executor;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\ClassDefinition\CustomLayout;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\DocType;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Property\Predefined;
use OpenDxp\Model\User;
use OpenDxp\SystemSettingsConfig;
use OpenDxp\Tool;
use OpenDxp\Tool\Admin;
use OpenDxp\Tool\MaintenanceModeHelperInterface;
use OpenDxp\Version;
use OpenDxp\Video;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AdminSettingsService
{
    public function __construct(
        private readonly Config $config,
        private readonly DashboardService $dashboardService,
        private readonly CsrfProtectionHandler $csrfProtection,
        private readonly Executor $maintenanceExecutor,
        private readonly MaintenanceModeHelperInterface $maintenanceModeHelper,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly KernelInterface $kernel,
        private readonly RequestStack $requestStack,
        private readonly SessionIdentityInterface $sessionIdentity,
        private readonly InstanceIdentityService $instanceIdentity,
        #[Autowire('%opendxp_admin.custom_admin_route_name%')]
        private readonly string $customAdminRouteName,
    ) {
    }

    public function createSettings(string $locale, User $user): AdminSettingsDto
    {
        $config = $this->config;
        $systemSettings = SystemSettingsConfig::get();
        $adminSettings = AdminConfig::get();

        $runtimePerspective = PerspectiveConfig::getRuntimePerspective($user);

        try {
            $adminEntrypointUrl = $this->urlGenerator->generate(
                $this->customAdminRouteName,
                [],
                UrlGeneratorInterface::ABSOLUTE_URL
            );
        } catch (Exception) {
            $adminEntrypointUrl = null;
        }

        $requiredLanguages = $systemSettings['general']['valid_languages'];
        if (array_key_exists('required_languages', $systemSettings['general'])) {
            $requiredLanguages = $systemSettings['general']['required_languages'];
        }

        $maxUpload = OpenDxp\Helper\FileSystemHelper::filesizeToBytes(ini_get('upload_max_filesize') . 'B');
        $maxPost = OpenDxp\Helper\FileSystemHelper::filesizeToBytes(ini_get('post_max_size') . 'B');
        $uploadBytes = min($maxUpload, $maxPost) ?: $maxUpload;

        $sessionGcMaxlifetime = (int) ini_get('session.gc_maxlifetime') ?: 120;

        $maintenanceActive = false;
        if (($lastExecution = $this->maintenanceExecutor->getLastExecution()) && time() - $lastExecution < 3660) {
            $maintenanceActive = true;
        }

        $mailIncomplete = false;
        if (isset($config['email']) && $systemSettings['email']) {
            if (OpenDxp::inDebugMode() && empty($systemSettings['email']['debug']['email_addresses'])) {
                $mailIncomplete = true;
            }
            if (empty($config['email']['sender']['email'])) {
                $mailIncomplete = true;
            }
        }

        $notificationsEnabled = (bool) $config['notifications']['enabled'];
        $environment = $this->kernel->getEnvironment();

        return new AdminSettingsDto(
            instanceId: $this->instanceIdentity->getInstanceId(),
            systemUuid: $this->instanceIdentity->getSystemUuid($environment),
            version: Version::getVersion(),
            build: Version::getRevision(),
            debug: OpenDxp::inDebugMode(),
            devMode: OpenDxp::inDevMode(),
            disableMinifyJs: OpenDxp::disableMinifyJs(),
            environment: $environment,
            sessionId: htmlentities($this->sessionIdentity->getId(), ENT_QUOTES, 'UTF-8'),

            language: $locale,
            websiteLanguages: Admin::reorderWebsiteLanguages($user, $systemSettings['general']['valid_languages'], true),
            requiredLanguages: $requiredLanguages,

            chromiumAvailable: HtmlToImage::isSupported(),
            videoConverterAvailable: Video::isAvailable(),

            debugAdminTranslations: (bool) $systemSettings['general']['debug_admin_translations'],
            generateDocumentPreviews: (bool) $config['documents']['generate_preview'],
            disableAssetTreePreview: (bool) $adminSettings['assets']['disable_tree_preview'],
            hideEditImage: (bool) $adminSettings['assets']['hide_edit_image'],
            dependencyEnabled: (bool) $config['dependency']['enabled'],

            mainDomain: $systemSettings['general']['domain'],
            customAdminEntrypointUrl: $adminEntrypointUrl,
            timezone: $config['general']['timezone'] ?: date_default_timezone_get(),
            tileLayerUrlTemplate: $config['maps']['tile_layer_url_template'],
            geocodingUrlTemplate: $config['maps']['geocoding_url_template'],
            reverseGeocodingUrlTemplate: $config['maps']['reverse_geocoding_url_template'],
            hostname: htmlentities(Tool::getHostname(), ENT_QUOTES, 'UTF-8'),
            assetDefaultUploadPath: $config['assets']['default_upload_path'],

            assetTreePagingLimit: $config['assets']['tree_paging_limit'],
            documentTreePagingLimit: $config['documents']['tree_paging_limit'],
            objectTreePagingLimit: $config['objects']['tree_paging_limit'],

            documentAutoSaveInterval: $config['documents']['auto_save_interval'],
            objectAutoSaveInterval: $config['objects']['auto_save_interval'],

            perspective: $runtimePerspective,
            availablePerspectives: PerspectiveConfig::getAvailablePerspectives($user),
            disabledPortlets: $this->dashboardService->getDisabledPortlets($user),

            imageThumbnailsWriteable: (new Asset\Image\Thumbnail\Config())->isWriteable(),
            videoThumbnailsWriteable: (new Asset\Video\Thumbnail\Config())->isWriteable(),
            documentTypesWriteable: (new DocType())->isWriteable(),
            predefinedPropertiesWriteable: (new Predefined())->isWriteable(),
            predefinedAssetMetadataWriteable: (new \OpenDxp\Model\Metadata\Predefined())->isWriteable(),
            perspectivesWriteable: PerspectiveConfig::isWriteable(),
            customViewsWriteable: \OpenDxp\Bundle\AdminBundle\CustomView\Config::isWriteable(),
            classDefinitionWriteable: !isset($_SERVER['OPENDXP_CLASS_DEFINITION_WRITABLE']) || (bool) $_SERVER['OPENDXP_CLASS_DEFINITION_WRITABLE'],
            objectCustomLayoutWriteable: (new CustomLayout())->isWriteable(),
            selectOptionsWriteable: (new \OpenDxp\Model\DataObject\SelectOptions\Config())->isWriteable(),

            assetSearchTypes: Asset::getTypes(),
            documentTypesConfiguration: Document::getTypesConfiguration(),
            documentSearchTypes: Document::getTypes(),
            documentValidTypes: array_values(array_filter(Document::getTypes(), fn ($t) => $t !== 'folder')),
            documentEmailSearchTypes: $config['documents']['email_search'],
            selectOptionsProviderClass: SelectOptionsOptionsProvider::class,

            uploadMaxFilesize: (int) $uploadBytes,
            sessionGcMaxlifetime: $sessionGcMaxlifetime,

            maintenanceActive: $maintenanceActive,
            maintenanceMode: $this->maintenanceModeHelper->isActive(),

            mailConfigured: !$mailIncomplete,
            mailDefaultAddress: $config['email']['sender']['email'] ?? null,

            customViews: $this->buildCustomViews(),

            notificationsEnabled: $notificationsEnabled,
            checkNewNotificationEnabled: $notificationsEnabled && (bool) $config['notifications']['check_new_notification']['enabled'],
            checkNewNotificationInterval: $config['notifications']['check_new_notification']['interval'] * 1000,

            csrfToken: $this->csrfProtection->getCsrfToken($this->requestStack->getSession()) ?? '',
        );
    }

    private function buildCustomViews(): array
    {
        $cvData = [];
        foreach (\OpenDxp\Bundle\AdminBundle\CustomView\Config::get() as $node) {
            $tmpData = $node;
            $treeType = $tmpData['treetype'] ?: 'object';
            $rootNode = Service::getElementByPath($treeType, $tmpData['rootfolder']);

            if ($rootNode) {
                $tmpData['rootId'] = $rootNode->getId();
                $tmpData['allowedClasses'] = $tmpData['classes'] ?? null;
                $tmpData['showroot'] = (bool) $tmpData['showroot'];

                if ($rootNode->isAllowed('list')) {
                    $cvData[] = $tmpData;
                }
            }
        }

        return $cvData;
    }
}

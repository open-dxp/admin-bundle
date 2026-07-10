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

namespace OpenDxp\Bundle\AdminBundle\Builder;

use Doctrine\DBAL\Connection;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Dto\Admin\AdminSettingsDto;
use OpenDxp\Bundle\AdminBundle\Dto\Admin\StatisticsDto;
use OpenDxp\Bundle\AdminBundle\Perspective\Config as PerspectiveConfig;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Service\Portal\DashboardService;
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
use OpenDxp\Bundle\AdminBundle\Handler\Admin\Settings\SettingsPayload;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\HttpKernel\KernelInterface;

final class AdminSettingsAssembler
{
    public function __construct(
        private readonly Config $config,
        private readonly DashboardService $dashboardService,
        private readonly CsrfProtectionHandler $csrfProtection,
        private readonly Executor $maintenanceExecutor,
        private readonly MaintenanceModeHelperInterface $maintenanceModeHelper,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly Connection $db,
        private readonly KernelInterface $kernel,
        private readonly RequestStack $requestStack,
        #[Autowire('%opendxp_admin.custom_admin_route_name%')]
        private readonly string $customAdminRouteName,
        #[Autowire('%secret%')]
        private readonly string $secret,
    ) {}

    public function createSettings(SettingsPayload $payload, User $user): AdminSettingsDto
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
        } catch (\Exception) {
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

        return new AdminSettingsDto(
            instanceId: $this->buildInstanceId(),
            version: Version::getVersion(),
            build: Version::getRevision(),
            debug: OpenDxp::inDebugMode(),
            devMode: OpenDxp::inDevMode(),
            disableMinifyJs: OpenDxp::disableMinifyJs(),
            environment: $this->kernel->getEnvironment(),
            sessionId: htmlentities($payload->sessionId, ENT_QUOTES, 'UTF-8'),

            language: $payload->locale,
            websiteLanguages: Admin::reorderWebsiteLanguages($user, $systemSettings['general']['valid_languages'], true),
            requiredLanguages: $requiredLanguages,

            chromiumAvailable: HtmlToImage::isSupported(),
            videoConverterAvailable: Video::isAvailable(),

            debugAdminTranslations: (bool) $systemSettings['general']['debug_admin_translations'],
            generateDocumentPreviews: (bool) $config['documents']['generate_preview'],
            disableAssetTreePreview: (bool) $adminSettings['assets']['disable_tree_preview'],
            hideEditImage: (bool) $adminSettings['assets']['hide_edit_image'],
            dependencyEnabled: $config['dependency']['enabled'],

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

            csrfToken: $this->csrfProtection->getCsrfToken($this->requestStack->getSession()),
        );
    }

    public function createStatistics(): StatisticsDto
    {
        try {
            $dbVersion = $this->db->fetchOne('SELECT VERSION()');
        } catch (\Throwable) {
            $dbVersion = null;
        }

        return new StatisticsDto(
            instanceId: $this->buildInstanceId(),
            revision: Version::getRevision(),
            version: Version::getVersion(),
            majorVersion: Version::getMajorVersion(),
            phpVersion: PHP_VERSION,
            dbVersion: is_string($dbVersion) ? $dbVersion : null,
            bundles: array_keys($this->kernel->getBundles()),
        );
    }

    private function buildInstanceId(): string
    {
        try {
            return sha1(substr($this->secret, 3, -3));
        } catch (\Exception) {
            return 'not-set';
        }
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

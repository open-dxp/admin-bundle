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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use Doctrine\DBAL\Connection;
use Exception;
use GuzzleHttp\ClientInterface;
use GuzzleHttp\Exception\GuzzleException;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\IndexActionSettingsEvent;
use OpenDxp\Bundle\AdminBundle\Helper\Dashboard;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Bundle\CoreBundle\OptionsProvider\SelectOptionsOptionsProvider;
use OpenDxp\Config;
use OpenDxp\Controller\KernelResponseEventInterface;
use OpenDxp\Extension\Bundle\OpenDxpBundleManager;
use OpenDxp\Image\HtmlToImage;
use OpenDxp\Maintenance\Executor;
use OpenDxp\Maintenance\ExecutorInterface;
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
use OpenDxp\Version;
use OpenDxp\Video;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Throwable;

/**
 * @internal
 */
class IndexController extends AdminAbstractController implements KernelResponseEventInterface
{
    public function __construct(
        protected EventDispatcherInterface $eventDispatcher,
        protected TranslatorInterface $translator,
        protected ClientInterface $httpClient
    ) {
    }

    /**
     * @throws Exception
     */
    #[Route('/', name: 'opendxp_admin_index', methods: ['GET'])]
    public function indexAction(
        Request $request,
        KernelInterface $kernel,
        Executor $maintenanceExecutor,
        CsrfProtectionHandler $csrfProtection,
        Config $config,
        OpenDxpBundleManager $bundleManager,
        Tool\MaintenanceModeHelperInterface $maintenanceModeHelper
    ): Response {
        $user = $this->getAdminUser();
        $perspectiveConfig = new \OpenDxp\Bundle\AdminBundle\Perspective\Config();
        $templateParams = [
            'config' => $config,
            'systemSettings' => SystemSettingsConfig::get(),
            'adminSettings' => AdminConfig::get(),
            'perspectiveConfig' => $perspectiveConfig,
        ];

        $this
            ->setAdminLanguage($request, $user)
            ->addRuntimePerspective($templateParams, $user)
            ->addPluginAssets($bundleManager, $templateParams);

        $this->buildOpenDxpSettings(
            $request,
            $templateParams,
            $user,
            $kernel,
            $maintenanceExecutor,
            $csrfProtection,
            $maintenanceModeHelper
        );

        if ($user->getTwoFactorAuthentication('required') && !$user->getTwoFactorAuthentication('enabled')) {
            return $this->redirectToRoute('opendxp_admin_2fa_setup');
        }

        // allow to alter settings via an event
        $settingsEvent = new IndexActionSettingsEvent($templateParams['settings'] ?? []);
        $this->eventDispatcher->dispatch($settingsEvent, AdminEvents::INDEX_ACTION_SETTINGS);
        $templateParams['settings'] = $settingsEvent->getSettings();

        return $this->render($settingsEvent->getTemplate() ?: '@OpenDxpAdmin/admin/index/index.html.twig', $templateParams);
    }

    #[Route('/index/statistics', name: 'opendxp_admin_index_statistics', methods: ['GET'])]
    public function statisticsAction(Request $request, Connection $db, KernelInterface $kernel): JsonResponse
    {
        if (!$request->isXmlHttpRequest()) {
            throw $this->createAccessDeniedHttpException();
        }

        try {
            $mysqlVersion = $db->fetchOne('SELECT VERSION()');
        } catch (Throwable) {
            $mysqlVersion = null;
        }

        try {
            $data = [
                'instance_id'   => $this->getInstanceId(),
                'revision'      => Version::getRevision(),
                'version'       => Version::getVersion(),
                'major_version' => Version::getMajorVersion(),
                'php_version'   => PHP_VERSION,
                'db_version'    => $mysqlVersion,
                'bundles'       => array_keys($kernel->getBundles()),
            ];
        } catch (Throwable) {
            $data = [];
        }

        try {
            $this->httpClient->request(
                'POST',
                'https://metrics.opendxp.io/statistics',
                [
                    'json' => $data,
                ]
            );
        } catch (GuzzleException) {
            // fail silently
        }

        return $this->adminJson([
            'success' => true,
        ]);
    }

    protected function addRuntimePerspective(array &$templateParams, User $user): static
    {
        $runtimePerspective = \OpenDxp\Bundle\AdminBundle\Perspective\Config::getRuntimePerspective($user);
        $templateParams['runtimePerspective'] = $runtimePerspective;

        return $this;
    }

    protected function addPluginAssets(OpenDxpBundleManager $bundleManager, array &$templateParams): static
    {
        $templateParams['pluginJsPaths'] = $bundleManager->getJsPaths();
        $templateParams['pluginCssPaths'] = $bundleManager->getCssPaths();

        return $this;
    }

    protected function setAdminLanguage(Request $request, User $user): static
    {
        // set user language
        $request->setLocale($user->getLanguage());
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($user->getLanguage());
        }

        return $this;
    }

    protected function buildOpenDxpSettings(
        Request $request,
        array &$templateParams,
        User $user, KernelInterface $kernel,
        ExecutorInterface $maintenanceExecutor,
        CsrfProtectionHandler $csrfProtection,
        Tool\MaintenanceModeHelperInterface $maintenanceModeHelper
    ): static {
        $config = $templateParams['config'];
        $systemSettings = $templateParams['systemSettings'];
        $adminSettings = $templateParams['adminSettings'];
        $requiredLanguages = $systemSettings['general']['valid_languages'];
        $dashboardHelper = new Dashboard($user);
        $customAdminEntrypoint = $this->getParameter('opendxp_admin.custom_admin_route_name');

        try {
            $adminEntrypointUrl = $this->generateUrl($customAdminEntrypoint, [], UrlGeneratorInterface::ABSOLUTE_URL);
        } catch (Exception) {
            // if the custom admin entrypoint is not defined, return null in the settings
            $adminEntrypointUrl = null;
        }

        if (array_key_exists('required_languages', $systemSettings['general'])) {
            $requiredLanguages = $systemSettings['general']['required_languages'];
        }

        $settings = [
            'instanceId'          => $this->getInstanceId(),
            'version'             => Version::getVersion(),
            'build'               => Version::getRevision(),
            'debug'               => OpenDxp::inDebugMode(),
            'devmode'             => OpenDxp::inDevMode(),
            'disableMinifyJs'     => OpenDxp::disableMinifyJs(),
            'environment'         => $kernel->getEnvironment(),
            'sessionId'           => htmlentities($request->getSession()->getId(), ENT_QUOTES, 'UTF-8'),

            // languages
            'language'         => $request->getLocale(),
            'websiteLanguages' => Admin::reorderWebsiteLanguages(
                $this->getAdminUser(),
                $systemSettings['general']['valid_languages'],
                true
            ),
            'requiredLanguages' => $requiredLanguages,

            // flags
            'showCloseConfirmation'          => true,
            'debug_admin_translations'       => (bool)$systemSettings['general']['debug_admin_translations'],
            'document_generatepreviews'      => (bool)$config['documents']['generate_preview'],
            'asset_disable_tree_preview'     => (bool)$adminSettings['assets']['disable_tree_preview'],
            'asset_hide_edit'                => (bool)$adminSettings['assets']['hide_edit_image'],
            'asset_tree_paging_limit'        => $config['assets']['tree_paging_limit'],
            'asset_default_upload_path'      => $config['assets']['default_upload_path'],
            'chromium'                       => HtmlToImage::isSupported(),
            'videoconverter'                 => Video::isAvailable(),
            'main_domain'                    => $systemSettings['general']['domain'],
            'custom_admin_entrypoint_url'    => $adminEntrypointUrl,
            'timezone'                       => $config['general']['timezone'] ?: date_default_timezone_get(),
            'tile_layer_url_template'        => $config['maps']['tile_layer_url_template'],
            'geocoding_url_template'         => $config['maps']['geocoding_url_template'],
            'reverse_geocoding_url_template' => $config['maps']['reverse_geocoding_url_template'],
            'document_tree_paging_limit'     => $config['documents']['tree_paging_limit'],
            'object_tree_paging_limit'       => $config['objects']['tree_paging_limit'],
            'hostname'                       => htmlentities(\OpenDxp\Tool::getHostname(), ENT_QUOTES, 'UTF-8'),
            'dependency'                     => $config['dependency']['enabled'],

            'document_auto_save_interval' => $config['documents']['auto_save_interval'],
            'object_auto_save_interval'   => $config['objects']['auto_save_interval'],

            // perspective and portlets
            'perspective'           => $templateParams['runtimePerspective'],
            'availablePerspectives' => \OpenDxp\Bundle\AdminBundle\Perspective\Config::getAvailablePerspectives($user),
            'disabledPortlets'      => $dashboardHelper->getDisabledPortlets(),

            // this stuff is used to decide whether the "add" button should be grayed out or not
            'image-thumbnails-writeable'          => (new Asset\Image\Thumbnail\Config())->isWriteable(),
            'video-thumbnails-writeable'          => (new Asset\Video\Thumbnail\Config())->isWriteable(),
            'document-types-writeable'            => (new DocType())->isWriteable(),
            'predefined-properties-writeable'     => (new Predefined())->isWriteable(),
            'predefined-asset-metadata-writeable' => (new \OpenDxp\Model\Metadata\Predefined())->isWriteable(),
            'perspectives-writeable'              => \OpenDxp\Bundle\AdminBundle\Perspective\Config::isWriteable(),
            'custom-views-writeable'              => \OpenDxp\Bundle\AdminBundle\CustomView\Config::isWriteable(),
            'class-definition-writeable'          => !isset($_SERVER['OPENDXP_CLASS_DEFINITION_WRITABLE']) ||
                (bool) $_SERVER['OPENDXP_CLASS_DEFINITION_WRITABLE'],
            'object-custom-layout-writeable' => (new CustomLayout())->isWriteable(),
            'select-options-writeable' => (new \OpenDxp\Model\DataObject\SelectOptions\Config())->isWriteable(),

            // search types
            'asset_search_types' => Asset::getTypes(),

            // document types
            'document_types_configuration' => Document::getTypesConfiguration(),
            'document_search_types' => Document::getTypes(),
            'document_valid_types' => array_values(array_filter(Document::getTypes(), fn ($type) => $type !== 'folder')),
            // email search compatible document types
            'document_email_search_types' => $config['documents']['email_search'],
            'select_options_provider_class' => SelectOptionsOptionsProvider::class,
        ];

        $this
            ->addSystemVarSettings($settings)
            ->addMaintenanceSettings($settings, $maintenanceExecutor, $maintenanceModeHelper)
            ->addMailSettings($settings, $config, $systemSettings)
            ->addCustomViewSettings($settings)
            ->addNotificationSettings($settings, $config);

        $settings['csrfToken'] = $csrfProtection->getCsrfToken($request->getSession());

        $templateParams['settings'] = $settings;

        return $this;
    }

    private function getInstanceId(): string
    {
        $instanceId = 'not-set';

        try {
            $instanceId = $this->getParameter('secret');
            $instanceId = sha1(substr($instanceId, 3, -3));
        } catch (Exception) {
            // nothing to do
        }

        return $instanceId;
    }

    protected function addSystemVarSettings(array &$settings): static
    {
        // upload limit
        $max_upload = filesize2bytes(ini_get('upload_max_filesize') . 'B');
        $max_post = filesize2bytes(ini_get('post_max_size') . 'B');
        $upload_mb = min($max_upload, $max_post) ?: $max_upload;

        $settings['upload_max_filesize'] = (int) $upload_mb;

        // session lifetime (gc)
        $session_gc_maxlifetime = ini_get('session.gc_maxlifetime');
        if (empty($session_gc_maxlifetime)) {
            $session_gc_maxlifetime = 120;
        }

        $settings['session_gc_maxlifetime'] = (int)$session_gc_maxlifetime;

        return $this;
    }

    protected function addMaintenanceSettings(
        array &$settings,
        ExecutorInterface $maintenanceExecutor,
        Tool\MaintenanceModeHelperInterface $maintenanceModeHelper
    ): static {
        // check maintenance
        $maintenance_active = false;
        // maintenance script should run at least every hour + a little tolerance
        if (($lastExecution = $maintenanceExecutor->getLastExecution()) && time() - $lastExecution < 3660) {
            $maintenance_active = true;
        }

        $settings['maintenance_active'] = $maintenance_active;
        $settings['maintenance_mode'] = $maintenanceModeHelper->isActive();

        return $this;
    }

    protected function addMailSettings(array &$settings, Config $config, array $systemSettings): static
    {
        //mail settings
        $mailIncomplete = false;
        if (isset($config['email']) && $systemSettings['email']) {
            if (OpenDxp::inDebugMode() && empty($systemSettings['email']['debug']['email_addresses'])) {
                $mailIncomplete = true;
            }
            if (empty($config['email']['sender']['email'])) {
                $mailIncomplete = true;
            }
        }

        $settings['mail'] = !$mailIncomplete;
        $settings['mailDefaultAddress'] = $config['email']['sender']['email'] ?? null;

        return $this;
    }

    protected function addCustomViewSettings(array &$settings): static
    {
        $cvData = [];

        // still needed when publishing objects
        $cvConfig = \OpenDxp\Bundle\AdminBundle\CustomView\Config::get();

        foreach ($cvConfig as $node) {
            $tmpData = $node;
            // backwards compatibility
            $treeType = $tmpData['treetype'] ?: 'object';
            $rootNode = Service::getElementByPath($treeType, $tmpData['rootfolder']);

            if ($rootNode) {
                $tmpData['rootId'] = $rootNode->getId();
                $tmpData['allowedClasses'] = $tmpData['classes'] ?? null;
                $tmpData['showroot'] = (bool)$tmpData['showroot'];

                // Check if a user has privileges to that node
                if ($rootNode->isAllowed('list')) {
                    $cvData[] = $tmpData;
                }
            }
        }

        $settings['customviews'] = $cvData;

        return $this;
    }

    /**
     * @return $this
     */
    protected function addNotificationSettings(array &$settings, Config $config): static
    {
        $enabled = (bool)$config['notifications']['enabled'];

        $settings['notifications_enabled'] = $enabled;
        $settings['checknewnotification_enabled'] = $enabled && (bool) $config['notifications']['check_new_notification']['enabled'];

        // convert the config parameter interval (seconds) in milliseconds
        $settings['checknewnotification_interval'] = $config['notifications']['check_new_notification']['interval'] * 1000;

        return $this;
    }

    public function onKernelResponseEvent(ResponseEvent $event): void
    {
        $event->getResponse()->headers->set('X-Frame-Options', 'deny', true);
    }
}

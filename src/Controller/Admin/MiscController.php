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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetIconListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetJsonTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Bundle\AdminBundle\Tool as AdminTool;
use OpenDxp\Config;
use OpenDxp\Controller\Config\ControllerDataProvider;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Tool;
use OpenDxp\Tool\Storage;
use OpenDxp\Translation\Translator;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/misc')]
class MiscController extends AdminAbstractController
{
    #[Route('/get-available-controller-references', name: 'opendxp_admin_misc_getavailablecontroller_references', methods: ['GET'])]
    public function getAvailableControllerReferencesAction(ControllerDataProvider $provider): JsonResponse
    {
        $controllerReferences = $provider->getControllerReferences();

        $result = array_map(fn ($controller) => [
            'name' => $controller,
        ], $controllerReferences);

        return $this->adminJson(ApiResponse::ok(['data' => $result, 'total' => count($result)]));
    }

    #[Route('/get-available-templates', name: 'opendxp_admin_misc_getavailabletemplates', methods: ['GET'])]
    public function getAvailableTemplatesAction(ControllerDataProvider $provider): JsonResponse
    {
        $templates = $provider->getTemplates();

        sort($templates, SORT_NATURAL | SORT_FLAG_CASE);

        $result = array_map(static fn ($template) => [
            'path' => $template,
        ], $templates);

        return $this->adminJson([
            'data' => $result,
        ]);
    }

    #[Route('/json-translations-system', name: 'opendxp_admin_misc_jsontranslationssystem', methods: ['GET'])]
    public function jsonTranslationsSystemAction(
        GetJsonTranslationsHandler $getJsonTranslations,
        Translator $translator,
        #[MapQueryParameter] ?string $language = null,
    ): Response {
        $result = $getJsonTranslations($translator, $language);

        $response = new Response('opendxp.system_i18n = ' . $this->encodeJson($result->translations) . ';');
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    /**
     * @internal
     */
    #[Route('/script-proxy', name: 'opendxp_admin_misc_scriptproxy', methods: ['GET'])]
    public function scriptProxyAction(
        #[MapQueryParameter] ?string $storageFile = null,
    ): Response {
        if (!$storageFile) {
            throw new InvalidArgumentException('The parameter storageFile is required');
        }

        $fileExtension = pathinfo($storageFile, PATHINFO_EXTENSION);
        $storage = Storage::get('admin');
        $scriptsContent = $storage->read($storageFile);

        if (!empty($scriptsContent)) {
            $contentType = 'text/javascript';
            if ($fileExtension === 'css') {
                $contentType = 'text/css';
            }

            $lifetime = 86400;

            $response = new Response($scriptsContent);
            $response->headers->set('Cache-Control', 'max-age=' . $lifetime);
            $response->headers->set('Pragma', '');
            $response->headers->set('Content-Type', $contentType);
            $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');

            return $response;
        }

        throw $this->createNotFoundException('Scripts not found');
    }

    #[Route('/admin-css', name: 'opendxp_admin_misc_admincss', methods: ['GET'])]
    public function adminCssAction(Config $config): Response
    {
        // customviews config
        $cvData = \OpenDxp\Bundle\AdminBundle\CustomView\Config::get();

        // languages
        $languages = \OpenDxp\Tool::getValidLanguages();
        $adminLanguages = \OpenDxp\Tool\Admin::getLanguages();
        $languages = array_unique([...$languages, ...$adminLanguages]);

        $response = $this->render('@OpenDxpAdmin/admin/misc/admin_css.html.twig', [
            'customviews' => $cvData,
            'adminSettings' => AdminConfig::get(),
            'languages' => $languages,
        ]);
        $response->headers->set('Content-Type', 'text/css; charset=UTF-8');

        return $response;
    }

    #[Route('/ping', name: 'opendxp_admin_misc_ping', methods: ['GET'])]
    public function pingAction(): JsonResponse
    {
        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/available-languages', name: 'opendxp_admin_misc_availablelanguages', methods: ['GET'])]
    public function availableLanguagesAction(): Response
    {
        $locales = Tool::getSupportedLocales();
        $response = new Response('opendxp.available_languages = ' . $this->encodeJson($locales) . ';');
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    #[Route('/get-valid-filename', name: 'opendxp_admin_misc_getvalidfilename', methods: ['GET'])]
    public function getValidFilenameAction(
        #[MapQueryParameter] ?string $value = null,
        #[MapQueryParameter] ?string $type = null,
    ): JsonResponse {
        return $this->adminJson([
            'filename' => \OpenDxp\Model\Element\Service::getValidKey($value, $type),
        ]);
    }

    #[IsGranted(CorePermission::MaintenanceMode->value)]
    #[Route('/maintenance', name: 'opendxp_admin_misc_maintenance', methods: ['POST'])]
    public function maintenanceAction(
        Request $request,
        Tool\MaintenanceModeHelperInterface $maintenanceModeHelper,
        #[MapQueryParameter] ?string $activate = null,
        #[MapQueryParameter] ?string $deactivate = null,
    ): JsonResponse {

        if ($activate) {
            $maintenanceModeHelper->activate($request->getSession()->getId());
        }

        if ($deactivate) {
            $maintenanceModeHelper->deactivate();
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/country-list', name: 'opendxp_admin_misc_countrylist', methods: ['GET'])]
    public function countryListAction(LocaleServiceInterface $localeService): JsonResponse
    {
        $countries = $localeService->getDisplayRegions();
        asort($countries);
        $options = [];

        foreach ($countries as $short => $translation) {
            if (strlen($short) === 2) {
                $options[] = [
                    'name' => $translation,
                    'code' => $short,
                ];
            }
        }

        return $this->adminJson(['data' => $options]);
    }

    #[Route('/language-list', name: 'opendxp_admin_misc_languagelist', methods: ['GET'])]
    public function languageListAction(): JsonResponse
    {
        $locales = Tool::getSupportedLocales();
        $options = [];

        foreach ($locales as $short => $translation) {
            $options[] = [
                'name' => $translation,
                'code' => $short,
            ];
        }

        return $this->adminJson(['data' => $options]);
    }

    #[Route('/get-language-flag', name: 'opendxp_admin_misc_getlanguageflag', methods: ['GET'])]
    public function getLanguageFlagAction(
        #[MapQueryParameter] ?string $language = null,
    ): BinaryFileResponse {
        $iconPath = AdminTool::getLanguageFlagFile($language);

        $response = new BinaryFileResponse($iconPath);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    #[Route('/icon-list', name: 'opendxp_admin_misc_iconlist', methods: ['GET'])]
    public function iconListAction(
        GetIconListHandler $getIconList,
        ?Profiler $profiler,
        #[MapQueryParameter] ?string $type = null,
    ): Response {
        if ($profiler) {
            $profiler->disable();
        }

        if ($type === null) {
            return $this->render('@OpenDxpAdmin/admin/misc/icon_library_reload.html.twig');
        }

        $result = $getIconList($type);

        return $this->render('@OpenDxpAdmin/admin/misc/icon_list.html.twig', [
            'icons' => $result->icons,
            'iconsCss' => $result->iconsCss,
            'type' => $result->type,
            'extraInfo' => $result->extraInfo,
            'source' => $result->source,
        ]);
    }

    #[Route('/test', name: 'opendxp_admin_misc_test')]
    public function testAction(): Response
    {
        return new Response('done');
    }
}

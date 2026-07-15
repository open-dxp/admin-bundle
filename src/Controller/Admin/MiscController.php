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

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\AdminCss\AdminCssHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetAvailableControllerReferences\GetAvailableControllerReferencesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetAvailableLanguages\GetAvailableLanguagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetAvailableTemplates\GetAvailableTemplatesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetCountryList\GetCountryListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetIconList\GetIconListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetIconList\GetIconListPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetJsonTranslations\GetJsonTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetJsonTranslations\GetJsonTranslationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetLanguageFlag\GetLanguageFlagHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetLanguageFlag\GetLanguageFlagPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetLanguageList\GetLanguageListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetValidFilename\GetValidFilenameHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\GetValidFilename\GetValidFilenamePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\Maintenance\MaintenanceHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\Maintenance\MaintenancePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\ScriptProxy\ScriptProxyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Misc\ScriptProxy\ScriptProxyPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profiler;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/misc')]
class MiscController extends AdminAbstractController
{
    #[Route('/get-available-controller-references', name: 'opendxp_admin_misc_getavailablecontroller_references', methods: ['GET'])]
    public function getAvailableControllerReferencesAction(
        GetAvailableControllerReferencesHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler());
    }

    #[Route('/get-available-templates', name: 'opendxp_admin_misc_getavailabletemplates', methods: ['GET'])]
    public function getAvailableTemplatesAction(
        GetAvailableTemplatesHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }

    #[Route('/json-translations-system', name: 'opendxp_admin_misc_jsontranslationssystem', methods: ['GET'])]
    public function jsonTranslationsSystemAction(
        GetJsonTranslationsPayload $payload,
        GetJsonTranslationsHandler $handler,
    ): Response {
        $result = $handler($payload);

        $response = new Response('opendxp.system_i18n = ' . $this->encodeJson($result->translations) . ';');
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    /**
     * @internal
     */
    #[Route('/script-proxy', name: 'opendxp_admin_misc_scriptproxy', methods: ['GET'])]
    public function scriptProxyAction(
        ScriptProxyPayload $payload,
        ScriptProxyHandler $handler,
    ): Response {
        $result = $handler($payload);

        $lifetime = 86400;

        $response = new Response($result->content);
        $response->headers->set('Cache-Control', 'max-age=' . $lifetime);
        $response->headers->set('Pragma', '');
        $response->headers->set('Content-Type', $result->contentType);
        $response->headers->set('Expires', gmdate('D, d M Y H:i:s', time() + $lifetime) . ' GMT');

        return $response;
    }

    #[Route('/admin-css', name: 'opendxp_admin_misc_admincss', methods: ['GET'])]
    public function adminCssAction(
        AdminCssHandler $handler,
    ): Response {
        $result = $handler();

        $response = $this->render('@OpenDxpAdmin/admin/misc/admin_css.html.twig', [
            'customviews'   => $result->customviews,
            'adminSettings' => $result->adminSettings,
            'languages'     => $result->languages,
        ]);

        $response->headers->set('Content-Type', 'text/css; charset=UTF-8');

        return $response;
    }

    #[Route('/available-languages', name: 'opendxp_admin_misc_availablelanguages', methods: ['GET'])]
    public function availableLanguagesAction(
        GetAvailableLanguagesHandler $handler,
    ): Response {
        $result = $handler();

        $response = new Response('opendxp.available_languages = ' . $this->encodeJson($result->locales) . ';');
        $response->headers->set('Content-Type', 'text/javascript');

        return $response;
    }

    #[Route('/get-valid-filename', name: 'opendxp_admin_misc_getvalidfilename', methods: ['GET'])]
    public function getValidFilenameAction(
        GetValidFilenamePayload $payload,
        GetValidFilenameHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload), envelope: false);
    }

    #[IsGranted(CorePermission::MaintenanceMode->value)]
    #[SessionIdentityAware]
    #[Route('/maintenance', name: 'opendxp_admin_misc_maintenance', methods: ['POST'])]
    public function maintenanceAction(
        MaintenancePayload $payload,
        MaintenanceHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/country-list', name: 'opendxp_admin_misc_countrylist', methods: ['GET'])]
    public function countryListAction(
        GetCountryListHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }

    #[Route('/language-list', name: 'opendxp_admin_misc_languagelist', methods: ['GET'])]
    public function languageListAction(
        GetLanguageListHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }

    #[Route('/get-language-flag', name: 'opendxp_admin_misc_getlanguageflag', methods: ['GET'])]
    public function getLanguageFlagAction(
        GetLanguageFlagPayload $payload,
        GetLanguageFlagHandler $handler,
    ): BinaryFileResponse {
        $result = $handler($payload);

        $response = new BinaryFileResponse($result->iconPath);
        $response->headers->set('Content-Type', 'image/svg+xml');

        return $response;
    }

    #[Route('/icon-list', name: 'opendxp_admin_misc_iconlist', methods: ['GET'])]
    public function iconListAction(
        GetIconListPayload $payload,
        GetIconListHandler $handler,
        ?Profiler $profiler,
    ): Response {
        if ($profiler) {
            $profiler->disable();
        }

        if ($payload->type === null) {
            return $this->render('@OpenDxpAdmin/admin/misc/icon_library_reload.html.twig');
        }

        $result = $handler($payload);

        return $this->render('@OpenDxpAdmin/admin/misc/icon_list.html.twig', [
            'icons'     => $result->icons,
            'iconsCss'  => $result->iconsCss,
            'type'      => $result->type,
            'extraInfo' => $result->extraInfo,
            'source'    => $result->source,
        ]);
    }

    #[Route('/ping', name: 'opendxp_admin_misc_ping', methods: ['GET'])]
    public function pingAction(): JsonResponse
    {
        return $this->apiOk();
    }

    #[Route('/test', name: 'opendxp_admin_misc_test')]
    public function testAction(): Response
    {
        return new Response('done');
    }
}

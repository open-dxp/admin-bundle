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

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearOpenDxpCacheHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearOutputCacheHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearSymfonyCacheHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearTemporaryFilesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\CreatePredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\CreatePredefinedPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\CreateWebsiteSettingHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteCustomLogoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeletePredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeletePredefinedPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteWebsiteSettingHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\PredefinedMetadataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\PredefinedPropertyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\WebsiteSettingPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DisplayCustomLogoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAppearanceSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableAdminLanguagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableAlgorithmsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableCountriesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableSitesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetFilteredPredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetPredefinedMetadataListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetPredefinedPropertiesListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetSystemSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetWebsiteSettingsListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\SaveAppearanceSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\SaveSystemSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ThumbnailAdapterCheckHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdatePredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdatePredefinedPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateWebsiteSettingHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UploadCustomLogoHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Logger;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\KernelInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/settings')]
class SettingsController extends AdminAbstractController
{
    #[Route('/display-custom-logo', name: 'opendxp_settings_display_custom_logo', methods: ['GET'])]
    public function displayCustomLogoAction(Request $request, DisplayCustomLogoHandler $handler): StreamedResponse
    {
        $result = $handler(white: $request->query->has('white'));

        return new StreamedResponse(static function () use ($result): void {
            fpassthru($result->stream);
        }, 200, [
            'Content-Type' => $result->mime,
            'Content-Security-Policy' => "script-src 'none'",
        ]);
    }

    #[Route('/upload-custom-logo', name: 'opendxp_admin_settings_uploadcustomlogo', methods: ['POST'])]
    public function uploadCustomLogoAction(Request $request, UploadCustomLogoHandler $handler): JsonResponse
    {
        /** @var UploadedFile $logoFile */
        $logoFile = $request->files->get('Filedata');

        if (!$logoFile instanceof UploadedFile) {
            throw new BadRequestHttpException('No file uploaded.');
        }

        $handler($logoFile->getPathname(), $logoFile->guessExtension() ?? '');

        $response = $this->adminJson(ApiResponse::ok());
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/delete-custom-logo', name: 'opendxp_admin_settings_deletecustomlogo', methods: ['DELETE'])]
    public function deleteCustomLogoAction(DeleteCustomLogoHandler $handler): JsonResponse
    {
        $handler();

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::AssetMetadata->value)]
    #[Route('/predefined-metadata', name: 'opendxp_admin_settings_metadata', methods: ['POST'])]
    public function metadataAction(
        PredefinedMetadataPayload $payload,
        GetPredefinedMetadataListHandler $getPredefinedMetadataList,
        CreatePredefinedMetadataHandler $createPredefinedMetadata,
        UpdatePredefinedMetadataHandler $updatePredefinedMetadata,
        DeletePredefinedMetadataHandler $deletePredefinedMetadata,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->destroyPredefinedMetadata($deletePredefinedMetadata, $payload),
                'update'  => $this->adminJson(ApiResponse::ok(['data' => $updatePredefinedMetadata($payload)->data])),
                'create'  => $this->adminJson(ApiResponse::ok(['data' => $createPredefinedMetadata($payload)->data])),
                default   => throw new BadRequestHttpException(),
            };
        }

        $result = $getPredefinedMetadataList($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    private function destroyPredefinedMetadata(DeletePredefinedMetadataHandler $handler, PredefinedMetadataPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[Route('/get-predefined-metadata', name: 'opendxp_admin_settings_getpredefinedmetadata', methods: ['GET'])]
    public function getPredefinedMetadataAction(
        GetFilteredPredefinedMetadataHandler $handler,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?string $subType = null,
        #[MapQueryParameter] ?string $group = null,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $handler($type, $subType, $group)->data]));
    }

    #[IsGranted(CorePermission::PredefinedProperties->value)]
    #[Route('/properties', name: 'opendxp_admin_settings_properties', methods: ['POST'])]
    public function propertiesAction(
        PredefinedPropertyPayload $payload,
        GetPredefinedPropertiesListHandler $getPredefinedPropertiesList,
        CreatePredefinedPropertyHandler $createPredefinedProperty,
        UpdatePredefinedPropertyHandler $updatePredefinedProperty,
        DeletePredefinedPropertyHandler $deletePredefinedProperty,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->destroyPredefinedProperty($deletePredefinedProperty, $payload),
                'update'  => $this->adminJson(ApiResponse::ok(['data' => $updatePredefinedProperty($payload)->data])),
                'create'  => $this->adminJson(ApiResponse::ok(['data' => $createPredefinedProperty($payload)->data])),
                default   => throw new BadRequestHttpException(),
            };
        }

        $result = $getPredefinedPropertiesList($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    private function destroyPredefinedProperty(DeletePredefinedPropertyHandler $handler, PredefinedPropertyPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[IsGranted(AdminPermission::SystemAppearance->value)]
    #[Route('/get-admin-system', name: 'opendxp_appearance_admin_settings_get', methods: ['GET'])]
    public function getAppearanceSystemAction(GetAppearanceSettingsHandler $handler): JsonResponse
    {
        return $this->adminJson(['values' => $handler()->values]);
    }

    #[IsGranted(CorePermission::SystemSettings->value)]
    #[Route('/get-system', name: 'opendxp_admin_settings_getsystem', methods: ['GET'])]
    public function getSystemAction(GetSystemSettingsHandler $handler): JsonResponse
    {
        $result = $handler();

        return $this->adminJson([
            'values' => $result->values,
            'config' => ['languages' => $result->languages],
        ]);
    }

    #[IsGranted(AdminPermission::SystemAppearance->value)]
    #[Route('/set-appearance', name: 'opendxp_admin_settings_appearance_set', methods: ['PUT'])]
    public function setAppearanceSystemAction(
        Request $request,
        KernelInterface $kernel,
        SaveAppearanceSettingsHandler $handler,
    ): JsonResponse {
        $handler(
            values: $this->decodeJson($request->request->get('data')),
            env: $request->request->get('env', $kernel->getEnvironment()),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::SystemSettings->value)]
    #[Route('/set-system', name: 'opendxp_admin_settings_setsystem', methods: ['PUT'])]
    public function setSystemAction(
        Request $request,
        KernelInterface $kernel,
        SaveSystemSettingsHandler $handler,
    ): JsonResponse {
        $handler(
            values: $this->decodeJson($request->request->get('data')),
            env: $request->request->get('env', $kernel->getEnvironment()),
        );

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(new Expression('is_granted("clear_cache") or is_granted("system_settings")'))]
    #[Route('/clear-cache', name: 'opendxp_admin_settings_clearcache', methods: ['DELETE'])]
    public function clearCacheAction(
        Request $request,
        KernelInterface $kernel,
        ClearOpenDxpCacheHandler $clearOpenDxpCache,
        ClearSymfonyCacheHandler $clearSymfonyCache,
    ): JsonResponse {
        $shouldClearOpenDxp = !(bool) $request->request->get('only_symfony_cache');
        $shouldClearSymfony = !(bool) $request->request->get('only_opendxp_cache');

        if ($shouldClearOpenDxp) {
            $clearOpenDxpCache();
        }

        if ($shouldClearSymfony) {
            $clearSymfonyCache($request->request->get('env', $kernel->getEnvironment()));
        }

        $response = new JsonResponse(ApiResponse::ok());

        if ($shouldClearSymfony) {
            // send response before exit so the client gets a reply before the process terminates
            $response->sendHeaders();
            $response->sendContent();
            exit;
        }

        return $response;
    }

    #[IsGranted(CorePermission::ClearFullpageCache->value)]
    #[Route('/clear-output-cache', name: 'opendxp_admin_settings_clearoutputcache', methods: ['DELETE'])]
    public function clearOutputCacheAction(ClearOutputCacheHandler $handler): JsonResponse
    {
        $handler();

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::ClearTempFiles->value)]
    #[Route('/clear-temporary-files', name: 'opendxp_admin_settings_cleartemporaryfiles', methods: ['DELETE'])]
    public function clearTemporaryFilesAction(ClearTemporaryFilesHandler $handler): JsonResponse
    {
        $handler();

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/get-available-admin-languages', name: 'opendxp_admin_settings_getavailableadminlanguages', methods: ['GET'])]
    public function getAvailableAdminLanguagesAction(GetAvailableAdminLanguagesHandler $handler): JsonResponse
    {
        return $this->adminJson($handler()->langs);
    }

    #[Route('/get-available-sites', name: 'opendxp_admin_settings_getavailablesites', methods: ['GET'])]
    public function getAvailableSitesAction(
        GetAvailableSitesHandler $handler,
        #[MapQueryParameter] ?string $excludeMainSite = null,
    ): JsonResponse {
        try {
            $this->checkPermission('documents');
        } catch (AccessDeniedHttpException) {
            Logger::log('[Startup] Sites are not loaded as "documents" permission is missing');

            return $this->adminJson([]);
        }

        return $this->adminJson($handler(excludeMainSite: (bool) $excludeMainSite)->sites);
    }

    #[Route('/get-available-countries', name: 'opendxp_admin_settings_getavailablecountries', methods: ['GET'])]
    public function getAvailableCountriesAction(GetAvailableCountriesHandler $handler): JsonResponse
    {
        $result = $handler();

        return $this->adminJson(ApiResponse::ok(['data' => $result->options, 'total' => count($result->options)]));
    }

    #[Route('/thumbnail-adapter-check', name: 'opendxp_admin_settings_thumbnailadaptercheck', methods: ['GET'])]
    public function thumbnailAdapterCheckAction(ThumbnailAdapterCheckHandler $handler): Response
    {
        return new Response($handler()->content);
    }

    #[IsGranted(CorePermission::WebsiteSettings->value)]
    #[Route('/website-settings', name: 'opendxp_admin_settings_websitesettings', methods: ['POST'])]
    public function websiteSettingsAction(
        WebsiteSettingPayload $payload,
        GetWebsiteSettingsListHandler $getWebsiteSettingsList,
        CreateWebsiteSettingHandler $createWebsiteSetting,
        UpdateWebsiteSettingHandler $updateWebsiteSetting,
        DeleteWebsiteSettingHandler $deleteWebsiteSetting,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->destroyWebsiteSetting($deleteWebsiteSetting, $payload),
                'update'  => $this->adminJson(ApiResponse::ok(['data' => $updateWebsiteSetting($payload)->data])),
                'create'  => $this->adminJson(ApiResponse::ok(['data' => $createWebsiteSetting($payload)->data])),
                default   => throw new BadRequestHttpException(),
            };
        }

        $result = $getWebsiteSettingsList($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    private function destroyWebsiteSetting(DeleteWebsiteSettingHandler $handler, WebsiteSettingPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[Route('/get-available-algorithms', name: 'opendxp_admin_settings_getavailablealgorithms', methods: ['GET'])]
    public function getAvailableAlgorithmsAction(GetAvailableAlgorithmsHandler $handler): JsonResponse
    {
        $result = $handler();

        return $this->adminJson(ApiResponse::ok(['data' => $result->options, 'total' => count($result->options)]));
    }
}

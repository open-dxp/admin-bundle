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

use OpenDxp\Bundle\AdminBundle\Attribute\AsHtmlContentTypeResponse;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearCache\ClearCachePayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearCache\ClearCacheHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearOutputCache\ClearOutputCacheHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearTemporaryFiles\ClearTemporaryFilesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\CreatePredefinedMetadata\CreatePredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\CreatePredefinedProperty\CreatePredefinedPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\CreateWebsiteSetting\CreateWebsiteSettingHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteCustomLogo\DeleteCustomLogoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeletePredefinedMetadata\DeletePredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeletePredefinedProperty\DeletePredefinedPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DeleteWebsiteSetting\DeleteWebsiteSettingHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\PredefinedMetadataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\PredefinedPropertyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\WebsiteSettingPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DisplayCustomLogo\DisplayCustomLogoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\DisplayCustomLogo\DisplayCustomLogoPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAppearanceSettings\GetAppearanceSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableAdminLanguages\GetAvailableAdminLanguagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableAlgorithms\GetAvailableAlgorithmsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableCountries\GetAvailableCountriesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableSites\GetAvailableSitesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetFilteredPredefinedMetadata\GetFilteredPredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetPredefinedMetadataList\GetPredefinedMetadataListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetPredefinedPropertiesList\GetPredefinedPropertiesListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetSystemSettings\GetSystemSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\GetWebsiteSettingsList\GetWebsiteSettingsListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\SaveAppearanceSettings\SaveAppearanceSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\SaveSettingsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\SaveSystemSettings\SaveSystemSettingsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\ThumbnailAdapterCheck\ThumbnailAdapterCheckHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdatePredefinedMetadata\UpdatePredefinedMetadataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdatePredefinedProperty\UpdatePredefinedPropertyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UpdateWebsiteSetting\UpdateWebsiteSettingHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UploadCustomLogo\UploadCustomLogoHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\UploadCustomLogo\UploadCustomLogoPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Logger;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/settings')]
class SettingsController extends AdminAbstractController
{
    #[Route('/display-custom-logo', name: 'opendxp_settings_display_custom_logo', methods: ['GET'])]
    public function displayCustomLogoAction(DisplayCustomLogoPayload $payload, DisplayCustomLogoHandler $handler): StreamedResponse
    {
        $result = $handler($payload);

        return new StreamedResponse(static function () use ($result): void {
            fpassthru($result->stream);
        }, 200, [
            'Content-Type'            => $result->mime,
            'Content-Security-Policy' => "script-src 'none'",
        ]);
    }

    #[AsHtmlContentTypeResponse]
    #[Route('/upload-custom-logo', name: 'opendxp_admin_settings_uploadcustomlogo', methods: ['POST'])]
    public function uploadCustomLogoAction(UploadCustomLogoPayload $payload, UploadCustomLogoHandler $handler): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
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
        Request $request,
        PredefinedMetadataPayload $payload,
        GetPredefinedMetadataListHandler $handler,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->forward(self::class . '::metadataDestroyAction', [], $request->query->all()),
                'update'  => $this->forward(self::class . '::metadataUpdateAction', [], $request->query->all()),
                'create'  => $this->forward(self::class . '::metadataCreateAction', [], $request->query->all()),
                default   => throw new AdminOperationFailedException(''),
            };
        }

        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::AssetMetadata->value)]
    #[Route('/predefined-metadata-destroy', name: 'opendxp_admin_settings_metadata_destroy', methods: ['POST'])]
    public function metadataDestroyAction(
        PredefinedMetadataPayload $payload,
        DeletePredefinedMetadataHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[IsGranted(CorePermission::AssetMetadata->value)]
    #[Route('/predefined-metadata-update', name: 'opendxp_admin_settings_metadata_update', methods: ['POST'])]
    public function metadataUpdateAction(
        PredefinedMetadataPayload $payload,
        UpdatePredefinedMetadataHandler $handler,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
    }

    #[IsGranted(CorePermission::AssetMetadata->value)]
    #[Route('/predefined-metadata-create', name: 'opendxp_admin_settings_metadata_create', methods: ['POST'])]
    public function metadataCreateAction(
        PredefinedMetadataPayload $payload,
        CreatePredefinedMetadataHandler $handler,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
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
        Request $request,
        PredefinedPropertyPayload $payload,
        GetPredefinedPropertiesListHandler $handler,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->forward(self::class . '::propertiesDestroyAction', [], $request->query->all()),
                'update'  => $this->forward(self::class . '::propertiesUpdateAction', [], $request->query->all()),
                'create'  => $this->forward(self::class . '::propertiesCreateAction', [], $request->query->all()),
                default   => throw new AdminOperationFailedException(''),
            };
        }

        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::PredefinedProperties->value)]
    #[Route('/properties-destroy', name: 'opendxp_admin_settings_properties_destroy', methods: ['POST'])]
    public function propertiesDestroyAction(
        PredefinedPropertyPayload $payload,
        DeletePredefinedPropertyHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[IsGranted(CorePermission::PredefinedProperties->value)]
    #[Route('/properties-update', name: 'opendxp_admin_settings_properties_update', methods: ['POST'])]
    public function propertiesUpdateAction(
        PredefinedPropertyPayload $payload,
        UpdatePredefinedPropertyHandler $handler,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
    }

    #[IsGranted(CorePermission::PredefinedProperties->value)]
    #[Route('/properties-create', name: 'opendxp_admin_settings_properties_create', methods: ['POST'])]
    public function propertiesCreateAction(
        PredefinedPropertyPayload $payload,
        CreatePredefinedPropertyHandler $handler,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
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
        SaveSettingsPayload $payload,
        SaveAppearanceSettingsHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(CorePermission::SystemSettings->value)]
    #[Route('/set-system', name: 'opendxp_admin_settings_setsystem', methods: ['PUT'])]
    public function setSystemAction(
        SaveSettingsPayload $payload,
        SaveSystemSettingsHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok());
    }

    #[IsGranted(new Expression(
        'is_granted("' . CorePermission::ClearCache->value . '") or is_granted("' . CorePermission::SystemSettings->value . '")'
    ))]
    #[Route('/clear-cache', name: 'opendxp_admin_settings_clearcache', methods: ['DELETE'])]
    public function clearCacheAction(
        ClearCachePayload $payload,
        ClearCacheHandler $handler,
    ): JsonResponse {
        $handler($payload);

        $response = new JsonResponse(ApiResponse::ok());

        if (!$payload->onlyOpendxpCache) {
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
        Request $request,
        WebsiteSettingPayload $payload,
        GetWebsiteSettingsListHandler $handler,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->forward(self::class . '::websiteSettingsDestroyAction', [], $request->query->all()),
                'update'  => $this->forward(self::class . '::websiteSettingsUpdateAction', [], $request->query->all()),
                'create'  => $this->forward(self::class . '::websiteSettingsCreateAction', [], $request->query->all()),
                default   => throw new AdminOperationFailedException(''),
            };
        }

        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[IsGranted(CorePermission::WebsiteSettings->value)]
    #[Route('/website-settings-destroy', name: 'opendxp_admin_settings_websitesettings_destroy', methods: ['POST'])]
    public function websiteSettingsDestroyAction(
        WebsiteSettingPayload $payload,
        DeleteWebsiteSettingHandler $handler,
    ): JsonResponse {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    #[IsGranted(CorePermission::WebsiteSettings->value)]
    #[Route('/website-settings-update', name: 'opendxp_admin_settings_websitesettings_update', methods: ['POST'])]
    public function websiteSettingsUpdateAction(
        WebsiteSettingPayload $payload,
        UpdateWebsiteSettingHandler $handler,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
    }

    #[IsGranted(CorePermission::WebsiteSettings->value)]
    #[Route('/website-settings-create', name: 'opendxp_admin_settings_websitesettings_create', methods: ['POST'])]
    public function websiteSettingsCreateAction(
        WebsiteSettingPayload $payload,
        CreateWebsiteSettingHandler $handler,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $handler($payload)->data]));
    }

    #[Route('/get-available-algorithms', name: 'opendxp_admin_settings_getavailablealgorithms', methods: ['GET'])]
    public function getAvailableAlgorithmsAction(GetAvailableAlgorithmsHandler $handler): JsonResponse
    {
        $result = $handler();

        return $this->adminJson(ApiResponse::ok(['data' => $result->options, 'total' => count($result->options)]));
    }
}

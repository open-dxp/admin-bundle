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
use OpenDxp\Bundle\AdminBundle\Attribute\SessionGatewayAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\AddAdminTranslationKeys\AddAdminTranslationKeysHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\AddAdminTranslationKeys\AddAdminTranslationKeysPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\BuildContentExportJobs\BuildContentExportJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\BuildContentExportJobs\BuildContentExportJobsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\CleanupTranslations\CleanupTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\CleanupTranslations\CleanupTranslationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\CreateTranslation\CreateTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\DeleteTranslation\DeleteTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\ExportTranslations\ExportTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\ExportTranslations\ExportTranslationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\GetTranslationDomains\GetTranslationDomainsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\GetTranslations\GetTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\GetWebsiteTranslationLanguages\GetWebsiteTranslationLanguagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\ImportTranslations\ImportTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\ImportTranslations\ImportTranslationsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\MergeTranslationItems\MergeTranslationItemsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\MergeTranslationItems\MergeTranslationItemsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\TranslationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\UpdateTranslation\UpdateTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\UploadTranslationImportFile\UploadTranslationImportFileHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\UploadTranslationImportFile\UploadTranslationImportFilePayload;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\TranslationImportSessionGateway;
use OpenDxp\Model\Translation;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

/**
 * @internal
 */
#[Route('/translation')]
class TranslationController extends AdminAbstractController
{
    #[AsHtmlContentTypeResponse]
    #[Route('/import', name: 'opendxp_admin_translation_import', methods: ['POST'])]
    #[SessionGatewayAware(TranslationImportSessionGateway::class)]
    public function importAction(
        ImportTranslationsHandler $handler,
        ImportTranslationsPayload $payload,
    ): JsonResponse {
        $this->checkPermission(($payload->domain === Translation::DOMAIN_ADMIN ? 'admin_' : '') . 'translations');

        return $this->apiJson($handler($payload), context: [AbstractObjectNormalizer::SKIP_NULL_VALUES => true]);
    }

    #[Route('/upload-import', name: 'opendxp_admin_translation_uploadimportfile', methods: ['POST'])]
    #[SessionGatewayAware(TranslationImportSessionGateway::class)]
    public function uploadImportFileAction(
        UploadTranslationImportFileHandler $handler,
        UploadTranslationImportFilePayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/export', name: 'opendxp_admin_translation_export', methods: ['GET'])]
    public function exportAction(
        ExportTranslationsHandler $handler,
        ExportTranslationsPayload $payload,
    ): Response {
        $this->checkPermission(($payload->domain === Translation::DOMAIN_ADMIN ? 'admin_' : '') . 'translations');

        $result = $handler($payload);

        $response = new Response("\xEF\xBB\xBF" . $result->csv);
        $response->headers->set('Content-Encoding', 'UTF-8');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename="export_' . $result->domain . '_translations.csv"');
        ini_set('display_errors', '0'); //to prevent warning messages in csv

        return $response;
    }

    #[Route('/add-admin-translation-keys', name: 'opendxp_admin_translation_addadmintranslationkeys', methods: ['POST'])]
    public function addAdminTranslationKeysAction(
        AddAdminTranslationKeysHandler $handler,
        AddAdminTranslationKeysPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/translations', name: 'opendxp_admin_translation_translations', methods: ['POST'])]
    public function translationsAction(
        Request $request,
        TranslationPayload $payload,
        GetTranslationsHandler $handler,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        $this->checkPermission(($payload->domain === Translation::DOMAIN_ADMIN ? 'admin_' : '') . 'translations');

        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->forward(self::class . '::translationsDestroyAction', [], $request->query->all()),
                'update'  => $this->forward(self::class . '::translationsUpdateAction', [], $request->query->all()),
                'create'  => $this->forward(self::class . '::translationsCreateAction', [], $request->query->all()),
                default   => throw new BadRequestHttpException(),
            };
        }

        return $this->apiJson($handler($payload));
    }

    #[Route('/translations-destroy', name: 'opendxp_admin_translation_translations_destroy', methods: ['POST'])]
    public function translationsDestroyAction(
        TranslationPayload $payload,
        DeleteTranslationHandler $handler,
    ): JsonResponse {
        $this->checkPermission(($payload->domain === Translation::DOMAIN_ADMIN ? 'admin_' : '') . 'translations');

        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/translations-update', name: 'opendxp_admin_translation_translations_update', methods: ['POST'])]
    public function translationsUpdateAction(
        TranslationPayload $payload,
        UpdateTranslationHandler $handler,
    ): JsonResponse {
        $this->checkPermission(($payload->domain === Translation::DOMAIN_ADMIN ? 'admin_' : '') . 'translations');

        return $this->apiJson($handler($payload));
    }

    #[Route('/translations-create', name: 'opendxp_admin_translation_translations_create', methods: ['POST'])]
    public function translationsCreateAction(
        TranslationPayload $payload,
        CreateTranslationHandler $handler,
    ): JsonResponse {
        $this->checkPermission(($payload->domain === Translation::DOMAIN_ADMIN ? 'admin_' : '') . 'translations');

        return $this->apiJson($handler($payload));
    }

    #[Route('/cleanup', name: 'opendxp_admin_translation_cleanup', methods: ['DELETE'])]
    public function cleanupAction(
        CleanupTranslationsHandler $handler,
        CleanupTranslationsPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    /**
     * -----------------------------------------------------------------------------------
     * THE FOLLOWING ISN'T RELATED TO THE SHARED TRANSLATIONS OR ADMIN-TRANSLATIONS
     * XLIFF CONTENT-EXPORT & MS WORD CONTENT-EXPORT
     * -----------------------------------------------------------------------------------
     */
    #[Route('/content-export-jobs', name: 'opendxp_admin_translation_contentexportjobs', methods: ['POST'])]
    public function contentExportJobsAction(
        BuildContentExportJobsHandler $handler,
        BuildContentExportJobsPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[Route('/merge-item', name: 'opendxp_admin_translation_mergeitem', methods: ['PUT'])]
    public function mergeItemAction(
        MergeTranslationItemsHandler $handler,
        MergeTranslationItemsPayload $payload,
    ): JsonResponse {
        $handler($payload);

        return $this->apiOk();
    }

    #[Route('/get-website-translation-languages', name: 'opendxp_admin_translation_getwebsitetranslationlanguages', methods: ['GET'])]
    public function getWebsiteTranslationLanguagesAction(
        GetWebsiteTranslationLanguagesHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }

    #[Route('/get-translation-domains', name: 'opendxp_admin_translation_gettranslationdomains', methods: ['GET'])]
    public function getTranslationDomainsAction(
        GetTranslationDomainsHandler $handler,
    ): JsonResponse {
        return $this->apiJson($handler(), envelope: false);
    }
}

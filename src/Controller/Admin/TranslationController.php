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

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\AddAdminTranslationKeysHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\BuildContentExportJobsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\CleanupTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\CreateTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\DeleteTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\ExportTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\GetTranslationDomainsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\GetTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\GetWebsiteTranslationLanguagesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\ImportTranslationsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\MergeTranslationItemsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\TranslationPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\UpdateTranslationHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Translation\UploadTranslationImportFileHandler;
use OpenDxp\Model\Translation;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/translation')]
class TranslationController extends AdminAbstractController
{
    #[Route('/import', name: 'opendxp_admin_translation_import', methods: ['POST'])]
    public function importAction(
        Request $request,
        ImportTranslationsHandler $importTranslations,
        #[MapQueryParameter] ?string $merge = null,
    ): JsonResponse {
        $domain = $request->request->get('domain', Translation::DOMAIN_DEFAULT);
        $admin = $domain === Translation::DOMAIN_ADMIN;

        $dialect = $request->request->get('csvSettings');
        $session = Session::getSessionBag($request->getSession(), 'opendxp_importconfig');
        $tmpFile = $session->get('translation_import_file');

        if ($dialect) {
            $dialect = json_decode($dialect);
        }

        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

        $overwrite = !$merge;

        $flagUrlTemplate = $this->generateUrl('opendxp_admin_misc_getlanguageflag', ['language' => '{language}']);
        $flagUrlTemplate = str_replace('%7Blanguage%7D', '{language}', $flagUrlTemplate);

        $result = $importTranslations(
            tmpFile: $tmpFile,
            domain: $domain,
            overwrite: $overwrite,
            dialect: $dialect,
            enrichDelta: (bool) $merge,
            flagUrlTemplate: $flagUrlTemplate,
        );

        $extra = [];
        if ($merge) {
            $extra['delta'] = base64_encode(json_encode($result->delta));
        }

        $response = $this->adminJson(ApiResponse::ok($extra));
        // set content-type to text/html, otherwise (when application/json is sent) chrome will complain in
        // Ext.form.Action.Submit and mark the submission as failed
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/upload-import', name: 'opendxp_admin_translation_uploadimportfile', methods: ['POST'])]
    public function uploadImportFileAction(
        Request $request,
        UploadTranslationImportFileHandler $uploadTranslationImportFile,
    ): JsonResponse {
        $file = $request->files->get('Filedata');

        $result = $uploadTranslationImportFile($file);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($result): void {
            $session->set('translation_import_file', $result->importFile);
        }, 'opendxp_importconfig');

        return $this->adminJson(ApiResponse::ok(['config' => [
            'csvSettings' => $result->dialect,
        ]]));
    }

    #[Route('/export', name: 'opendxp_admin_translation_export', methods: ['GET'])]
    public function exportAction(
        ExportTranslationsHandler $exportTranslations,
        #[MapQueryParameter] ?string $domain = null,
        #[MapQueryParameter] ?string $filter = null,
        #[MapQueryParameter] ?string $searchString = null,
    ): Response {
        $admin = $domain === Translation::DOMAIN_ADMIN;

        $this->checkPermission(($admin ? 'admin_' : '') . 'translations');

        $result = $exportTranslations(
            domain: $domain,
            filter: $filter,
            searchString: $searchString,
            admin: $admin,
        );

        $response = new Response("\xEF\xBB\xBF" . $result->csv);
        $response->headers->set('Content-Encoding', 'UTF-8');
        $response->headers->set('Content-Type', 'text/csv; charset=UTF-8');
        $response->headers->set('Content-Disposition', 'attachment; filename: "export_' . $result->domain . '_translations.csv"');
        ini_set('display_errors', '0'); //to prevent warning messages in csv

        return $response;
    }

    #[Route('/add-admin-translation-keys', name: 'opendxp_admin_translation_addadmintranslationkeys', methods: ['POST'])]
    public function addAdminTranslationKeysAction(
        Request $request,
        AddAdminTranslationKeysHandler $addAdminTranslationKeys,
    ): JsonResponse {
        $keys = $request->request->get('keys');

        if ($keys) {
            $data = $this->decodeJson($keys);
            $addAdminTranslationKeys($data);
        }

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/translations', name: 'opendxp_admin_translation_translations', methods: ['POST'])]
    public function translationsAction(
        TranslationPayload $payload,
        DeleteTranslationHandler $deleteTranslation,
        UpdateTranslationHandler $updateTranslation,
        CreateTranslationHandler $createTranslation,
        GetTranslationsHandler $getTranslations,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        $this->checkPermission(($payload->domain === Translation::DOMAIN_ADMIN ? 'admin_' : '') . 'translations');

        if ($payload->hasData) {
            return match ($xaction) {
                'destroy' => $this->handleDestroyTranslation($deleteTranslation, $payload),
                'update'  => $this->handleUpdateTranslation($updateTranslation, $payload),
                'create'  => $this->handleCreateTranslation($createTranslation, $payload),
                default   => throw new BadRequestHttpException(),
            };
        }

        $result = $getTranslations($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->translations, 'total' => $result->total]));
    }

    private function handleDestroyTranslation(DeleteTranslationHandler $handler, TranslationPayload $payload): JsonResponse
    {
        $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => []]));
    }

    private function handleUpdateTranslation(UpdateTranslationHandler $handler, TranslationPayload $payload): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => [
            'key'              => $result->key,
            'creationDate'     => $result->creationDate,
            'modificationDate' => $result->modificationDate,
            'type'             => $result->type,
            ...$this->prefixTranslations($result->translations),
        ]]));
    }

    private function handleCreateTranslation(CreateTranslationHandler $handler, TranslationPayload $payload): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok(['data' => [
            'key'              => $result->key,
            'creationDate'     => $result->creationDate,
            'modificationDate' => $result->modificationDate,
            'type'             => $result->type,
            ...$this->prefixTranslations($result->translations),
        ]]));
    }

    protected function prefixTranslations(array $translations): array
    {
        $prefixedTranslations = [];
        foreach ($translations as $lang => $trans) {
            $prefixedTranslations['_' . $lang] = $trans;
        }

        return $prefixedTranslations;
    }

    #[Route('/cleanup', name: 'opendxp_admin_translation_cleanup', methods: ['DELETE'])]
    public function cleanupAction(
        Request $request,
        CleanupTranslationsHandler $cleanupTranslations,
    ): JsonResponse {
        $domain = $request->request->get('domain', Translation::DOMAIN_DEFAULT);

        $cleanupTranslations($domain);

        return $this->adminJson(ApiResponse::ok());
    }

    /**
     * -----------------------------------------------------------------------------------
     * THE FOLLOWING ISN'T RELATED TO THE SHARED TRANSLATIONS OR ADMIN-TRANSLATIONS
     * XLIFF CONTENT-EXPORT & MS WORD CONTENT-EXPORT
     * -----------------------------------------------------------------------------------
     */
    #[Route('/content-export-jobs', name: 'opendxp_admin_translation_contentexportjobs', methods: ['POST'])]
    public function contentExportJobsAction(
        Request $request,
        BuildContentExportJobsHandler $buildContentExportJobs,
    ): JsonResponse {
        $data = $this->decodeJson($request->request->get('data'));
        $source = str_replace('_', '-', $request->request->get('source', ''));
        $target = str_replace('_', '-', $request->request->get('target', ''));
        $type = $request->request->get('type');
        $jobUrl = $request->request->get('job_url', $request->getBaseUrl() . '/admin/translation/' . $type . '-export');

        $elementsPerJob = max(1, (int) $request->request->get('elements_per_job', 10));

        $result = $buildContentExportJobs(
            data: $data && is_array($data) ? $data : [],
            source: $source,
            target: $target,
            jobUrl: $jobUrl,
            elementsPerJob: $elementsPerJob,
        );

        return $this->adminJson(ApiResponse::ok(['jobs' => $result->jobs, 'id' => $result->exportId]));
    }

    #[Route('/merge-item', name: 'opendxp_admin_translation_mergeitem', methods: ['PUT'])]
    public function mergeItemAction(
        Request $request,
        MergeTranslationItemsHandler $mergeTranslationItems,
    ): JsonResponse {
        $domain = $request->request->get('domain', Translation::DOMAIN_DEFAULT);
        $dataList = json_decode($request->request->get('data'), true);

        $mergeTranslationItems($dataList, $domain);

        return $this->adminJson(ApiResponse::ok());
    }

    #[Route('/get-website-translation-languages', name: 'opendxp_admin_translation_getwebsitetranslationlanguages', methods: ['GET'])]
    public function getWebsiteTranslationLanguagesAction(GetWebsiteTranslationLanguagesHandler $getWebsiteTranslationLanguages): JsonResponse
    {
        $result = $getWebsiteTranslationLanguages();

        return $this->adminJson([
            'view' => $result->view,
            //when no view language is defined, all languages are editable. if one view language is defined, it
            //may be possible that no edit language is set intentionally
            'edit' => $result->edit,
        ]);
    }

    #[Route('/get-translation-domains', name: 'opendxp_admin_translation_gettranslationdomains', methods: ['GET'])]
    public function getTranslationDomainsAction(GetTranslationDomainsHandler $getTranslationDomains): JsonResponse
    {
        $result = $getTranslationDomains();

        return $this->adminJson(['domains' => $result->domains]);
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\CopyDocument\CopyDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\CopyDocument\CopyDocumentPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\GetDocumentChildIds\GetDocumentChildIdsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\GetDocumentChildIds\GetDocumentChildIdsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\RewriteDocumentIds\RewriteDocumentIdsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\RewriteDocumentIds\RewriteDocumentIdsPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/document')]
#[IsGranted(CorePermission::Documents->value)]
class DocumentCopyController extends AdminAbstractController
{
    #[Route('/copy-info', name: 'opendxp_admin_document_document_copyinfo', methods: ['GET'])]
    public function copyInfoAction(
        GetDocumentChildIdsPayload $getChildIdsPayload,
        GetDocumentChildIdsHandler $getChildIds,
        Request $request,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] ?string $targetId = null,
        #[MapQueryParameter] ?string $language = null,
        #[MapQueryParameter] ?string $enableInheritance = null,
    ): JsonResponse {
        $transactionId = time();
        $pasteJobs = [];

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($transactionId): void {
            $session->set((string) $transactionId, ['idMapping' => []]);
        }, 'opendxp_copy');

        $sourceId = $getChildIdsPayload->sourceId;

        if ($type === 'recursive' || $type === 'recursive-update-references') {
            $pasteJobs[] = [[
                'url' => $this->generateUrl('opendxp_admin_document_document_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $sourceId,
                    'targetId' => $targetId,
                    'type' => 'child',
                    'language' => $language,
                    'enableInheritance' => $enableInheritance,
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                    'resetIndex' => true,
                ],
            ]];

            $childIds = $getChildIds($getChildIdsPayload)->ids;

            foreach ($childIds as $id) {
                $pasteJobs[] = [[
                    'url' => $this->generateUrl('opendxp_admin_document_document_copy'),
                    'method' => 'POST',
                    'params' => [
                        'sourceId' => $id,
                        'targetParentId' => $targetId,
                        'sourceParentId' => $sourceId,
                        'type' => 'child',
                        'language' => $language,
                        'enableInheritance' => $enableInheritance,
                        'transactionId' => $transactionId,
                    ],
                ]];
            }

            if ($type === 'recursive-update-references') {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        'url' => $this->generateUrl('opendxp_admin_document_document_copyrewriteids'),
                        'method' => 'PUT',
                        'params' => [
                            'transactionId' => $transactionId,
                            'enableInheritance' => $enableInheritance,
                            '_dc' => uniqid('', false),
                        ],
                    ]];
                }
            }
        } elseif ($type === 'child' || $type === 'replace') {
            $pasteJobs[] = [[
                'url' => $this->generateUrl('opendxp_admin_document_document_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $sourceId,
                    'targetId' => $targetId,
                    'type' => $type,
                    'language' => $language,
                    'enableInheritance' => $enableInheritance,
                    'transactionId' => $transactionId,
                    'resetIndex' => ($type === 'child'),
                ],
            ]];
        }

        return $this->adminJson(['pastejobs' => $pasteJobs]);
    }

    #[Route('/copy-rewrite-ids', name: 'opendxp_admin_document_document_copyrewriteids', methods: ['PUT'])]
    public function copyRewriteIdsAction(
        RewriteDocumentIdsPayload $payload,
        RewriteDocumentIdsHandler $rewriteIds,
        Request $request,
    ): JsonResponse {
        $rewriteIds($payload);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($payload): void {
            $session->set($payload->transactionId, $payload->updatedIdStore);
        }, 'opendxp_copy');

        return $this->adminJson(ApiResponse::ok(['id' => $payload->documentId]));
    }

    #[Route('/copy', name: 'opendxp_admin_document_document_copy', methods: ['POST'])]
    public function copyAction(
        CopyDocumentPayload $payload,
        CopyDocumentHandler $copyDocument,
        Request $request,
    ): JsonResponse {
        $result = $copyDocument($payload);

        if ($result->newDocument !== null) {
            $sessionBag = $payload->sessionBag;
            $sessionBag['idMapping'][$result->sourceId] = $result->newDocument->getId();

            if ($payload->saveParentId) {
                $sessionBag['parentId'] = $result->newDocument->getId();
            }

            Session::getSessionBag($request->getSession(), 'opendxp_copy')->set($payload->transactionId, $sessionBag);
        }

        return $this->adminJson(ApiResponse::ok());
    }
}

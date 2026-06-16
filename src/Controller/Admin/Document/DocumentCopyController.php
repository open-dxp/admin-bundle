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
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\CopyDocumentHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\GetDocumentChildIdsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\RewriteDocumentIdsHandler;
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
        GetDocumentChildIdsHandler $getChildIds,
        Request $request,
        #[MapQueryParameter] ?string $type = null,
        #[MapQueryParameter] int $sourceId = 0,
        #[MapQueryParameter] ?string $targetId = null,
        #[MapQueryParameter] ?string $language = null,
        #[MapQueryParameter] ?string $enableInheritance = null,
    ): JsonResponse {
        $transactionId = time();
        $pasteJobs = [];

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($transactionId): void {
            $session->set((string) $transactionId, ['idMapping' => []]);
        }, 'opendxp_copy');

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

            $childIds = $getChildIds($sourceId)->ids;

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
    public function copyRewriteIdsAction(RewriteDocumentIdsHandler $rewriteIds, Request $request): JsonResponse
    {
        $transactionId = $request->request->get('transactionId');

        $idStore = Session::useBag($request->getSession(), static fn (AttributeBagInterface $session) => $session->get($transactionId), 'opendxp_copy');

        if (!array_key_exists('rewrite-stack', $idStore)) {
            $idStore['rewrite-stack'] = array_values($idStore['idMapping']);
        }

        $id = array_shift($idStore['rewrite-stack']);
        $enableInheritance = $request->request->get('enableInheritance') === 'true';

        $rewriteIds((int) $id, $idStore['idMapping'], $enableInheritance);

        Session::useBag($request->getSession(), static function (AttributeBagInterface $session) use ($transactionId, $idStore): void {
            $session->set($transactionId, $idStore);
        }, 'opendxp_copy');

        return $this->adminJson(ApiResponse::ok(['id' => $id]));
    }

    #[Route('/copy', name: 'opendxp_admin_document_document_copy', methods: ['POST'])]
    public function copyAction(CopyDocumentHandler $copyDocument, Request $request): JsonResponse
    {
        $sourceId = (int) $request->request->get('sourceId');
        $targetId = (int) $request->request->get('targetId');
        $type = (string) $request->request->get('type');

        $session = Session::getSessionBag($request->getSession(), 'opendxp_copy');
        $sessionBag = $session->get($request->request->get('transactionId'));

        $sourceParentId = $request->request->get('targetParentId') ? (int) $request->request->get('sourceParentId') : null;
        $targetParentId = $request->request->get('targetParentId') ? (int) $request->request->get('targetParentId') : null;
        $sessionParentId = !empty($sessionBag['parentId']) ? (int) $sessionBag['parentId'] : null;

        $enableInheritance = $request->request->get('enableInheritance') === 'true';
        $resetIndex = $request->request->get('resetIndex') === 'true';
        $language = ($request->request->get('language') ?: null);

        $result = $copyDocument(
            $sourceId,
            $targetId,
            $type,
            $sourceParentId,
            $targetParentId,
            $sessionParentId,
            $enableInheritance,
            $resetIndex,
            $language,
        );

        if ($result->newDocument !== null) {
            $sessionBag['idMapping'][$result->sourceId] = $result->newDocument->getId();

            if ($request->request->get('saveParentId')) {
                $sessionBag['parentId'] = $result->newDocument->getId();
            }

            $session->set($request->request->get('transactionId'), $sessionBag);
        }

        return $this->adminJson(ApiResponse::ok());
    }
}

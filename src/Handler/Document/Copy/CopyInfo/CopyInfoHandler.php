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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\CopyInfo;

use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\CopySessionGateway;
use OpenDxp\Model\Document;
use Symfony\Component\Routing\RouterInterface;

final class CopyInfoHandler
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly CopySessionGateway $copySession,
    ) {
    }

    public function __invoke(CopyInfoPayload $payload): CopyInfoResult
    {
        $transactionId = time();
        $this->copySession->startTransaction((string) $transactionId);
        $pasteJobs = [];

        if ($payload->type === 'recursive' || $payload->type === 'recursive-update-references') {
            $pasteJobs[] = [[
                'url' => $this->router->generate('opendxp_admin_document_document_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $payload->sourceId,
                    'targetId' => $payload->targetId,
                    'type' => 'child',
                    'language' => $payload->language,
                    'enableInheritance' => $payload->enableInheritance,
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                    'resetIndex' => true,
                ],
            ]];

            $document = Document::getById($payload->sourceId) ?? throw new DocumentNotFoundException($payload->sourceId);
            $childIds = [];

            if ($document->hasChildren()) {
                $list = new Document\Listing();
                $list->setCondition('`path` LIKE ?', [$list->escapeLike($document->getRealFullPath()) . '/%']);
                $list->setOrderKey('LENGTH(`path`)', false);
                $list->setOrder('ASC');
                $childIds = $list->loadIdList();
            }

            foreach ($childIds as $id) {
                $pasteJobs[] = [[
                    'url' => $this->router->generate('opendxp_admin_document_document_copy'),
                    'method' => 'POST',
                    'params' => [
                        'sourceId' => $id,
                        'targetParentId' => $payload->targetId,
                        'sourceParentId' => $payload->sourceId,
                        'type' => 'child',
                        'language' => $payload->language,
                        'enableInheritance' => $payload->enableInheritance,
                        'transactionId' => $transactionId,
                    ],
                ]];
            }

            if ($payload->type === 'recursive-update-references') {
                for ($i = 0; $i < (count($childIds) + 1); $i++) {
                    $pasteJobs[] = [[
                        'url' => $this->router->generate('opendxp_admin_document_document_copyrewriteids'),
                        'method' => 'PUT',
                        'params' => [
                            'transactionId' => $transactionId,
                            'enableInheritance' => $payload->enableInheritance,
                            '_dc' => uniqid('', false),
                        ],
                    ]];
                }
            }
        } elseif ($payload->type === 'child' || $payload->type === 'replace') {
            $pasteJobs[] = [[
                'url' => $this->router->generate('opendxp_admin_document_document_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $payload->sourceId,
                    'targetId' => $payload->targetId,
                    'type' => $payload->type,
                    'language' => $payload->language,
                    'enableInheritance' => $payload->enableInheritance,
                    'transactionId' => $transactionId,
                    'resetIndex' => ($payload->type === 'child'),
                ],
            ]];
        }

        return new CopyInfoResult($transactionId, $pasteJobs);
    }
}

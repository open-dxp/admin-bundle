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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\RewriteDocumentIds;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\CopySessionGateway;
use OpenDxp\Model\Document;

final class RewriteDocumentIdsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly CopySessionGateway $copySession,
    ) {}

    public function __invoke(RewriteDocumentIdsPayload $payload): RewriteDocumentIdsResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $documentId = $this->copySession->popRewriteStackId($payload->transactionId);
        $idMapping = $this->copySession->getIdMapping($payload->transactionId);
        $document = Document::getById($documentId);

        if ($document === null) {
            return new RewriteDocumentIdsResult($documentId);
        }

        $document = Document\Service::rewriteIds($document, ['document' => $idMapping], [
            'enableInheritance' => $payload->enableInheritance,
        ]);
        $document->setUserModification($userId);
        $document->save();

        return new RewriteDocumentIdsResult($documentId);
    }
}

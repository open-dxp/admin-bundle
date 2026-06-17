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

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Document;

final class RewriteDocumentIdsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(RewriteDocumentIdsPayload $payload): void
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $document = Document::getById($payload->documentId);

        if ($document === null) {
            return;
        }

        $document = Document\Service::rewriteIds($document, ['document' => $payload->idMapping], [
            'enableInheritance' => $payload->enableInheritance,
        ]);
        $document->setUserModification($userId);
        $document->save();
    }
}

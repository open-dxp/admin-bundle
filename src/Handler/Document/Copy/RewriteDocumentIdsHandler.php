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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy;

use OpenDxp\Model\Document;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class RewriteDocumentIdsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(int $documentId, array $idMapping, bool $enableInheritance): void
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $document = Document::getById($documentId);

        if ($document === null) {
            return;
        }

        $document = Document\Service::rewriteIds($document, ['document' => $idMapping], [
            'enableInheritance' => $enableInheritance,
        ]);
        $document->setUserModification($userId);
        $document->save();
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedDocuments;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\Document;

final class GetModifiedDocumentsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(): GetModifiedDocumentsResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $list = Document::getList([
            'limit' => 10,
            'order' => 'DESC',
            'orderKey' => 'modificationDate',
            'condition' => 'userModification = ' . $userId,
        ]);

        $documents = [];
        foreach ($list as $doc) {
            if ($doc->isAllowed('view')) {
                $documents[] = [
                    'id' => $doc->getId(),
                    'type' => $doc->getType(),
                    'path' => $doc->getRealFullPath(),
                    'date' => $doc->getModificationDate(),
                ];
            }
        }

        return new GetModifiedDocumentsResult(documents: $documents);
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\GetDocumentChildIds;

use OpenDxp\Bundle\AdminBundle\Exception\Document\DocumentNotFoundException;
use OpenDxp\Model\Document;

final class GetDocumentChildIdsHandler
{
    public function __invoke(GetDocumentChildIdsPayload $payload): GetDocumentChildIdsResult
    {
        $document = Document::getById($payload->sourceId) ?? throw new DocumentNotFoundException($payload->sourceId);

        if (!$document->hasChildren()) {
            return new GetDocumentChildIdsResult([]);
        }

        $list = new Document\Listing();
        $list->setCondition('`path` LIKE ?', [$list->escapeLike($document->getRealFullPath()) . '/%']);
        $list->setOrderKey('LENGTH(`path`)', false);
        $list->setOrder('ASC');

        return new GetDocumentChildIdsResult($list->loadIdList());
    }
}

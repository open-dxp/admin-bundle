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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentIdForPath;

use OpenDxp\Model\Document;

final class GetDocumentIdForPathHandler
{
    public function __invoke(GetDocumentIdForPathPayload $payload): ?GetDocumentIdForPathResult
    {
        $document = Document::getByPath($payload->path);
        if (!$document) {
            return null;
        }

        return new GetDocumentIdForPathResult($document->getId(), $document->getType());
    }
}

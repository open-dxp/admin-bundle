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

namespace OpenDxp\Bundle\AdminBundle\Enricher\Document;

use OpenDxp\Model\Document;
use OpenDxp\Model\Element\Service as ElementService;

final class DocumentMetaEnricher
{
    public function enrich(Document $document, array &$data): void
    {
        $fresh = Document::getById($document->getId() ?? 0, ['force' => true]) ?? $document;
        $data['versionDate'] = $fresh->getModificationDate();
        $data['userPermissions'] = $document->getUserPermissions();
        $data['idPath'] = ElementService::getIdPath($document);
    }
}

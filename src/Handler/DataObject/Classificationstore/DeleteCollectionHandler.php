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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use OpenDxp\Model\DataObject\Classificationstore;

final class DeleteCollectionHandler
{
    public function __invoke(DeleteCollectionPayload $payload): void
    {
        $configRelations = new Classificationstore\CollectionGroupRelation\Listing();
        $configRelations->setCondition('colId = ?', $payload->id);
        $list = $configRelations->load();
        foreach ($list as $item) {
            $item->delete();
        }

        $config = Classificationstore\CollectionConfig::getById($payload->id);
        $config->delete();
    }
}

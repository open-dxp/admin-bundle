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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveCollectionRelations;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\DataObject\Classificationstore;

final class SaveCollectionRelationsHandler
{
    public function __invoke(SaveCollectionRelationsPayload $payload): SaveCollectionRelationsResult
    {
        if (!$payload->hasData) {
            throw new AdminOperationFailedException();
        }

        $data = $payload->data;
        if (count($data) === count($data, 1)) {
            $data = [$data];
        }

        foreach ($data as &$row) {
            $colId = $row['colId'];
            $groupId = $row['groupId'];
            $sorter = $row['sorter'];

            $config = new Classificationstore\CollectionGroupRelation();
            $config->setGroupId($groupId);
            $config->setColId($colId);
            $config->setSorter((int) $sorter);

            $config->save();

            $row['id'] = $config->getColId() . '-' . $config->getGroupId();
        }

        return new SaveCollectionRelationsResult(data: $data);
    }
}

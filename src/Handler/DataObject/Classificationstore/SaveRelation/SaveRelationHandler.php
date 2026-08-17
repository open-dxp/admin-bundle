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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SaveRelation;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\DataObject\Classificationstore;

final class SaveRelationHandler
{
    public function __invoke(SaveRelationPayload $payload): SaveRelationResult
    {
        if (!$payload->hasData) {
            throw new AdminOperationFailedException();
        }

        $data = $payload->data;
        $keyId = $data['keyId'];
        $groupId = $data['groupId'];
        $sorter = $data['sorter'];
        $mandatory = $data['mandatory'];

        $config = new Classificationstore\KeyGroupRelation();
        $config->setGroupId((int) $groupId);
        $config->setKeyId((int) $keyId);
        $config->setSorter($sorter);
        $config->setMandatory($mandatory);

        $config->save();
        $data['id'] = $config->getGroupId() . '-' . $config->getKeyId();

        return new SaveRelationResult(data: $data);
    }
}

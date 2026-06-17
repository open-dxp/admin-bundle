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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\GetDataObjectChildIds;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Model\DataObject;

final class GetDataObjectChildIdsHandler
{
    public function __invoke(GetDataObjectChildIdsPayload $payload): GetDataObjectChildIdsResult
    {
        $object = DataObject::getById($payload->sourceId) ?? throw new DataObjectNotFoundException($payload->sourceId);

        if (!$object->hasChildren(DataObject::$types)) {
            return new GetDataObjectChildIdsResult([]);
        }

        $list = new DataObject\Listing();
        $list->setCondition('`path` LIKE ' . $list->quote($list->escapeLike($object->getRealFullPath()) . '/%'));
        $list->setOrderKey('LENGTH(`path`)', false);
        $list->setOrder('ASC');
        $list->setObjectTypes(DataObject::$types);

        return new GetDataObjectChildIdsResult($list->loadIdList());
    }
}

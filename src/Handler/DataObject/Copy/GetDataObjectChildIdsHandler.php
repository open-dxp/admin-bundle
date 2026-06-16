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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy;

use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Model\DataObject;

final class GetDataObjectChildIdsHandler
{
    public function __invoke(int $sourceId): ChildIdsResult
    {
        $object = DataObject::getById($sourceId) ?? throw new DataObjectNotFoundException($sourceId);

        if (!$object->hasChildren(DataObject::$types)) {
            return new ChildIdsResult([]);
        }

        $list = new DataObject\Listing();
        $list->setCondition('`path` LIKE ' . $list->quote($list->escapeLike($object->getRealFullPath()) . '/%'));
        $list->setOrderKey('LENGTH(`path`)', false);
        $list->setOrder('ASC');
        $list->setObjectTypes(DataObject::$types);

        return new ChildIdsResult($list->loadIdList());
    }
}

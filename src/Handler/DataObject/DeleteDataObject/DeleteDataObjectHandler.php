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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\DeleteDataObject;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DeleteDataObjectHandler
{
    public function __invoke(DeleteDataObjectPayload $payload): DeleteDataObjectResult
    {
        $type = $payload->type;
        $id = $payload->id;
        $amount = $payload->amount;

        if ($type !== 'children' && !$id) {
            throw new AdminOperationFailedException();
        }

        if ($type === 'children') {
            $parentObject = DataObject::getById($id);

            $list = new DataObject\Listing();
            $list->setCondition('`path` LIKE ' . $list->quote($list->escapeLike($parentObject->getRealFullPath()) . '/%'));
            $list->setLimit($amount);
            $list->setOrderKey('LENGTH(`path`)', false);
            $list->setOrder('DESC');

            $deletedItems = [];
            foreach ($list as $object) {
                $deletedItems[$object->getId()] = $object->getRealFullPath();
                if ($object->isAllowed('delete') && !$object->isLocked()) {
                    $object->delete();
                }
            }

            return new DeleteDataObjectResult(deleted: $deletedItems);
        }

        $object = DataObject::getById($id);

        if ($object) {
            if (!$object->isAllowed('delete')) {
                throw new AccessDeniedHttpException('Missing permission to delete object');
            }

            if ($object->isLocked()) {
                throw new AdminOperationFailedException('prevented deleting object, because it is locked: ID: ' . $object->getId());
            }

            $object->delete();
        }

        // Return empty result even when the object doesn't exist — valid for batch delete incl. children
        return new DeleteDataObjectResult();
    }
}

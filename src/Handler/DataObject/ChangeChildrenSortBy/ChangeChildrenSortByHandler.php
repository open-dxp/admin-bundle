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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ChangeChildrenSortBy;

use Exception;
use OpenDxp\Db;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class ChangeChildrenSortByHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(ChangeChildrenSortByPayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $id = $payload->id;
        $sortBy = $payload->sortBy;
        $sortOrder = in_array($payload->sortOrder, ['ASC', 'DESC']) ? $payload->sortOrder : 'ASC';

        $object = DataObject::getById($id);

        if (!$object) {
            throw new AdminOperationFailedException(sprintf('DataObject with id %d not found', $id));
        }

        $currentSortBy = $object->getChildrenSortBy();

        $object->setChildrenSortBy($sortBy);
        $object->setChildrenSortOrder($sortOrder);

        if ($currentSortBy !== $sortBy) {
            if (!$adminUser->isAdmin() && !$adminUser->isAllowed('objects_sort_method')) {
                throw new AdminOperationFailedException('Changing the sort method is only allowed for admin users');
            }

            if ($sortBy === 'index') {
                $this->reindexBasedOnSortOrder($object, $sortOrder);
            }
        }

        $object->save();
    }

    private function reindexBasedOnSortOrder(DataObject\AbstractObject $parentObject, string $currentSortOrder): void
    {
        $fn = function () use ($parentObject, $currentSortOrder): void {
            $list = new DataObject\Listing();

            $db = Db::get();
            $db->executeStatement(
                'UPDATE ' . $list->getDao()->getTableName() . ' o,
                    (
                    SELECT newIndex, id FROM (
                        SELECT @n := @n +1 AS newIndex, id
                        FROM ' . $list->getDao()->getTableName() . ',
                                (SELECT @n := -1) variable
                                 WHERE parentId = ? ORDER BY `key` ' . $currentSortOrder
                . ') tmp
                    ) order_table
                    SET o.index = order_table.newIndex
                    WHERE o.id=order_table.id',
                [
                    $parentObject->getId(),
                ]
            );

            $db = Db::get();
            $children = $db->fetchAllAssociative(
                'SELECT id, modificationDate, versionCount FROM objects WHERE parentId = ? ORDER BY `index` ASC',
                [$parentObject->getId()]
            );

            foreach ($children as $child) {
                $this->updateLatestVersionIndex($child['id'], $child['modificationDate']);

                DataObject::clearDependentCacheByObjectId($child['id']);
            }
        };

        $this->executeInsideTransaction($fn);
    }

    private function updateLatestVersionIndex(int $objectId, int $newIndex): void
    {
        $object = DataObject\Concrete::getById($objectId);

        if (
            $object &&
            $object->getType() !== DataObject::OBJECT_TYPE_FOLDER &&
            $latestVersion = $object->getLatestVersion()
        ) {
            // don't renew references (which means loading the target elements)
            // Not needed as we just save a new version with the updated index
            $object = $latestVersion->loadData(false);
            if ($newIndex !== $object->getIndex()) {
                $object->setIndex($newIndex);
            }
            $latestVersion->save();
        }
    }

    private function executeInsideTransaction(callable $fn): void
    {
        $maxRetries = 5;
        for ($retries = 0; $retries < $maxRetries; $retries++) {
            try {
                Db::get()->beginTransaction();

                $fn();

                Db::get()->commit();

                break;
            } catch (Exception $e) {
                Db::get()->rollBack();

                // we try to start the transaction $maxRetries times again (deadlocks, ...)
                if ($retries < ($maxRetries - 1)) {
                    $run = $retries + 1;
                    $waitTime = random_int(1, 5) * 100000; // microseconds
                    Logger::warn('Unable to finish transaction (' . $run . ". run) because of the following reason '" . $e->getMessage() . "'. --> Retrying in " . $waitTime . ' microseconds ... (' . ($run + 1) . ' of ' . $maxRetries . ')');

                    usleep($waitTime); // wait specified time until we restart the transaction
                } else {
                    // if the transaction still fail after $maxRetries retries, we throw out the exception
                    Logger::error('Finally giving up restarting the same transaction again and again, last message: ' . $e->getMessage());

                    throw $e;
                }
            }
        }
    }
}

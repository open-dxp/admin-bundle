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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\UpdateDataObject;

use Exception;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Exception\DataObject\DataObjectNotFoundException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\DataObject\DataObjectGridService;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Db;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element\Service;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class UpdateDataObjectHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DataObjectGridService $dataObjectGridService,
        private readonly ElementServiceInterface $elementService,
    ) {
    }

    public function __invoke(UpdateDataObjectPayload $payload): UpdateDataObjectResult
    {
        $result = null;
        foreach ($payload->ids as $id) {
            $result = $this->processOne($id, $payload->values);
        }

        return $result ?? new UpdateDataObjectResult(treeData: []);
    }

    private function processOne(int $id, array $values): UpdateDataObjectResult
    {
        $object = DataObject::getById($id);
        if (!$object instanceof DataObject) {
            throw new DataObjectNotFoundException($id);
        }

        $adminUser = $this->userContext->getAdminUser();
        if ($object instanceof DataObject\Concrete) {
            $object->setOmitMandatoryCheck(true);
        }

        // this prevents the user from renaming, relocating (actions in the tree) if the newest version isn't the published one
        // the reason is that otherwise the content of the newer not published version will be overwritten
        if ($object instanceof DataObject\Concrete) {
            $latestVersion = $object->getLatestVersion();
            if ($latestVersion && $latestVersion->getData()->getModificationDate() != $object->getModificationDate()) {
                throw new AdminOperationFailedException("You can't rename or relocate if there's a newer not published version");
            }
        }

        $key = $values['key'] ?? null;
        if ($key) {
            $key = Service::getValidKey($key, 'object');
        }

        if ($object->isAllowed('settings')) {
            if ($key) {
                if ($object->isAllowed('rename')) {
                    $object->setKey($key);
                } elseif ($key !== $object->getKey()) {
                    Logger::debug('prevented renaming object because of missing permissions ');
                }
            }

            if (!empty($values['parentId'])) {
                $parent = DataObject::getById((int) $values['parentId']);

                //check if parent is changed
                if ($object->getParentId() !== $parent->getId()) {
                    if (!$parent->isAllowed('create')) {
                        throw new AccessDeniedHttpException('Prevented moving object - no create permission on new parent ');
                    }

                    $objectWithSamePath = DataObject::getByPath($parent->getRealFullPath() . '/' . $object->getKey());

                    if ($objectWithSamePath != null) {
                        throw new AdminOperationFailedException('prevented creating object because object with same path+key already exists');
                    }

                    if ($object->isLocked()) {
                        throw new AdminOperationFailedException('prevented moving object, because it is locked: ID: ' . $object->getId());
                    }

                    $object->setParentId($values['parentId']);
                }
            }

            if (array_key_exists('locked', $values)) {
                $object->setLocked($values['locked']);
            }

            $object->setModificationDate(time());
            $object->setUserModification($adminUser->getId());

            $isIndexUpdate = isset($values['indices']);

            if ($isIndexUpdate) {
                // Ensure the update sort index is already available in the postUpdate eventListener
                $indexUpdate = is_int($values['indices']) ? $values['indices'] : $values['indices'][$object->getId()];
                $object->setIndex($indexUpdate);
            }

            $object->save();

            if ($isIndexUpdate) {
                $this->updateIndexesOfObjectSiblings($object, $indexUpdate);
            }

            return new UpdateDataObjectResult(
                treeData: $this->elementService->getElementTreeNodeConfig($object),
            );
        }

        if ($key && $object->isAllowed('rename')) {
            $result = $this->dataObjectGridService->renameObject($object, $key);
            if (!$result['success']) {
                throw new AdminOperationFailedException($result['message'] ?? 'Failed to rename object');
            }

            return new UpdateDataObjectResult(
                treeData: $this->elementService->getElementTreeNodeConfig($object),
            );
        }

        Logger::debug('prevented update object because of missing permissions.');

        // Return current tree data even when no changes were applied
        return new UpdateDataObjectResult(
            treeData: $this->elementService->getElementTreeNodeConfig($object),
        );
    }

    private function updateIndexesOfObjectSiblings(DataObject\AbstractObject $updatedObject, int $newIndex): void
    {
        $fn = function () use ($updatedObject, $newIndex): void {
            $list = new DataObject\Listing();
            $updatedObject->saveIndex($newIndex);

            // The cte and the limit are needed to order the data before the newIndex is set
            $db = Db::get();
            $db->executeStatement(
                'UPDATE ' . $list->getDao()->getTableName() . ' o,
                    (
                        SELECT newIndex, id
                        FROM (
                            With cte As (SELECT `index`, id FROM ' . $list->getDao()->getTableName() . ' WHERE parentId = ? AND id != ? AND `type` IN (\'' . implode(
                    "','", [
                        DataObject::OBJECT_TYPE_OBJECT,
                        DataObject::OBJECT_TYPE_VARIANT,
                        DataObject::OBJECT_TYPE_FOLDER,
                    ]
                ) . '\') ORDER BY `index` LIMIT ' . $updatedObject->getParent()->getChildAmount([
                    DataObject::OBJECT_TYPE_OBJECT,
                    DataObject::OBJECT_TYPE_VARIANT,
                    DataObject::OBJECT_TYPE_FOLDER,
                ]) . ')
                            SELECT @n := IF(@n = ? - 1,@n + 2,@n + 1) AS newIndex, id
                            FROM cte,
                            (SELECT @n := -1) variable
                        ) tmp
                    ) order_table
                    SET o.index = order_table.newIndex
                    WHERE o.id=order_table.id',
                [
                    $updatedObject->getParentId(),
                    $updatedObject->getId(),
                    $newIndex,
                ]
            );

            $siblings = $db->fetchAllAssociative(
                'SELECT id, modificationDate, versionCount, `key`, `index` FROM objects
                    WHERE parentId = ? AND id != ? AND `type` IN ("object", "variant", "folder") ORDER BY `index` ASC',
                [$updatedObject->getParentId(), $updatedObject->getId()]
            );
            $index = 0;

            foreach ($siblings as $sibling) {
                if ($index === $newIndex) {
                    $index++;
                }

                $this->updateLatestVersionIndex((int) $sibling['id'], $index);
                $index++;

                DataObject::clearDependentCacheByObjectId((int) $sibling['id']);
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

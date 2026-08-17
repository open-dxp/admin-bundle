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

namespace OpenDxp\Bundle\AdminBundle\Coordinator\DataObject;

use OpenDxp\Bundle\AdminBundle\Dto\DataObject\DataObjectPersistenceDto;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementServiceInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class DataObjectPersistenceCoordinator
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
        private readonly ElementDraftService $elementDraftService,
    ) {
    }

    public function save(DataObject\Concrete $object, string $task): DataObjectPersistenceDto
    {
        if ($task === 'session') {
            $this->elementDraftService->saveObject($object);

            return new DataObjectPersistenceDto(
                general: [
                    'modificationDate' => 0,
                    'versionDate'      => 0,
                    'versionCount'     => 0
                ],
                treeData: [],
            );
        }

        $adminUser = $this->userContext->getAdminUser();

        if ($task === 'unpublish') {
            $object->setPublished(false);
        }

        if ($task === 'publish') {
            $object->setPublished(true);
        }

        // unpublish and save version is possible without checking mandatory fields
        if (in_array($task, ['unpublish', 'version', 'autoSave'])) {
            $object->setOmitMandatoryCheck(true);
        }

        if ($task === 'publish' || $task === 'unpublish') {
            $object->save();
            $treeData = $this->elementService->getElementTreeNodeConfig($object);
            $newObject = DataObject::getById($object->getId(), ['force' => true]);

            if ($task === 'publish') {
                $object->deleteAutoSaveVersions($adminUser->getId());
            }

            return new DataObjectPersistenceDto(
                general: [
                    'modificationDate' => $object->getModificationDate() ?? 0,
                    'versionDate'      => $newObject->getModificationDate() ?? 0,
                    'versionCount'     => $newObject->getVersionCount(),
                ],
                treeData: $treeData,
            );
        }

        if ($task === 'scheduler' && $object->isAllowed('settings')) {
            $object->saveScheduledTasks();

            return new DataObjectPersistenceDto(
                general: [
                    'modificationDate' => $object->getModificationDate() ?? 0,
                    'versionDate'      => $object->getModificationDate() ?? 0,
                    'versionCount'     => $object->getVersionCount(),
                ],
                treeData: [],
            );
        }

        if ($object->isAllowed('save') || $object->isAllowed('publish')) {
            $isAutoSave = $task === 'autoSave';
            $draftData = [];

            if ($object->isPublished() || $isAutoSave) {
                $version = $object->saveVersion(true, true, null, $isAutoSave);
                $draftData = [
                    'id'               => $version->getId(),
                    'modificationDate' => $version->getDate(),
                    'isAutoSave'       => $version->isAutoSave(),
                ];
            } else {
                $object->save();
            }

            if ($task === 'version') {
                $object->deleteAutoSaveVersions($adminUser->getId());
            }

            $treeData = $this->elementService->getElementTreeNodeConfig($object);
            $newObject = DataObject::getById($object->getId(), ['force' => true]);

            return new DataObjectPersistenceDto(
                general: [
                    'modificationDate' => $object->getModificationDate() ?? 0,
                    'versionDate'      => $newObject->getModificationDate() ?? 0,
                    'versionCount'     => $newObject->getVersionCount(),
                ],
                treeData: $treeData,
                draft: $draftData,
            );
        }

        throw new AccessDeniedHttpException('Missing permission to save object');
    }
}

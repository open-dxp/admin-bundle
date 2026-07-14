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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObject;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Helper\DataObjectVersionHelper;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\DataObject\DataObjectPayloadMapper;
use OpenDxp\Bundle\AdminBundle\Service\DataObject\DataObjectPersistenceCoordinator;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data\EqualComparisonInterface;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class SaveDataObjectHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly DataObjectPayloadMapper $mapper,
        private readonly DataObjectPersistenceCoordinator $coordinator,
    ) {}

    public function __invoke(SaveDataObjectPayload $payload): SaveDataObjectResult
    {
        $objectFromDatabase = DataObject\Concrete::getById($payload->id);
        if (!$objectFromDatabase instanceof DataObject\Concrete) {
            throw new AdminOperationFailedException('Could not find object');
        }

        $adminUser = $this->userContext->getAdminUser();
        $object = DataObjectVersionHelper::resolveLatestDraft($objectFromDatabase, $adminUser?->getId());
        $object->setUserModification($adminUser->getId());

        $objectFromVersion = $object !== $objectFromDatabase;
        if ($objectFromVersion) {
            if (method_exists($object, 'getLocalizedFields')) {
                /** @var DataObject\Localizedfield $localizedFields */
                $localizedFields = $object->getLocalizedFields();
                $localizedFields->setLoadedAllLazyData();
            }

            // Mark fields that have changed as dirty
            if ($payload->task !== 'autoSave' && $payload->task !== 'unpublish') {
                foreach ($object->getClass()->getFieldDefinitions() as $fieldName => $fieldDefinition) {
                    $getter = 'get' . ucfirst($fieldName);
                    $oldValue = $objectFromDatabase->$getter();
                    $newValue = $object->$getter();
                    $isEqual = $fieldDefinition instanceof EqualComparisonInterface
                        ? $fieldDefinition->isEqual($oldValue, $newValue)
                        : $oldValue === $newValue;

                    if (!$isEqual) {
                        $object->markFieldDirty($fieldName);
                    }
                }
            }
        }

        if (
            ($payload->task === 'unpublish' && !$object->isAllowed('unpublish')) ||
            ($payload->task === 'publish' && !$object->isAllowed('publish'))
        ) {
            throw new AccessDeniedHttpException();
        }

        $this->mapper->applyPayload($payload, $object, $objectFromDatabase);

        $persistenceData = $this->coordinator->save($object, $payload->task);

        return new SaveDataObjectResult(
            general: $persistenceData->general,
            treeData: $persistenceData->treeData,
            draft: $persistenceData->draft
        );
    }
}

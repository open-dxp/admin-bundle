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

namespace OpenDxp\Bundle\AdminBundle\Mapper\DataObject;

use Exception;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\SaveDataObject\SaveDataObjectPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use OpenDxp\Model\Schedule\Task;
use OpenDxp\Tool;
use Throwable;

final class DataObjectPayloadMapper
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function applyPayload(SaveDataObjectPayload $payload, DataObject\Concrete $object, DataObject\Concrete $objectFromDatabase): void
    {
        if ($payload->data !== []) {
            try {
                $this->applyChanges($object, $payload->data);
            } catch (Throwable) {
                $this->applyChanges($objectFromDatabase, $payload->data);
            }
        }

        $this->assignProperties($payload->properties, $object);
        $this->applyScheduler($payload->scheduler, $object);
    }

    public function applyChanges(DataObject\Concrete $object, array $changes): void
    {
        foreach ($changes as $key => $value) {
            $fd = $object->getClass()->getFieldDefinition($key);
            if ($fd) {
                if ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                    $user = Tool\Admin::getCurrentUser();
                    if (!$user->getAdmin()) {
                        $allowedLanguages = DataObject\Service::getLanguagePermissions($object, $user, 'lEdit');
                        if (!is_null($allowedLanguages)) {
                            $allowedLanguages = array_keys($allowedLanguages);
                            $submittedLanguages = array_keys($changes[$key]);
                            foreach ($submittedLanguages as $submittedLanguage) {
                                if (!in_array($submittedLanguage, $allowedLanguages)) {
                                    unset($value[$submittedLanguage]);
                                }
                            }
                        }
                    }
                }

                if ($fd instanceof ReverseObjectRelation) {
                    $remoteClass = DataObject\ClassDefinition::getByName($fd->getOwnerClassName());
                    $relations = $object->getRelationData($fd->getOwnerFieldName(), false, $remoteClass->getId());
                    $toAdd = $this->detectAddedRemoteOwnerRelations($relations, $value);
                    $toDelete = $this->detectDeletedRemoteOwnerRelations($relations, $value);
                    if (count($toAdd) > 0 || count($toDelete) > 0) {
                        $this->processRemoteOwnerRelations($object, $toDelete, $toAdd, $fd->getOwnerFieldName());
                    }
                } else {
                    $object->setValue($key, $fd->getDataFromEditmode($value, $object));
                }
            }
        }
    }

    private function assignProperties(array $propertiesData, DataObject\AbstractObject $object): void
    {
        if ($propertiesData === []) {
            return;
        }

        $properties = [];
        foreach ($object->getProperties() as $p) {
            if ($p->isInherited()) {
                $properties[$p->getName()] = $p;
            }
        }

        foreach ($propertiesData as $propertyName => $propertyData) {
            $value = $propertyData['data'];

            try {
                $property = new Model\Property();
                $property->setType($propertyData['type']);
                $property->setName($propertyName);
                $property->setCtype('object');
                $property->setDataFromEditmode($value);
                $property->setInheritable($propertyData['inheritable']);

                $properties[$propertyName] = $property;
            } catch (Exception) {
                Logger::err("Can't add " . $propertyName . ' to object ' . $object->getRealFullPath());
            }
        }

        $object->setProperties($properties);
    }

    private function applyScheduler(array $schedulerData, DataObject\AbstractObject $object): void
    {
        if ($schedulerData === []) {
            return;
        }

        $adminUser = $this->userContext->getAdminUser();
        $tasks = [];
        foreach ($schedulerData as $taskData) {
            $taskData['userId'] = $adminUser?->getId();
            $task = new Task($taskData);
            $tasks[] = $task;
        }

        if ($object->isAllowed('settings') && method_exists($object, 'setScheduledTasks')) {
            $object->setScheduledTasks($tasks);
        }
    }

    private function processRemoteOwnerRelations(DataObject\Concrete $object, array $toDelete, array $toAdd, string $ownerFieldName): void
    {
        $getter = 'get' . ucfirst($ownerFieldName);
        $setter = 'set' . ucfirst($ownerFieldName);

        foreach ($toDelete as $id) {
            $owner = DataObject::getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                if (is_array($currentData)) {
                    $counter = count($currentData);
                    for ($i = 0; $i < $counter; $i++) {
                        if ($currentData[$i]->getId() == $object->getId()) {
                            unset($currentData[$i]);
                            $owner->$setter($currentData);

                            break;
                        }
                    }
                } elseif ($currentData->getId() == $object->getId()) {
                    $owner->$setter(null);
                }
            }
            $owner->setUserModification($object->getUserModification());
            $owner->save();
            Logger::debug('Saved object id [ ' . $owner->getId() . ' ] by remote modification through [' . $object->getId() . '], Action: deleted [ ' . $object->getId() . " ] from [ $ownerFieldName]");
        }

        foreach ($toAdd as $id) {
            $owner = DataObject::getById($id);
            //TODO: lock ?!
            if (method_exists($owner, $getter)) {
                $currentData = $owner->$getter();
                if (is_array($currentData)) {
                    $currentData[] = $object;
                } else {
                    $currentData = $object;
                }
                $owner->$setter($currentData);
                $owner->setUserModification($object->getUserModification());
                $owner->save();
                Logger::debug('Saved object id [ ' . $owner->getId() . ' ] by remote modification through [' . $object->getId() . '], Action: added [ ' . $object->getId() . " ] to [ $ownerFieldName ]");
            }
        }
    }

    private function detectDeletedRemoteOwnerRelations(array $relations, array $value): array
    {
        $originals = [];
        $changed = [];
        foreach ($relations as $r) {
            $originals[] = $r['dest_id'];
        }

        foreach ($value as $row) {
            $changed[] = $row['id'];
        }

        return array_diff($originals, $changed);
    }

    private function detectAddedRemoteOwnerRelations(array $relations, array $value): array
    {
        $originals = [];
        $changed = [];

        foreach ($relations as $r) {
            $originals[] = $r['dest_id'];
        }

        foreach ($value as $row) {
            $changed[] = $row['id'];
        }

        return array_diff($changed, $originals);
    }
}

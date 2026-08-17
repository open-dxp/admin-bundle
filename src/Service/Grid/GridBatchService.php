<?php

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

namespace OpenDxp\Bundle\AdminBundle\Service\Grid;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Loader\ImplementationLoader\Exception\UnsupportedException;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Metadata;
use OpenDxp\Model\User;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;

final class GridBatchService
{
    public function __construct(
        private readonly GridHelperService $gridHelperService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    /**
     * @return int[]
     */
    public function getAssetBatchJobIds(array $params, User $adminUser): array
    {
        $list = $this->gridHelperService->prepareAssetListingForGrid($params, $adminUser);

        return $list->loadIdList();
    }

    /**
     * @return int[]
     */
    public function getObjectBatchJobIds(array $params, string $locale, User $adminUser): array
    {
        $list = $this->gridHelperService->prepareListingForGrid($params, $locale, $adminUser);

        return $list->loadIdList();
    }

    /**
     * Executes a batch metadata update on a single asset.
     *
     * @throws Exception on permission denied, save failure, or when there is
     *                    no asset to update (job already completed) or nothing changed
     */
    public function executeAssetBatch(array $data, User $adminUser): void
    {
        $loader = OpenDxp::getContainer()->get('opendxp.implementation_loader.asset.metadata.data');

        $updateEvent = new GenericEvent($this->currentControllerContext->getController(), [
            'data' => $data,
            'processed' => false,
        ]);

        $this->eventDispatcher->dispatch($updateEvent, AdminEvents::ASSET_LIST_BEFORE_BATCH_UPDATE);

        if ($updateEvent->getArgument('processed')) {
            return;
        }

        $language = null;
        if (isset($data['language'])) {
            $language = $data['language'] !== 'default' ? $data['language'] : null;
        }

        $asset = Asset::getById((int) $data['job']);

        if (!$asset) {
            throw new Exception('AssetHelperController::batchAction => There is no asset left to update.');
        }

        if (!$asset->isAllowed('publish')) {
            throw new Exception("Permission denied. You don't have the rights to save this asset.");
        }

        $metadata = $asset->getMetadata(null, null, false, true);
        $dirty = false;

        $name = $data['name'];
        $value = $data['value'];

        if ($data['valueType'] === 'object') {
            $value = json_decode($value);
        }

        $fieldDef = explode('~', $name);
        $name = $fieldDef[0];
        if (count($fieldDef) > 1) {
            $language = ($fieldDef[1] === 'none' ? '' : $fieldDef[1]);
        }

        foreach ($metadata as &$em) {
            if ($em['name'] == $name && $em['language'] == $language) {
                try {
                    $dataImpl = $loader->build($em['type']);
                    $value = $dataImpl->getDataFromListfolderGrid($value, $em);
                } catch (UnsupportedException) {
                    Logger::error('could not resolve metadata implementation for ' . $em['type']);
                }
                $em['data'] = $value;
                $dirty = true;

                break;
            }
        }

        if (!$dirty) {
            $defaultMetadata = ['title', 'alt', 'copyright'];
            if (in_array($name, $defaultMetadata)) {
                $newEm = [
                    'name' => $name,
                    'language' => $language,
                    'type' => 'input',
                    'data' => $value,
                ];

                try {
                    $dataImpl = $loader->build($newEm['type']);
                    $newEm['data'] = $dataImpl->getDataFromListfolderGrid($value, $newEm);
                } catch (UnsupportedException) {
                    Logger::error('could not resolve metadata implementation for ' . $newEm['type']);
                }

                $metadata[] = $newEm;
                $dirty = true;
            } else {
                $predefined = Metadata\Predefined::getByName($name);
                if ($predefined && (empty($predefined->getTargetSubtype())
                        || $predefined->getTargetSubtype() === $asset->getType())) {
                    $newEm = [
                        'name' => $name,
                        'language' => $language,
                        'type' => $predefined->getType(),
                        'data' => $value,
                    ];

                    try {
                        $dataImpl = $loader->build($newEm['type']);
                        $newEm['data'] = $dataImpl->getDataFromListfolderGrid($value, $newEm);
                    } catch (UnsupportedException) {
                        Logger::error('could not resolve metadata implementation for ' . $newEm['type']);
                    }

                    $metadata[] = $newEm;
                    $dirty = true;
                }
            }
        }

        if (!$dirty) {
            throw new Exception('AssetHelperController::batchAction => There is no asset left to update.');
        }

        $metadataEvent = new GenericEvent($this->currentControllerContext->getController(), [
            'id' => $asset->getId(),
            'metadata' => $metadata,
        ]);

        $this->eventDispatcher->dispatch($metadataEvent, AdminEvents::ASSET_METADATA_PRE_SET);

        $asset->setMetadataRaw($metadata);
        $asset->save();
    }

    /**
     * Executes a batch field update on a single DataObject.
     *
     * @throws Exception on permission denied, save failure, or when there is
     *                    no object to update (job already completed)
     */
    public function executeObjectBatch(array $params, string $locale, User $adminUser): void
    {
        $object = DataObject\Concrete::getById($params['job']);

        if (!$object) {
            throw new Exception('There is no object left to update.');
        }

        $requestedLanguage = $params['language'];
        if (!$requestedLanguage) {
            $requestedLanguage = $locale;
        } elseif ($requestedLanguage === 'default') {
            $requestedLanguage = $locale;
        }

        $name = $params['name'];

        if (!$object->isAllowed('save') || ($name === 'published' && !$object->isAllowed('publish'))) {
            throw new Exception("Permission denied. You don't have the rights to save this object.");
        }

        $append = $params['append'] ?? false;
        $remove = $params['remove'] ?? false;

        $className = $object->getClassName();
        $class = DataObject\ClassDefinition::getByName($className);
        $value = $params['value'];
        if ($params['valueType'] === 'object') {
            $value = json_decode($value, true);
        }

        $parts = explode('~', $name);

        if (str_starts_with($name, '~')) {
            $type = $parts[1];
            $field = $parts[2];
            $keyId = $parts[3];

            if ($type === 'classificationstore') {
                $groupKeyId = explode('-', $keyId);
                $groupId = (int) $groupKeyId[0];
                $keyId = (int) $groupKeyId[1];

                $getter = 'get' . ucfirst($field);
                if (method_exists($object, $getter)) {
                    /** @var DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                    $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                    $csLanguage = $requestedLanguage;
                    if (!$csFieldDefinition->isLocalized()) {
                        $csLanguage = 'default';
                    }

                    /** @var DataObject\ClassDefinition\Data\Classificationstore $fd */
                    $fd = $class->getFieldDefinition($field);
                    $keyConfig = $fd->getKeyConfiguration($keyId);
                    $dataDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromKeyConfig($keyConfig);

                    /** @var DataObject\Classificationstore $classificationStoreData */
                    $classificationStoreData = $object->$getter();
                    if ($append) {
                        $oldValue = $classificationStoreData->getLocalizedKeyValue($groupId, $keyId);
                        $value = $dataDefinition->appendData($oldValue, $value);
                    }
                    if ($remove) {
                        $oldValue = $classificationStoreData->getLocalizedKeyValue($groupId, $keyId);
                        $value = $dataDefinition->removeData($oldValue, $value);
                    }
                    $classificationStoreData->setLocalizedKeyValue(
                        $groupId,
                        $keyId,
                        $dataDefinition->getDataFromEditmode($value),
                        $csLanguage
                    );
                    $object->markFieldDirty($field);
                }
            }
        } elseif (count($parts) > 1) {
            // check for bricks
            $brickType = $parts[0];

            if (str_contains($brickType, '?')) {
                $brickDescriptor = substr($brickType, 1);
                $brickDescriptor = json_decode($brickDescriptor, true);
                $brickType = $brickDescriptor['containerKey'];
            }
            $brickKey = $parts[1];
            $brickField = DataObject\Service::getFieldForBrickType($object->getClass(), $brickType);

            $fieldGetter = 'get' . ucfirst($brickField);
            $brickGetter = 'get' . ucfirst($brickType);
            $valueSetter = 'set' . ucfirst($brickKey);

            $brick = $object->$fieldGetter()->$brickGetter();
            if (empty($brick)) {
                $classname = '\\OpenDxp\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickType);
                $brickSetter = 'set' . ucfirst($brickType);
                $brick = new $classname($object);
                $object->$fieldGetter()->$brickSetter($brick);
            }

            $brickClass = DataObject\Objectbrick\Definition::getByKey($brickType);
            $field = $brickClass->getFieldDefinition($brickKey);

            $newData = $field->getDataFromEditmode($value, $object);

            if ($append) {
                $valueGetter = 'get' . ucfirst($brickKey);
                $existingData = $brick->$valueGetter();
                $newData = $field->appendData($existingData, $newData);
            }
            if ($remove) {
                $valueGetter = 'get' . ucfirst($brickKey);
                $existingData = $brick->$valueGetter();
                $newData = $field->removeData($existingData, $newData);
            }

            $localizedFields = $brickClass->getFieldDefinition('localizedfields');
            $isLocalizedField = false;
            if ($localizedFields instanceof DataObject\ClassDefinition\Data\Localizedfields && $localizedFields->getFieldDefinition($brickKey)) {
                $isLocalizedField = true;
            }

            if ($isLocalizedField) {
                $brick->$valueSetter($newData, $params['language']);
            } else {
                $brick->$valueSetter($newData);
            }
        } else {
            // everything else
            $field = $class->getFieldDefinition($name);
            if ($field) {
                $newData = $field->getDataFromEditmode($value, $object);

                if ($append) {
                    $existingData = $object->{'get' . $name}();
                    $newData = $field->appendData($existingData, $newData);
                }
                if ($remove) {
                    $existingData = $object->{'get' . $name}();
                    $newData = $field->removeData($existingData, $newData);
                }
                $object->setValue($name, $newData);
            } else {
                // check if it is a localized field
                if ($params['language']) {
                    $localizedField = $class->getFieldDefinition('localizedfields');
                    if ($localizedField instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                        $field = $localizedField->getFieldDefinition($name);
                        if ($field) {
                            $getter = 'get' . $name;
                            $setter = 'set' . $name;
                            $newData = $field->getDataFromEditmode($value, $object);
                            if ($append) {
                                $existingData = $object->$getter($params['language']);
                                $newData = $field->appendData($existingData, $newData);
                            }
                            if ($remove) {
                                $existingData = $object->$getter($params['language']);
                                $newData = $field->removeData($existingData, $newData);
                            }

                            $object->$setter($newData, $params['language']);
                        }
                    }
                }

                // seems to be a system field, this is actually only possible for the "published" field yet
                if ($name === 'published') {
                    if ($value === 'false' || empty($value)) {
                        $object->setPublished(false);
                    } else {
                        $object->setPublished(true);
                    }
                }
            }
        }

        // don't check for mandatory fields here
        $object->setOmitMandatoryCheck(!$object->isPublished());
        $object->setUserModification($adminUser->getId());
        $object->save();
    }
}

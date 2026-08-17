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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Service\DataObject;

use Exception;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Mapper\GridData;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\GridColumnConfigSessionGateway;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Logger;
use OpenDxp\Model\DataObject;
use OpenDxp\Tool;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DataObjectGridService
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GridHelperService $gridHelperService,
        private readonly LocaleServiceInterface $localeService,
        private readonly GridColumnConfigSessionGateway $gridColumnConfigSession,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    public function renameObject(?DataObject $object, string $key): array
    {
        try {
            if (!$object instanceof DataObject) {
                throw new Exception('No Object found for given id.');
            }

            $object->setKey($key);
            $object->save();

            return ['success' => true];
        } catch (Exception $e) {
            Logger::error((string) $e);

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function gridProxy(array $allParams, string $objectType, string $requestedLanguage): array
    {
        $action = $allParams['xaction'] ?? 'list';
        $csvMode = $allParams['csvMode'] ?? false;

        if ($action === 'update') {
            try {
                $data = json_decode($allParams['data'], true, 512, JSON_THROW_ON_ERROR);
                $object = DataObject::getById((int)$data['id']);

                if (!$object instanceof DataObject\Concrete) {
                    throw new NotFoundHttpException('Object not found');
                }

                if (!$object->isAllowed('publish')) {
                    throw new AccessDeniedHttpException("Permission denied. You don't have the rights to save this object.");
                }

                $objectData = $this->prepareObjectData($data, $object, $requestedLanguage);
                $object->setValues($objectData);

                if ($object->getPublished() === false) {
                    $object->setOmitMandatoryCheck(true);
                }
                $object->save();

                return [
                    'success' => true,
                    'data' => GridData\DataObject::getData(
                        $object,
                        $allParams['fields'],
                        $requestedLanguage,
                        [
                            'helperDefinitions' => $this->gridColumnConfigSession->getHelperColumns(),
                        ]
                    ),
                ];
            } catch (Exception $e) {
                return [
                    'success' => false,
                    'message' => $e->getMessage(),
                ];
            }
        } else { // get list of objects/variants
            $list = $this->gridHelperService->prepareListingForGrid($allParams, $requestedLanguage, $this->userContext->getAdminUser());

            if ($objectType === DataObject::OBJECT_TYPE_OBJECT) {
                $beforeListLoadEvent = new GenericEvent($this->currentControllerContext->getController(), [
                    'list' => $list,
                    'context' => $allParams,
                ]);
                $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::OBJECT_LIST_BEFORE_LIST_LOAD);
                /** @var DataObject\Listing\Concrete $list */
                $list = $beforeListLoadEvent->getArgument('list');
            }

            if ($objectType === DataObject::OBJECT_TYPE_VARIANT) {
                $list->setObjectTypes([DataObject::OBJECT_TYPE_VARIANT]);
            }

            $list->load();

            $objects = [];
            foreach ($list->getObjects() as $object) {

                if ($csvMode) {

                    $o = DataObject\Service::getCsvDataForObject(
                        $object,
                        $requestedLanguage,
                        $allParams['fields'],
                        $this->gridColumnConfigSession->getHelperColumns(),
                        $this->localeService,
                        'title',
                        false,
                        $allParams['context']
                    );

                    // respect isAllowed method which can be extended via object DI for custom permissions
                    if ($object->isAllowed('list')) {
                        $objects[] = $o;
                    }

                } else {

                    $o = GridData\DataObject::getData(
                        $object,
                        $allParams['fields'] ?? null,
                        $requestedLanguage,
                        [
                            'csvMode' => $csvMode,
                            'helperDefinitions' => $this->gridColumnConfigSession->getHelperColumns(),
                        ]
                    );

                    if ($o['permissions']['list']) {
                        $objects[] = $o;
                    }
                }
            }

            $result = [
                'success' => true,
                'data' => $objects,
                'total' => $list->getTotalCount(),
            ];

            if ($objectType === DataObject::OBJECT_TYPE_OBJECT) {
                $afterListLoadEvent = new GenericEvent($this->currentControllerContext->getController(), [
                    'list' => $result,
                    'context' => $allParams,
                ]);

                $this->eventDispatcher->dispatch($afterListLoadEvent, AdminEvents::OBJECT_LIST_AFTER_LIST_LOAD);
                $result = $afterListLoadEvent->getArgument('list');
            }

            return $result;
        }
    }

    /**
     * @throws Exception
     */
    private function prepareObjectData(
        array $data,
        DataObject\Concrete $object,
        string $requestedLanguage,
    ): array {
        $user = Tool\Admin::getCurrentUser();
        $languagePermissions = [];
        if (!$user->isAdmin()) {
            $languagePermissionsData = $object->getPermissions('lEdit', $user);

            if ($languagePermissionsData['lEdit']) {
                $languagePermissions = explode(',', $languagePermissionsData['lEdit']);
            }
        }

        $class = $object->getClass();
        $objectData = [];
        foreach ($data as $key => $value) {
            $parts = explode('~', $key);
            if (str_starts_with($key, '~')) {
                [, $type, $field, $keyId] = $parts;

                if ($type === 'classificationstore') {
                    $groupKeyId = array_map(intval(...), explode('-', $keyId));
                    [$groupId, $keyId] = $groupKeyId;

                    $getter = 'get' . ucfirst($field);
                    if (method_exists($object, $getter)) {
                        /** @var DataObject\ClassDefinition\Data\Classificationstore $csFieldDefinition */
                        $csFieldDefinition = $object->getClass()->getFieldDefinition($field);
                        $csLanguage = $requestedLanguage;
                        if (!$csFieldDefinition->isLocalized()) {
                            $csLanguage = 'default';
                        }

                        /** @var DataObject\Classificationstore $classificationStoreData */
                        $classificationStoreData = $object->$getter();

                        $keyConfig = DataObject\Classificationstore\KeyConfig::getById($keyId);
                        if ($keyConfig) {
                            $fieldDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(
                                json_decode($keyConfig->getDefinition(), true),
                                $keyConfig->getType()
                            );
                            if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                                $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                            }
                        }

                        $object->markFieldDirty($field);

                        $activeGroups = $classificationStoreData->getActiveGroups() ?: [];
                        $activeGroups[$groupId] = true;
                        $classificationStoreData->setActiveGroups($activeGroups);
                        $classificationStoreData->setLocalizedKeyValue($groupId, $keyId, $value, $csLanguage);
                    }
                }
            } elseif (count($parts) > 1) {
                $brickType = $parts[0];
                $brickDescriptor = null;

                if (str_contains($brickType, '?')) {
                    $brickDescriptor = substr($brickType, 1);
                    $brickDescriptor = json_decode($brickDescriptor, true);
                    $brickType = $brickDescriptor['containerKey'];
                }
                $brickKey = $parts[1];
                $brickField = DataObject\Service::getFieldForBrickType($object->getClass(), $brickType);

                $fieldGetter = 'get' . ucfirst($brickField);
                $brickGetter = 'get' . ucfirst($brickType);

                $brick = $object->$fieldGetter()->$brickGetter();
                if (empty($brick)) {
                    $classname = '\\OpenDxp\\Model\\DataObject\\Objectbrick\\Data\\' . ucfirst($brickType);
                    $brickSetter = 'set' . ucfirst($brickType);
                    $brick = new $classname($object);
                    $object->$fieldGetter()->$brickSetter($brick);
                }

                if ($brickDescriptor) {
                    $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);
                    /** @var DataObject\ClassDefinition\Data\Localizedfields $fieldDefinitionLocalizedFields */
                    $fieldDefinitionLocalizedFields = $brickDefinition->getFieldDefinition('localizedfields');
                    $fieldDefinition = $fieldDefinitionLocalizedFields->getFieldDefinition($brickKey);
                } else {
                    $fieldDefinition = $this->getFieldDefinitionFromBrick($brickType, $brickKey);
                }

                if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                    $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                }

                if ($brickDescriptor) {
                    /** @var DataObject\Localizedfield $localizedFields */
                    $localizedFields = $brick->getLocalizedfields();
                    $localizedFields->setLocalizedValue($brickKey, $value);
                } else {
                    $brick->setObjectVar($brickKey, $value);
                }
            } else {
                if ($languagePermissions) {
                    $fd = $class->getFieldDefinition($key);
                    if (!$fd) {
                        // try to get via localized fields
                        $localized = $class->getFieldDefinition('localizedfields');
                        if ($localized instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                            $field = $localized->getFieldDefinition($key);
                            if ($field) {
                                $currentLocale = $this->localeService->findLocale();
                                if (!in_array($currentLocale, $languagePermissions)) {
                                    continue;
                                }
                            }
                        }
                    }
                }

                $fieldDefinition = $this->getFieldDefinition($class, $key);
                if ($fieldDefinition && method_exists($fieldDefinition, 'getDataFromGridEditor')) {
                    $value = $fieldDefinition->getDataFromGridEditor($value, $object, []);
                }

                $objectData[$key] = $value;
            }
        }

        return $objectData;
    }

    private function getFieldDefinition(DataObject\ClassDefinition $class, string $key): ?DataObject\ClassDefinition\Data
    {
        return $class->getFieldDefinition($key);
    }

    private function getFieldDefinitionFromBrick(string $brickType, string $key): ?DataObject\ClassDefinition\Data
    {
        $brickDefinition = DataObject\Objectbrick\Definition::getByKey($brickType);
        if ($brickDefinition) {
            return $brickDefinition->getFieldDefinition($key);
        }

        return null;
    }
}

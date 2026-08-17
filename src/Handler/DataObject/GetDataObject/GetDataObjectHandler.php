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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObject;

use OpenDxp\Bundle\AdminBundle\Enricher\DataObject\CustomLayoutEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\DataObject\DraftEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PhpMetaEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\PreSendDataEventEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\DataObjectVersionHelper;
use OpenDxp\Bundle\AdminBundle\Model\DataObject\DataObjectLoadContext;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Bundle\AdminBundle\Service\Element\ElementDraftService;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data\ManyToManyObjectRelation;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Relations\AbstractRelations;
use OpenDxp\Model\DataObject\ClassDefinition\Data\ReverseObjectRelation;
use OpenDxp\Model\DataObject\ClassDefinition\PreviewGeneratorInterface;
use OpenDxp\Model\Element;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Throwable;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;

final class GetDataObjectHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly PreviewGeneratorInterface $defaultPreviewGenerator,
        private readonly EditLockService $editLockService,
        private readonly ElementDraftService $elementDraftService,
        private readonly AdminStyleEnricher $adminStyleEnricher,
        private readonly UserNamesEnricher $userNamesEnricher,
        private readonly CustomLayoutEnricher $customLayoutEnricher,
        private readonly DraftEnricher $draftEnricher,
        private readonly PhpMetaEnricher $phpMetaEnricher,
        private readonly PreSendDataEventEnricher $preSendDataEventEnricher,
    ) {}

    public function __invoke(GetDataObjectPayload $payload): GetDataObjectResult
    {
        $id = $payload->id;
        $layoutId = $payload->layoutId;
        $objectFromDatabase = DataObject\Concrete::getById($id);
        if (!$objectFromDatabase instanceof DataObject\Concrete) {
            throw new NotFoundHttpException('element_not_found');
        }

        $objectFromDatabase = clone $objectFromDatabase;
        $draftVersion = null;
        $adminUser = $this->userContext->getAdminUser();
        $object = DataObjectVersionHelper::resolveLatestDraft($objectFromDatabase, $adminUser?->getId(), $draftVersion);
        $objectFromVersion = $object !== $objectFromDatabase;

        if (!$object->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        if ($object->isAllowed('save') || $object->isAllowed('publish') || $object->isAllowed('unpublish') || $object->isAllowed('delete')) {
            $this->editLockService->checkAndAcquire($object->getId(), 'object', AdminEvents::OBJECT_GET_IS_LOCKED, $object);
        }

        $objectData = [];

        /** -------------------------------------------------------------
         *   Load some general data from published object (if existing)
         *  ------------------------------------------------------------- */
        $objectData['idPath'] = Element\Service::getIdPath($objectFromDatabase);

        $linkGeneratorReference = $objectFromDatabase->getClass()->getLinkGeneratorReference();
        $previewGenerator = $objectFromDatabase->getClass()->getPreviewGenerator();
        if (empty($previewGenerator) && !empty($linkGeneratorReference)) {
            $previewGenerator = $this->defaultPreviewGenerator;
        }

        $objectData['hasPreview'] = false;
        if ($linkGeneratorReference || $previewGenerator) {
            $objectData['hasPreview'] = true;
        }

        $objectData['general'] = [];

        $allowedKeys = ['published', 'key', 'id', 'creationDate', 'classId', 'className', 'type', 'parentId', 'userOwner', 'userModification'];
        foreach ($objectFromDatabase->getObjectVars() as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $objectData['general'][$key] = $value;
            }
        }
        $objectData['general']['classTitle'] = $objectFromDatabase->getClass()->getTitle() ?: $objectFromDatabase->getClassName();
        $objectData['general']['fullpath'] = $objectFromDatabase->getRealFullPath();
        $objectData['general']['locked'] = $objectFromDatabase->isLocked();
        $objectData['general']['allowInheritance'] = $objectFromDatabase->getClass()->getAllowInherit();
        $objectData['general']['allowVariants'] = $objectFromDatabase->getClass()->getAllowVariants();
        $objectData['general']['showVariants'] = $objectFromDatabase->getClass()->getShowVariants();
        $objectData['general']['showAppLoggerTab'] = $objectFromDatabase->getClass()->getShowAppLoggerTab();
        $objectData['general']['showFieldLookup'] = $objectFromDatabase->getClass()->getShowFieldLookup();
        $objectData['general']['linkGeneratorReference'] = $linkGeneratorReference;

        if ($previewGenerator) {
            $objectData['general']['previewConfig'] = $previewGenerator->getPreviewConfig($objectFromDatabase);
        }

        $objectData['layout'] = $objectFromDatabase->getClass()->getLayoutDefinitions();
        $objectData['userPermissions'] = $objectFromDatabase->getUserPermissions($adminUser);
        $objectVersions = Element\Service::getSafeVersionInfo($objectFromDatabase->getVersions());
        $objectData['versions'] = array_splice($objectVersions, -1, 1);
        $objectData['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $objectFromDatabase->getScheduledTasks()
        );

        $objectData['childdata']['id'] = $objectFromDatabase->getId();
        $objectData['childdata']['data']['classes'] = $this->prepareChildClasses($objectFromDatabase->getDao()->getClasses());
        $objectData['childdata']['data']['general'] = $objectData['general'];

        /** -------------------------------------------------------------
         *   Load remaining general data from latest version
         *  ------------------------------------------------------------- */
        $allowedKeys = ['modificationDate', 'userModification'];
        foreach ($object->getObjectVars() as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $objectData['general'][$key] = $value;
            }
        }

        $loadContext = new DataObjectLoadContext();
        try {
            $this->getDataForObject($object, $loadContext, $objectFromVersion);
        } catch (Throwable) {
            $loadContext = new DataObjectLoadContext();
            $this->getDataForObject($objectFromDatabase, $loadContext, false);
        }

        $objectData['data'] = $loadContext->objectData;
        $objectData['metaData'] = $loadContext->metaData;
        $objectData['properties'] = Element\Service::minimizePropertiesForEditmode($object->getProperties());

        // this used for the "this is not a published version" hint
        // and for adding the published icon to version overview
        $objectData['general']['versionDate'] = $objectFromDatabase->getModificationDate();
        $objectData['general']['versionCount'] = $objectFromDatabase->getVersionCount();

        $currentLayoutId = $layoutId;

        $validLayouts = DataObject\Service::getValidLayouts($object);

        //Fallback if $currentLayoutId is not set or empty string
        //Uses first valid layout instead of admin layout when empty
        $ok = false;
        foreach ($validLayouts as $layout) {
            if ($currentLayoutId == $layout->getId()) {
                $ok = true;
            }
        }

        if (!$ok) {
            $currentLayoutId = null;
        }

        //main layout has id 0 so we check for is_null()
        if ($currentLayoutId === null && $validLayouts !== []) {
            if (count($validLayouts) === 1) {
                $firstLayout = reset($validLayouts);
                $currentLayoutId = $firstLayout->getId();
            } else {
                foreach ($validLayouts as $checkDefaultLayout) {
                    if ($checkDefaultLayout->getDefault()) {
                        $currentLayoutId = $checkDefaultLayout->getId();
                    }
                }
            }
        }

        if ($currentLayoutId === null && count($validLayouts) > 0) {
            $currentLayoutId = reset($validLayouts)->getId();
        }

        if ($validLayouts !== []) {
            $objectData['validLayouts'] = [];

            foreach ($validLayouts as $validLayout) {
                $objectData['validLayouts'][] = ['id' => $validLayout->getId(), 'name' => $validLayout->getName()];
            }

            usort($objectData['validLayouts'], static function ($layoutData1, $layoutData2) {
                if ($layoutData2['id'] === '-1') {
                    return 1;
                }

                if ($layoutData1['id'] === '-1') {
                    return -1;
                }

                if ($layoutData2['id'] === '0') {
                    return 1;
                }

                if ($layoutData1['id'] === '0') {
                    return -1;
                }

                return strcasecmp($layoutData1['name'], $layoutData2['name']);
            });

            if ($currentLayoutId == -1 && $adminUser->isAdmin()) {
                $layout = DataObject\Service::getSuperLayoutDefinition($object);
                $objectData['layout'] = $layout;
            } elseif (!empty($currentLayoutId)) {
                $objectData['layout'] = $validLayouts[$currentLayoutId]->getLayoutDefinitions();
            }

            $objectData['currentLayoutId'] = $currentLayoutId;
        }

        DataObject\Service::enrichLayoutDefinition($objectData['layout'], $object);

        $this->phpMetaEnricher->enrich($objectFromDatabase, $objectData['general']);
        $this->preSendDataEventEnricher->enrich($object, $objectData);

        $this->adminStyleEnricher->forEditor($object, $objectData['general']);
        $this->userNamesEnricher->enrich($object, $objectData['general']);
        $this->customLayoutEnricher->enrich($object, $objectData);
        $this->draftEnricher->enrich($object, $objectData, $draftVersion);

        $this->elementDraftService->removeObject('object', $payload->id);

        return new GetDataObjectResult(data: $objectData);
    }

    private function getDataForObject(DataObject\Concrete $object, DataObjectLoadContext $loadContext, bool $objectFromVersion = false): void
    {
        foreach ($object->getClass()->getFieldDefinitions(['object' => $object]) as $key => $def) {
            $this->getDataForField($object, $key, $def, $loadContext, $objectFromVersion);
        }
    }

    /**
     * Gets recursively attribute data from parent and fills objectData and metaData
     */
    private function getDataForField(DataObject\Concrete $object, string $key, DataObject\ClassDefinition\Data $fielddefinition, DataObjectLoadContext $loadContext, bool $objectFromVersion, int $level = 0): void
    {
        $parent = DataObject\Service::hasInheritableParentObject($object);
        $getter = 'get' . ucfirst($key);

        // Editmode optimization for lazy loaded relations (note that this is just for AbstractRelations, not for all
        // LazyLoadingSupportInterface types. It tries to optimize fetching the data needed for the editmode without
        // loading the entire target element.
        // ReverseObjectRelation should go in there anyway (regardless if it a version or not),
        // so that the values can be loaded.
        if (
            (!$objectFromVersion && $fielddefinition instanceof AbstractRelations)
            || $fielddefinition instanceof ReverseObjectRelation
        ) {
            $refId = null;

            if ($fielddefinition instanceof ReverseObjectRelation) {
                $refKey = $fielddefinition->getOwnerFieldName();
                $refClass = DataObject\ClassDefinition::getByName($fielddefinition->getOwnerClassName());
                if ($refClass) {
                    $refId = $refClass->getId();
                }
            } else {
                $refKey = $key;
            }

            $relations = $object->getRelationData($refKey, !$fielddefinition instanceof ReverseObjectRelation, $refId);

            if ($fielddefinition->supportsInheritance() && $relations === [] && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $loadContext, $objectFromVersion, $level + 1);
            } else {
                $data = [];

                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\ManyToOneRelation) {
                    if (isset($relations[0])) {
                        $data = $relations[0];
                        $data['published'] = (bool)$data['published'];
                    } else {
                        $data = null;
                    }
                } elseif (
                    ($fielddefinition instanceof DataObject\ClassDefinition\Data\OptimizedAdminLoadingInterface && $fielddefinition->isOptimizedAdminLoading())
                    || ($fielddefinition instanceof ManyToManyObjectRelation && !$fielddefinition->getVisibleFields() && !$fielddefinition instanceof DataObject\ClassDefinition\Data\AdvancedManyToManyObjectRelation)
                ) {
                    foreach ($relations as $rkey => $rel) {
                        $index = $rkey + 1;
                        $rel['fullpath'] = $rel['path'];
                        $rel['classname'] = $rel['subtype'];
                        $rel['rowId'] = $rel['id'] . AbstractRelations::RELATION_ID_SEPARATOR . $index . AbstractRelations::RELATION_ID_SEPARATOR . $rel['type'];
                        $rel['published'] = (bool)$rel['published'];
                        $data[] = $rel;
                    }
                } else {
                    $fieldData = $object->$getter();
                    $data = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
                }
                $loadContext->objectData[$key] = $data;
                $loadContext->metaData[$key]['objectid'] = $object->getId();
                $loadContext->metaData[$key]['inherited'] = $level !== 0;
            }
        } else {
            $fieldData = $object->$getter();
            $isInheritedValue = false;

            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\CalculatedValue) {
                $fieldData = new DataObject\Data\CalculatedValue($fielddefinition->getName());
                $fieldData->setContextualData('object', null, null, null, null, null, $fielddefinition);
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            } else {
                $value = $fielddefinition->getDataForEditmode($fieldData, $object, ['objectFromVersion' => $objectFromVersion]);
            }

            // following some exceptions for special data types (localizedfields, objectbricks)
            if ($value && ($fieldData instanceof DataObject\Localizedfield || $fieldData instanceof DataObject\Classificationstore)) {
                // make sure that the localized field participates in the inheritance detection process
                $isInheritedValue = $value['inherited'];
            }
            if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Objectbricks && is_array($value)) {
                // make sure that the objectbricks participate in the inheritance detection process
                foreach ($value as $singleBrickData) {
                    if (!empty($singleBrickData['inherited'])) {
                        $isInheritedValue = true;
                    }
                }
            }

            if ($fielddefinition->isEmpty($fieldData) && !empty($parent)) {
                $this->getDataForField($parent, $key, $fielddefinition, $loadContext, $objectFromVersion, $level + 1);
                // exception for classification store. if there are no items then it is empty by definition.
                // consequence is that we have to preserve the metadata information
                if ($fielddefinition instanceof DataObject\ClassDefinition\Data\Classificationstore && $level === 0) {
                    $loadContext->objectData[$key]['metaData'] = $value['metaData'] ?? [];
                    $loadContext->objectData[$key]['inherited'] = true;
                }
            } else {
                $isInheritedValue = $isInheritedValue || ($level !== 0);
                $loadContext->metaData[$key]['objectid'] = $object->getId();

                $loadContext->objectData[$key] = $value;
                $loadContext->metaData[$key]['inherited'] = $isInheritedValue;

                if ($isInheritedValue && !$fielddefinition->isEmpty($fieldData) && !$fielddefinition->supportsInheritance()) {
                    $loadContext->objectData[$key] = null;
                    $loadContext->metaData[$key]['inherited'] = false;
                    $loadContext->metaData[$key]['hasParentValue'] = true;
                }
            }
        }
    }

    /**
     * @param DataObject\ClassDefinition[] $classes
     */
    private function prepareChildClasses(array $classes): array
    {
        $reduced = [];
        foreach ($classes as $class) {
            $reduced[] = [
                'id' => $class->getId(),
                'name' => $class->getName(),
                'inheritance' => $class->getAllowInherit(),
            ];
        }

        return $reduced;
    }

}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkCommit;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Tool\Session;
use RuntimeException;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class BulkCommitHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly RequestStack $requestStack,
    ) {}

    public function __invoke(BulkCommitPayload $payload): void
    {
        $data = $payload->data;
        $permissionMap = ['class' => 'classes', 'objectbrick' => 'objectbricks', 'fieldcollection' => 'fieldcollections', 'customlayout' => 'classes'];
        $type = $data['type'];

        if (isset($permissionMap[$type])) {
            $adminUser = $this->userContext->getAdminUser();
            if (!$adminUser?->isAllowed($permissionMap[$type])) {
                throw new AccessDeniedHttpException('Permission denied for bulk commit of type: ' . $type);
            }
        }

        $session = Session::getSessionBag($this->requestStack->getCurrentRequest()->getSession(), 'opendxp_objects');
        $filename = $session->get('class_bulk_import_file');

        $json = @file_get_contents($filename);
        $json = json_decode($json, true);

        $name = $data['name'];
        $list = $json[$type];

        foreach ($list as $item) {
            unset($item['creationDate'], $item['modificationDate'], $item['userOwner'], $item['userModification']);

            if ($type === 'class' && $item['name'] == $name) {
                $class = DataObject\ClassDefinition::getByName($name);
                if (!$class) {
                    $class = new DataObject\ClassDefinition();
                    $class->setName($name);
                }
                if (!DataObject\ClassDefinition\Service::importClassDefinitionFromJson($class, json_encode($item), true)) {
                    throw new RuntimeException('Failed to import class definition: ' . $name);
                }

                return;
            }

            if ($type === 'objectbrick' && $item['key'] == $name) {
                if (!$brick = DataObject\Objectbrick\Definition::getByKey($name)) {
                    $brick = new DataObject\Objectbrick\Definition();
                    $brick->setKey($name);
                }
                if (!DataObject\ClassDefinition\Service::importObjectBrickFromJson($brick, json_encode($item), true)) {
                    throw new RuntimeException('Failed to import objectbrick: ' . $name);
                }

                return;
            }

            if ($type === 'fieldcollection' && $item['key'] == $name) {
                if (!$fieldCollection = DataObject\Fieldcollection\Definition::getByKey($name)) {
                    $fieldCollection = new DataObject\Fieldcollection\Definition();
                    $fieldCollection->setKey($name);
                }
                if (!DataObject\ClassDefinition\Service::importFieldCollectionFromJson($fieldCollection, json_encode($item), true)) {
                    throw new RuntimeException('Failed to import field collection: ' . $name);
                }

                return;
            }

            if ($type === 'customlayout') {
                $layoutData = json_decode(base64_decode($data['name']), true);
                $className = $layoutData['className'];
                $layoutName = $layoutData['name'];

                if ($item['name'] == $layoutName && $item['className'] == $className) {
                    $class = DataObject\ClassDefinition::getByName($className);
                    if (!$class) {
                        throw new BadRequestHttpException('Class does not exist');
                    }

                    $classId = $class->getId();

                    $layoutList = new DataObject\ClassDefinition\CustomLayout\Listing();
                    $layoutList->setFilter(fn (DataObject\ClassDefinition\CustomLayout $layout) => $layout->getName() === $layoutName && $layout->getClassId() === $classId);
                    $layoutList = $layoutList->load();

                    $layoutDefinition = null;
                    if ($layoutList) {
                        $layoutDefinition = array_values($layoutList)[0];
                    }

                    if (!$layoutDefinition) {
                        $layoutDefinition = new DataObject\ClassDefinition\CustomLayout();
                        $layoutDefinition->setName($layoutName);
                        $layoutDefinition->setClassId($classId);
                    }

                    $layoutDefinition->setDescription($item['description']);
                    $layoutDef = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($item['layoutDefinitions'], true);
                    $layoutDefinition->setLayoutDefinitions($layoutDef);
                    $layoutDefinition->save();
                }
            }
        }
    }
}

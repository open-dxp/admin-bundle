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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\RequestStack;

final class DoBulkExportHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly RequestStack $requestStack,
    ) {}

    public function __invoke(): DoBulkExportResult
    {
        $session = Session::getSessionBag($this->requestStack->getCurrentRequest()->getSession(), 'opendxp_objects');
        $list = json_decode($session->get('class_bulk_export_settings'), true);

        $adminUser = $this->userContext->getAdminUser();
        $result = [];

        foreach ($list as $item) {
            if ($item['type'] === 'fieldcollection' && $adminUser->isAllowed('fieldcollections')) {
                if ($fieldCollection = DataObject\Fieldcollection\Definition::getByKey($item['name'])) {
                    $fieldCollectionJson = json_decode(DataObject\ClassDefinition\Service::generateFieldCollectionJson($fieldCollection));
                    $fieldCollectionJson->key = $item['name'];
                    $result['fieldcollection'][] = $fieldCollectionJson;
                }
            } elseif ($item['type'] === 'class' && $adminUser->isAllowed('classes')) {
                if ($class = DataObject\ClassDefinition::getByName($item['name'])) {
                    $data = json_decode(DataObject\ClassDefinition\Service::generateClassDefinitionJson($class));
                    $data->name = $item['name'];
                    $result['class'][] = $data;
                }
            } elseif ($item['type'] === 'objectbrick' && $adminUser->isAllowed('objectbricks')) {
                if ($objectBrick = DataObject\Objectbrick\Definition::getByKey($item['name'])) {
                    $objectBrickJson = json_decode(DataObject\ClassDefinition\Service::generateObjectBrickJson($objectBrick));
                    $objectBrickJson->key = $item['name'];
                    $result['objectbrick'][] = $objectBrickJson;
                }
            } elseif ($item['type'] === 'customlayout' && $adminUser->isAllowed('classes')) {
                if ($customLayout = DataObject\ClassDefinition\CustomLayout::getById($item['name'])) {
                    $classId = $customLayout->getClassId();
                    $class = DataObject\ClassDefinition::getById($classId);
                    $customLayoutJson = json_decode(DataObject\ClassDefinition\Service::generateCustomLayoutJson($customLayout));
                    $customLayoutJson->name = $customLayout->getName();
                    $customLayoutJson->className = $class->getName();
                    $result['customlayout'][] = $customLayoutJson;
                }
            }
        }

        return new DoBulkExportResult(json: json_encode($result, JSON_PRETTY_PRINT));
    }
}

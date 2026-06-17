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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use Doctrine\DBAL\ArrayParameterType;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use OpenDxp\Model\DataObject\Classificationstore;

final class AddCollectionsHandler
{
    public function __invoke(AddCollectionsPayload $payload): AddCollectionsResult
    {
        $ids = $payload->ids;
        $oid = $payload->oid;
        $fieldname = $payload->fieldname;
        $data = [];

        if (!$ids) {
            return new AddCollectionsResult(data: $data);
        }

        $db = Db::get();
        $mappedData = [];
        $groupsData = $db->fetchAllAssociative(
            'SELECT * FROM classificationstore_groups g, classificationstore_collectionrelations c
                    WHERE colId IN (?) AND g.id = c.groupId',
            [array_values(array_filter($ids, is_numeric(...)))],
            [ArrayParameterType::INTEGER]
        );

        foreach ($groupsData as $groupData) {
            $mappedData[$groupData['id']] = $groupData;
        }

        $groupIdList = [];
        $groupId = null;
        $allowedGroupIds = null;

        $object = $oid ? DataObject\Concrete::getById($oid) : null;
        if ($object) {
            $class = $object->getClass();
            /** @var DataObject\ClassDefinition\Data\Classificationstore $fd */
            $fd = $class->getFieldDefinition($fieldname);
            $allowedGroupIds = $fd->getAllowedGroupIds();
        }

        foreach ($groupsData as $groupItem) {
            $groupId = $groupItem['groupId'];
            if (!$allowedGroupIds || in_array($groupId, $allowedGroupIds)) {
                $groupIdList[] = $groupId;
            }
        }

        if ($groupIdList) {
            $groupList = new Classificationstore\GroupConfig\Listing();
            $groupCondition = 'id in (' . implode(',', $groupIdList) . ')';
            $groupList->setCondition($groupCondition);
            $groupList = $groupList->load();

            $keyCondition = 'groupId in (' . implode(',', $groupIdList) . ')';
            $keyList = new Classificationstore\KeyGroupRelation\Listing();
            $keyList->setCondition($keyCondition);
            $keyList->setOrderKey(['sorter', 'id']);
            $keyList->setOrder(['ASC', 'ASC']);
            $keyList = $keyList->load();

            foreach ($groupList as $groupData) {
                $data[$groupData->getId()] = [
                    'name' => $groupData->getName(),
                    'id' => $groupData->getId(),
                    'description' => $groupData->getDescription(),
                    'keys' => [],
                    'sorter' => (int) $mappedData[$groupData->getId()]['sorter'],
                    'collectionId' => $mappedData[$groupId]['colId'],
                ];
            }

            foreach ($keyList as $keyData) {
                $groupId = $keyData->getGroupId();

                $keys = $data[$groupId]['keys'];
                $type = $keyData->getType();
                $definition = json_decode($keyData->getDefinition(), true);
                $definition = Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);

                if (method_exists($definition, '__wakeup')) {
                    $definition->__wakeup();
                }

                $context['object'] = $object;
                $context['class'] = $object ? $object->getClass() : null;
                $context['ownerType'] = 'classificationstore';
                $context['ownerName'] = $fieldname;
                $context['keyId'] = $keyData->getKeyId();
                $context['groupId'] = $groupId;
                $context['keyDefinition'] = $definition;

                if ($definition instanceof LayoutDefinitionEnrichmentInterface) {
                    $definition = $definition->enrichLayoutDefinition($object, $context);
                }

                $keys[] = [
                    'name' => $keyData->getName(),
                    'id' => $keyData->getKeyId(),
                    'description' => $keyData->getDescription(),
                    'definition' => $definition,
                ];
                $data[$groupId]['keys'] = $keys;
            }
        }

        return new AddCollectionsResult(data: $data);
    }
}

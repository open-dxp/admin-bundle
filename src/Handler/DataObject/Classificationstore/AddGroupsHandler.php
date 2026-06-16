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

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Data\LayoutDefinitionEnrichmentInterface;
use OpenDxp\Model\DataObject\Classificationstore;

final class AddGroupsHandler
{
    public function __invoke(array $ids, int $oid, ?string $fieldname): AddGroupsResult
    {
        $object = $oid ? DataObject\Concrete::getById($oid) : null;

        $placeholders = implode(',', array_fill(0, count($ids), '?'));

        $keyList = new Classificationstore\KeyGroupRelation\Listing();
        $keyList->setCondition('groupId in (' . $placeholders . ')', $ids);
        $keyList->setOrderKey(['sorter', 'id']);
        $keyList->setOrder(['ASC', 'ASC']);
        $keyList = $keyList->load();

        $groupList = new Classificationstore\GroupConfig\Listing();
        $groupList->setCondition('id in (' . $placeholders . ')', $ids);
        $groupList->setOrder('ASC');
        $groupList->setOrderKey('id');
        $groupList = $groupList->load();

        $data = [];

        foreach ($groupList as $groupData) {
            $data[$groupData->getId()] = [
                'name' => $groupData->getName(),
                'id' => $groupData->getId(),
                'description' => $groupData->getDescription(),
                'keys' => [],
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

        return new AddGroupsResult(data: $data);
    }
}

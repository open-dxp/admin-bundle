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

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Classificationstore;
use stdClass;

final class GetRelationsHandler
{
    public function __invoke(
        array $queryAll,
        ?string $relationIds,
        int $limit,
        int $start,
        ?string $dir,
        bool $overrideSort,
        ?string $filter,
        ?string $groupId,
    ): GetRelationsResult {
        $mapping = ['keyName' => 'name', 'keyDescription' => 'description'];

        $orderKey = 'name';
        $order = 'ASC';

        $relationIdList = $relationIds ? json_decode($relationIds, true) : null;

        if ($dir !== null) {
            $order = $dir;
        }

        $sortingSettings = QueryParams::extractSortingSettings($queryAll);

        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $orderKey = $mapping[$sortingSettings['orderKey']] ?? $sortingSettings['orderKey'];
            $order = $sortingSettings['order'];
        }

        if ($overrideSort) {
            $orderKey = 'id';
            $order = 'DESC';
        }

        if ($limit === 0 && is_array($relationIdList)) {
            $limit = count($relationIdList);
        }

        $list = new Classificationstore\KeyGroupRelation\Listing();

        if ($limit > 0) {
            $list->setLimit($limit);
        }

        $list->setOffset($start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);
        $conditionParts = [];

        if ($filter !== null) {
            $db = Db::get();
            $filters = json_decode($filter);
            /** @var stdClass $f */
            foreach ($filters as $f) {
                if (!isset($f->value)) {
                    continue;
                }

                $fieldname = $mapping[$f->field];
                $conditionParts[] = $db->quoteIdentifier($fieldname) . ' LIKE ' . $db->quote('%' . $f->value . '%');
            }
        }

        if ($relationIdList === null) {
            $conditionParts[] = ' groupId = ' . $list->quote($groupId);
        }

        if ($relationIdList) {
            $relationParts = [];

            foreach ($relationIdList as $relationId) {
                $keyId = $relationId['keyId'];
                $entryGroupId = $relationId['groupId'];
                $relationParts[] = '(keyId = ' . $list->quote($keyId) . ' AND groupId = ' . $list->quote($entryGroupId) . ')';
            }

            $conditionParts[] = '(' . implode(' OR ', $relationParts) . ')';
        }

        $condition = implode(' AND ', $conditionParts);

        $list->setCondition($condition);

        $listItems = $list->load();

        $data = [];
        foreach ($listItems as $config) {
            $type = $config->getType();
            $definition = json_decode($config->getDefinition(), true);
            $definition = Classificationstore\Service::getFieldDefinitionFromJson($definition, $type);
            DataObject\Service::enrichLayoutDefinition($definition);

            $item = [
                'keyId' => $config->getKeyId(),
                'groupId' => $config->getGroupId(),
                'keyName' => $config->getName(),
                'keyDescription' => $config->getDescription(),
                'id' => $config->getGroupId() . '-' . $config->getKeyId(),
                'sorter' => $config->getSorter(),
                'layout' => $definition,
                'mandatory' => $config->isMandatory(),
            ];

            $data[] = $item;
        }

        return new GetRelationsResult(data: $data, total: $list->getTotalCount());
    }
}

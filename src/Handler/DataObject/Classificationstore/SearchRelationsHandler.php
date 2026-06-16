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
use OpenDxp\Bundle\AdminBundle\Service\Translation\AdminSearchTermResolver;
use OpenDxp\Db;
use OpenDxp\Model\DataObject\Classificationstore;
use stdClass;

final class SearchRelationsHandler
{
    public function __construct(private readonly AdminSearchTermResolver $searchTermResolver) {}

    public function __invoke(
        array $queryAll,
        ?int $storeId,
        int $limit,
        int $start,
        ?string $dir,
        bool $overrideSort,
        ?string $filter,
        ?string $searchfilter,
    ): SearchRelationsResult {
        $db = Db::get();

        $mapping = [
            'groupName' => Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '.name',
            'keyName' => Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.name',
            'keyDescription' => Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.description',
        ];

        $orderKey = 'name';
        $order = 'ASC';

        if ($dir) {
            $order = $dir;
        }

        $sortingSettings = QueryParams::extractSortingSettings($queryAll);
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $orderKey = $sortingSettings['orderKey'];
            if ($orderKey === 'keyName') {
                $orderKey = 'name';
            }
            $order = $sortingSettings['order'];
        }

        if ($overrideSort) {
            $orderKey = 'id';
            $order = 'DESC';
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
            $filters = json_decode($filter);
            /** @var stdClass $f */
            foreach ($filters as $f) {
                if (!isset($f->value)) {
                    continue;
                }

                $fieldname = $mapping[$f->property];
                $conditionParts[] = $fieldname . ' LIKE ' . $db->quote('%' . $f->value . '%');
            }
        }

        $conditionParts[] = '  groupId IN (select id from classificationstore_groups where storeId = ' . $db->quote($storeId) . ')';

        if ($searchfilter) {
            $searchFilterConditions = [];

            $searchTerms = [$searchfilter, ...$this->searchTermResolver->resolve($searchfilter)];
            foreach ($searchTerms as $searchFilterTerm) {
                $searchFilterConditions[] = Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.name LIKE ' . $db->quote('%' . $searchFilterTerm . '%')
                    . ' OR ' . Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '.name LIKE ' . $db->quote('%' . $searchFilterTerm . '%')
                    . ' OR ' . Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.description LIKE ' . $db->quote('%' . $searchFilterTerm . '%');
            }

            $conditionParts[] = '(' . implode(' OR ', $searchFilterConditions) . ')';
        }

        $condition = implode(' AND ', $conditionParts);
        $list->setCondition($condition);
        $list->setResolveGroupName(true);

        $data = [];
        foreach ($list->getList() as $config) {
            $item = [
                'keyId' => $config->getKeyId(),
                'groupId' => $config->getGroupId(),
                'keyName' => $config->getName(),
                'keyDescription' => $config->getDescription(),
                'id' => $config->getGroupId() . '-' . $config->getKeyId(),
                'sorter' => $config->getSorter(),
            ];

            $groupConfig = Classificationstore\GroupConfig::getById($config->getGroupId());
            if ($groupConfig) {
                $item['groupName'] = $groupConfig->getName();
            }

            $data[] = $item;
        }

        return new SearchRelationsResult(data: $data, total: $list->getTotalCount());
    }

}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\SearchRelations;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Resolver\Translation\AdminSearchTermResolver;
use OpenDxp\Db;
use OpenDxp\Model\DataObject\Classificationstore;
use stdClass;

final class SearchRelationsHandler
{
    public function __construct(private readonly AdminSearchTermResolver $searchTermResolver)
    {
    }

    public function __invoke(SearchRelationsPayload $payload): SearchRelationsResult
    {
        $db = Db::get();

        $mapping = [
            'groupName' => Classificationstore\GroupConfig\Dao::TABLE_NAME_GROUPS . '.name',
            'keyName' => Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.name',
            'keyDescription' => Classificationstore\KeyConfig\Dao::TABLE_NAME_KEYS . '.description',
        ];

        $orderKey = 'name';
        $order = 'ASC';

        if ($payload->dir) {
            $order = $payload->dir;
        }

        $sortingSettings = QueryParams::extractSortingSettings($payload->queryAll);
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $orderKey = $sortingSettings['orderKey'];
            if ($orderKey === 'keyName') {
                $orderKey = 'name';
            }
            $order = $sortingSettings['order'];
        }

        if ($payload->overrideSort) {
            $orderKey = 'id';
            $order = 'DESC';
        }

        $list = new Classificationstore\KeyGroupRelation\Listing();

        if ($payload->limit > 0) {
            $list->setLimit($payload->limit);
        }
        $list->setOffset($payload->start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $conditionParts = [];

        if ($payload->filter !== null) {
            $filters = json_decode($payload->filter);
            /** @var stdClass $f */
            foreach ($filters as $f) {
                if (!isset($f->value)) {
                    continue;
                }

                $fieldname = $mapping[$f->property];
                $conditionParts[] = $fieldname . ' LIKE ' . $db->quote('%' . $f->value . '%');
            }
        }

        $conditionParts[] = '  groupId IN (select id from classificationstore_groups where storeId = ' . $db->quote($payload->storeId) . ')';

        if ($payload->searchfilter) {
            $searchFilterConditions = [];

            $searchTerms = [$payload->searchfilter, ...$this->searchTermResolver->resolve($payload->searchfilter)];
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

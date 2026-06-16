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
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Classificationstore;
use stdClass;

final class GetGroupsHandler
{
    public function __construct(private readonly AdminSearchTermResolver $searchTermResolver) {}

    public function __invoke(
        array $queryAll,
        int $limit,
        int $start,
        ?string $dir,
        ?string $sort,
        bool $overrideSort,
        ?string $searchfilter,
        int $storeId,
        ?string $filter,
        ?int $oid,
        ?string $fieldname,
    ): GetGroupsResult {
        $orderKey = 'name';
        $order = 'ASC';

        if ($dir !== null) {
            $order = $dir;
        }

        if ($sort !== null) {
            $orderKey = $sort;
        }

        $sortingSettings = QueryParams::extractSortingSettings($queryAll);
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $orderKey = $sortingSettings['orderKey'];
            $order = $sortingSettings['order'];
        }

        if ($overrideSort) {
            $orderKey = 'id';
            $order = 'DESC';
        }

        $list = new Classificationstore\GroupConfig\Listing();

        $list->setLimit($limit);
        $list->setOffset($start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $conditionParts = [];
        $db = Db::get();

        if ($searchfilter !== null) {
            $searchFilterConditions = [];

            $searchTerms = [$searchfilter, ...$this->searchTermResolver->resolve($searchfilter)];
            foreach ($searchTerms as $searchFilterTerm) {
                $searchFilterConditions[] = 'name LIKE ' . $db->quote('%' . $searchFilterTerm . '%') . ' OR description LIKE ' . $db->quote('%' . $searchFilterTerm . '%');
            }

            $conditionParts[] = '(' . implode(' OR ', $searchFilterConditions) . ')';
        }

        if ($storeId) {
            $conditionParts[] = '(storeId = ' . $db->quote($storeId) . ')';
        }

        if ($filter !== null) {
            $filters = json_decode($filter);
            /** @var stdClass $f */
            foreach ($filters as $f) {
                if (!isset($f->value)) {
                    continue;
                }

                $conditionParts[] = $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%');
            }
        }

        if ($oid !== null) {
            $object = DataObject\Concrete::getById($oid);
            $class = $object->getClass();
            /** @var DataObject\ClassDefinition\Data\Classificationstore $fd */
            $fd = $class->getFieldDefinition($fieldname);
            $allowedGroupIds = $fd->getAllowedGroupIds();

            if ($allowedGroupIds) {
                $conditionParts[] = 'ID in (' . implode(',', $allowedGroupIds) . ')';
            }
        }

        $condition = implode(' AND ', $conditionParts);
        $list->setCondition($condition);

        $list->load();
        $configList = $list->getList();

        $data = [];
        foreach ($configList as $config) {
            $name = $config->getName();
            if (!$name) {
                $name = 'EMPTY';
            }
            $item = [
                'storeId' => $config->getStoreId(),
                'id' => $config->getId(),
                'name' => $name,
                'description' => $config->getDescription(),
            ];
            if ($config->getCreationDate()) {
                $item['creationDate'] = $config->getCreationDate();
            }

            if ($config->getModificationDate()) {
                $item['modificationDate'] = $config->getModificationDate();
            }

            $data[] = $item;
        }

        return new GetGroupsResult(data: $data, total: $list->getTotalCount());
    }

}

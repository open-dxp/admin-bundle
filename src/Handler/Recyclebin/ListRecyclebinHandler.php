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

namespace OpenDxp\Bundle\AdminBundle\Handler\Recyclebin;

use OpenDxp\Model\Element\Recyclebin;

final class ListRecyclebinHandler
{
    public function __invoke(
        int $limit,
        int $offset,
        string $orderKey,
        string $order,
        ?string $filterFullText,
        array $filters,
    ): ListRecyclebinResult {
        $db = \OpenDxp\Db::get();

        $list = new Recyclebin\Item\Listing();
        $list->setLimit($limit);
        $list->setOffset($offset);
        $list->setOrderKey($orderKey);
        $list->setOrder($order);

        $conditionFilters = [];

        if ($filterFullText) {
            $conditionFilters[] = '`path` LIKE ' . $list->quote('%' . $list->escapeLike($filterFullText) . '%');
        }

        foreach ($filters as $filter) {
            $operator = '=';

            $filterField = $filter['property'];
            $filterOperator = $filter['operator'];

            if ($filter['type'] === 'string') {
                $operator = 'LIKE';
            } elseif ($filter['type'] === 'numeric') {
                if ($filterOperator === 'lt') {
                    $operator = '<';
                } elseif ($filterOperator === 'gt') {
                    $operator = '>';
                } elseif ($filterOperator === 'eq') {
                    $operator = '=';
                }
            } elseif ($filter['type'] === 'date') {
                if ($filterOperator === 'lt') {
                    $operator = '<';
                } elseif ($filterOperator === 'gt') {
                    $operator = '>';
                } elseif ($filterOperator === 'eq') {
                    $operator = '=';
                }
                $filter['value'] = strtotime($filter['value']);
            } elseif ($filter['type'] === 'list') {
                $operator = '=';
            } elseif ($filter['type'] === 'boolean') {
                $operator = '=';
                $filter['value'] = (int) $filter['value'];
            }

            $value = ($filter['value'] ?? '');
            if ($operator === 'LIKE') {
                $value = '%' . $value . '%';
            }

            $field = $db->quoteIdentifier($filterField);
            if (($filter['field'] ?? false) === 'fullpath') {
                $field = 'CONCAT(`path`,filename)';
            }

            if ($filter['type'] === 'date' && $operator === '=') {
                $maxTime = $value + (86400 - 1);
                $condition = $field . ' BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxTime);
                $conditionFilters[] = $condition;
            } else {
                $conditionFilters[] = $field . $operator . ' ' . $db->quote($value);
            }
        }

        if ($conditionFilters !== []) {
            $list->setCondition(implode(' AND ', $conditionFilters));
        }

        $items = $list->load();
        $data = [];
        foreach ($items as $item) {
            $data[] = $item->getObjectVars();
        }

        return new ListRecyclebinResult(data: $data, total: $list->getTotalCount());
    }
}

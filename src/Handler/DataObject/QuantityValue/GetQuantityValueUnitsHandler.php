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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Db;
use OpenDxp\Model\DataObject\QuantityValue\Unit;

final class GetQuantityValueUnitsHandler
{
    public function __invoke(
        array $queryAll,
        int $limit,
        int $start,
        ?string $filter,
    ): GetQuantityValueUnitsResult {
        $list = new Unit\Listing();

        $order = ['ASC', 'ASC', 'ASC'];
        $orderKey = ['baseunit', 'factor', 'abbreviation'];

        $sortingSettings = QueryParams::extractSortingSettings($queryAll);

        if ($sortingSettings['orderKey']) {
            array_unshift($orderKey, $sortingSettings['orderKey']);
        }
        if ($sortingSettings['order']) {
            array_unshift($order, $sortingSettings['order']);
        }

        $list->setOrder($order);
        $list->setOrderKey($orderKey);
        $list->setLimit($limit);
        $list->setOffset($start);

        if ($filter) {
            $condition = '1 = 1';
            $filters = json_decode($filter);
            $db = Db::get();
            foreach ($filters as $f) {
                if ($f->type === 'string') {
                    $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%');
                } elseif ($f->type === 'numeric') {
                    $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' ' . $this->getOperator($f->comparison) . ' ' . $db->quote($f->value);
                }
            }
            $list->setCondition($condition);
        }

        $units = [];
        foreach ($list->getUnits() as $u) {
            $units[] = $u->getObjectVars();
        }

        return new GetQuantityValueUnitsResult($units, $list->getTotalCount());
    }

    private function getOperator(string $comparison): string
    {
        return match ($comparison) {
            'lt' => '<',
            'gt' => '>',
            default => '=',
        };
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Db;
use OpenDxp\Model\Element;

final class GetNoteListHandler
{
    public function __invoke(
        int $offset,
        ?int $limit,
        array $sortingSettings,
        ?string $filterText,
        ?string $filterJson,
        ?string $cid,
        ?string $ctype,
    ): GetNoteListResult {
        $list = new Element\Note\Listing();

        $list->setLimit($limit);
        $list->setOffset($offset);

        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $list->setOrderKey($sortingSettings['orderKey']);
            $list->setOrder($sortingSettings['order']);
        } else {
            $list->setOrderKey(['date', 'id']);
            $list->setOrder(['DESC', 'DESC']);
        }

        $conditions = [];

        if ($filterText) {
            $conditions[] = '('
                . '`title` LIKE ' . $list->quote('%' . $filterText . '%')
                . ' OR `description` LIKE ' . $list->quote('%' . $filterText . '%')
                . ' OR `type` LIKE ' . $list->quote('%' . $filterText . '%')
                . ' OR `user` IN (SELECT `id` FROM `users` WHERE `name` LIKE ' . $list->quote('%' . $filterText . '%') . ')'
                . " OR DATE_FORMAT(FROM_UNIXTIME(`date`), '%Y-%m-%d') LIKE " . $list->quote('%' . $filterText . '%')
                . ')';
        }

        if ($filterJson) {
            $db = Db::get();
            $filters = json_decode($filterJson, true) ?? [];
            $propertyKey = 'property';
            $comparisonKey = 'operator';

            foreach ($filters as $filter) {
                $operator = '=';

                if ($filter['type'] === 'string') {
                    $operator = 'LIKE';
                } elseif ($filter['type'] === 'numeric') {
                    $operator = match ($filter[$comparisonKey] ?? '') {
                        'lt' => '<',
                        'gt' => '>',
                        default => '=',
                    };
                } elseif ($filter['type'] === 'date') {
                    $operator = match ($filter[$comparisonKey] ?? '') {
                        'lt' => '<',
                        'gt' => '>',
                        default => '=',
                    };
                    $filter['value'] = strtotime($filter['value']);
                } elseif ($filter[$comparisonKey] === 'list') {
                    $operator = '=';
                } elseif ($filter[$comparisonKey] === 'boolean') {
                    $operator = '=';
                    $filter['value'] = (int) $filter['value'];
                }

                $value = ($filter['value'] ?? '');
                if ($operator === 'LIKE') {
                    $value = '%' . $value . '%';
                }

                if ($filter[$propertyKey] === 'user') {
                    $conditions[] = '`user` IN (SELECT `id` FROM `users` WHERE `name` LIKE ' . $list->quote($value) . ')';
                } elseif ($filter['type'] === 'date' && ($filter[$comparisonKey] ?? '') === 'eq') {
                    $maxTime = $value + (86400 - 1);
                    $conditions[] = '`' . $filter[$propertyKey] . '` BETWEEN ' . $db->quote($value) . ' AND ' . $db->quote($maxTime);
                } else {
                    $conditions[] = $db->quoteIdentifier($filter[$propertyKey]) . ' ' . $operator . ' ' . $db->quote($value);
                }
            }
        }

        if ($cid !== null && $ctype !== null) {
            $conditions[] = '(cid = ' . $list->quote($cid) . ' AND ctype = ' . $list->quote($ctype) . ')';
        }

        if ($conditions !== []) {
            $list->setCondition(implode(' AND ', $conditions));
        }

        $list->load();

        $notes = [];
        foreach ($list->getNotes() as $note) {
            $notes[] = Element\Service::getNoteData($note);
        }

        return new GetNoteListResult(data: $notes, total: $list->getTotalCount());
    }
}

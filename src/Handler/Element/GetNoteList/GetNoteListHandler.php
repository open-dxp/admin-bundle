<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteList;

use OpenDxp\Db;
use OpenDxp\Model\Element;
use OpenDxp\Bundle\AdminBundle\Handler\Element\NoteListPayload;

final class GetNoteListHandler
{
    public function __invoke(NoteListPayload $payload): GetNoteListResult
    {
        $list = new Element\Note\Listing();

        $list->setLimit($payload->limit);
        $list->setOffset($payload->offset);

        if ($payload->sortingSettings['orderKey'] && $payload->sortingSettings['order']) {
            $list->setOrderKey($payload->sortingSettings['orderKey']);
            $list->setOrder($payload->sortingSettings['order']);
        } else {
            $list->setOrderKey(['date', 'id']);
            $list->setOrder(['DESC', 'DESC']);
        }

        $conditions = [];

        if ($payload->filterText) {
            $conditions[] = '('
                . '`title` LIKE ' . $list->quote('%' . $payload->filterText . '%')
                . ' OR `description` LIKE ' . $list->quote('%' . $payload->filterText . '%')
                . ' OR `type` LIKE ' . $list->quote('%' . $payload->filterText . '%')
                . ' OR `user` IN (SELECT `id` FROM `users` WHERE `name` LIKE ' . $list->quote('%' . $payload->filterText . '%') . ')'
                . " OR DATE_FORMAT(FROM_UNIXTIME(`date`), '%Y-%m-%d') LIKE " . $list->quote('%' . $payload->filterText . '%')
                . ')';
        }

        if ($payload->filterJson) {
            $db = Db::get();
            $filters = json_decode($payload->filterJson, true) ?? [];
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

        if ($payload->cid !== null && $payload->ctype !== null) {
            $conditions[] = '(cid = ' . $list->quote($payload->cid) . ' AND ctype = ' . $list->quote($payload->ctype) . ')';
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

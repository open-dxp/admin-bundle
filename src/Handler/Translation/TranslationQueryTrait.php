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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation;

use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use OpenDxp\Model\Translation;

trait TranslationQueryTrait
{
    protected const string FILTER_PLACEHOLDER_NAME = 'placeHolder';

    protected function extendTranslationQuery(array $joins, Translation\Listing $list, string $tableName, array $filters): void
    {
        if ($joins) {
            $list->onCreateQueryBuilder(
                function (DoctrineQueryBuilder $select) use ($joins, $tableName, $filters): void {
                    $db = \OpenDxp\Db::get();

                    $alreadyJoined = [];

                    foreach ($joins as $join) {
                        $fieldname = $join['language'];

                        if (isset($alreadyJoined[$fieldname])) {
                            continue;
                        }
                        $alreadyJoined[$fieldname] = 1;

                        $select->addSelect($fieldname . '.text AS ' . $fieldname);
                        $select->leftJoin(
                            $tableName,
                            $tableName,
                            $fieldname,
                            '('
                            . $fieldname . '.key = ' . $tableName . '.key'
                            . ' and ' . $fieldname . '.language = ' . $db->quote($fieldname)
                            . ')'
                        );
                    }

                    $havings = $filters['conditions'];
                    if ($havings) {
                        $havings = implode(' AND ', $havings);
                        $select->having($havings);
                    }
                }
            );
        }
    }

    protected function getGridFilterCondition(array $filterParameters, string $tableName, bool $languageMode, array $validLanguages): array
    {
        $placeHolderCount = 0;
        $joins = [];
        $conditions = [];

        $db = \OpenDxp\Db::get();
        $conditionFilters = [];

        $filterJson = $filterParameters['filter'];
        if ($filterJson) {
            $propertyField = 'property';
            $operatorField = 'operator';

            $filters = json_decode($filterJson, true);
            foreach ($filters as $filter) {
                $operator = '=';
                $field = null;
                $value = null;

                $fieldname = $filter[$propertyField];
                if (in_array(ltrim($fieldname, '_'), $validLanguages)) {
                    $fieldname = ltrim($fieldname, '_');
                }
                $fieldname = str_replace('--', '', $fieldname);
                if (!$languageMode && in_array($fieldname, $validLanguages)) {
                    continue;
                }
                if ($languageMode && !in_array($fieldname, $validLanguages)) {
                    continue;
                }

                $allowedNonLanguageFields = ['key', 'type', 'creationDate', 'modificationDate'];
                if (!$languageMode && !in_array($fieldname, $allowedNonLanguageFields)) {
                    continue;
                }

                if (!$languageMode) {
                    $fieldname = $tableName . '.' . $fieldname;
                }

                if (!empty($filter['value'])) {
                    if ($filter['type'] === 'string') {
                        $operator = 'LIKE';
                        $field = $fieldname;
                        $value = '%' . $filter['value'] . '%';
                    } elseif ($filter['type'] === 'date' ||
                        (in_array($fieldname, ['modificationDate', 'creationDate']))) {
                        if ($filter[$operatorField] === 'lt') {
                            $operator = '<';
                        } elseif ($filter[$operatorField] === 'gt') {
                            $operator = '>';
                        } elseif ($filter[$operatorField] === 'eq') {
                            $operator = '=';
                            $fieldname = "UNIX_TIMESTAMP(DATE(FROM_UNIXTIME({$fieldname})))";
                        }
                        $filter['value'] = strtotime($filter['value']);
                        $field = $fieldname;
                        $value = $filter['value'];
                    }
                }

                if ($field && $value) {
                    $condition = $db->quoteIdentifier($field) . ' ' . $operator . ' ' . $db->quote($value);

                    if ($languageMode) {
                        $conditions[$fieldname] = $condition;
                        $joins[] = [
                            'language' => $fieldname,
                        ];
                    } else {
                        $placeHolderName = self::FILTER_PLACEHOLDER_NAME . $placeHolderCount;
                        $placeHolderCount++;
                        $conditionFilters[] = [
                            'condition' => $field . ' ' . $operator . ' :' . $placeHolderName,
                            'field' => $placeHolderName,
                            'value' => $value,
                        ];
                    }
                }
            }
        }

        if (!empty($filterParameters['searchString'])) {
            $conditionFilters[] = [
                'condition' => '(lower(' . $tableName . '.key) LIKE :filterTerm OR lower(' . $tableName . '.text) LIKE :filterTerm)',
                'field' => 'filterTerm',
                'value' => '%' . mb_strtolower($filterParameters['searchString']) . '%',
            ];
        }

        if ($languageMode) {
            return [
                'joins' => $joins,
                'conditions' => $conditions,
            ];
        }

        if ($conditionFilters !== []) {
            $conditions = [];
            $params = [];
            foreach ($conditionFilters as $conditionFilter) {
                $conditions[] = $conditionFilter['condition'];
                $params[$conditionFilter['field']] = $conditionFilter['value'];
            }

            $conditionFilters = [
                'condition' => implode(' AND ', $conditions),
                'params' => $params,
            ];
        }

        return $conditionFilters;
    }
}

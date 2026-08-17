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

namespace OpenDxp\Bundle\AdminBundle\Service\Translation;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Query\QueryBuilder as DoctrineQueryBuilder;
use OpenDxp\Model\Translation;

final class TranslationQueryService
{
    private const string FILTER_PLACEHOLDER_NAME = 'placeHolder';

    public function __construct(protected Connection $db)
    {
    }

    public function extendTranslationQuery(array $joins, Translation\Listing $list, string $tableName, array $filters): void
    {
        if (count($joins) === 0) {
            return;
        }

        $list->onCreateQueryBuilder(
            function (DoctrineQueryBuilder $select) use ($joins, $tableName, $filters): void {

                $alreadyJoined = [];

                foreach ($joins as $join) {
                    $fieldName = $join['language'];

                    if (isset($alreadyJoined[$fieldName])) {
                        continue;
                    }

                    $alreadyJoined[$fieldName] = 1;

                    $select->addSelect($fieldName . '.text AS ' .$fieldName);
                    $select->leftJoin(
                        $tableName,
                        $tableName,
                        $fieldName,
                        '('
                        .$fieldName . '.key = ' . $tableName . '.key'
                        . ' and ' .$fieldName . '.language = ' . $this->db->quote($fieldName)
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

    public function getGridFilterCondition(array $filterParameters, string $tableName, bool $languageMode, array $validLanguages): array
    {
        $placeHolderCount = 0;
        $joins = [];
        $conditions = [];

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

                $fieldName = $filter[$propertyField];
                if (in_array(ltrim($fieldName, '_'), $validLanguages)) {
                    $fieldName = ltrim($fieldName, '_');
                }
                $fieldName = str_replace('--', '', $fieldName);
                if (!$languageMode && in_array($fieldName, $validLanguages)) {
                    continue;
                }
                if ($languageMode && !in_array($fieldName, $validLanguages)) {
                    continue;
                }

                $allowedNonLanguageFields = ['key', 'type', 'creationDate', 'modificationDate'];
                if (!$languageMode && !in_array($fieldName, $allowedNonLanguageFields)) {
                    continue;
                }

                if (!$languageMode) {
                    $fieldName = $tableName . '.' .$fieldName;
                }

                if (!empty($filter['value'])) {
                    if ($filter['type'] === 'string') {
                        $operator = 'LIKE';
                        $field =$fieldName;
                        $value = '%' . $filter['value'] . '%';
                    } elseif ($filter['type'] === 'date' ||
                        (in_array($fieldName, ['modificationDate', 'creationDate']))) {
                        if ($filter[$operatorField] === 'lt') {
                            $operator = '<';
                        } elseif ($filter[$operatorField] === 'gt') {
                            $operator = '>';
                        } elseif ($filter[$operatorField] === 'eq') {
                            $operator = '=';
                            $fieldName = "UNIX_TIMESTAMP(DATE(FROM_UNIXTIME({$fieldName})))";
                        }
                        $filter['value'] = strtotime($filter['value']);
                        $field =$fieldName;
                        $value = $filter['value'];
                    }
                }

                if ($field && $value) {
                    $condition = $this->db->quoteIdentifier($field) . ' ' . $operator . ' ' . $this->db->quote($value);

                    if ($languageMode) {
                        $conditions[$fieldName] = $condition;
                        $joins[] = [
                            'language' =>$fieldName,
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

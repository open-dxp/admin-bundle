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

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Translation;
use OpenDxp\Tool;

final class GetTranslationsHandler
{
    use TranslationQueryTrait;

    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(
        string $domain,
        array $requestParams,
        int $limit,
        int $offset,
        ?string $filter,
        ?string $searchString,
    ): GetTranslationsResult {
        $admin = $domain === Translation::DOMAIN_ADMIN;
        $validLanguages = $admin
            ? Tool\Admin::getLanguages()
            : $this->userContext->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();
        $translation = new Translation();
        $translation->setDomain($domain);
        $tableName = $translation->getDao()->getDatabaseTableName();

        $list = new Translation\Listing();
        $list->setDomain($domain);
        $list->setOrder('asc');
        $list->setOrderKey($tableName . '.key', false);
        $list->setLanguages($validLanguages);

        $sortingSettings = QueryParams::extractSortingSettings($requestParams);

        $joins = [];

        if ($orderKey = $sortingSettings['orderKey']) {
            if (in_array(trim($orderKey, '_'), $validLanguages)) {
                $orderKey = trim($orderKey, '_');
                $joins[] = [
                    'language' => $orderKey,
                ];
                $list->setOrderKey($orderKey);
            } elseif ($list->isValidOrderKey($sortingSettings['orderKey'])) {
                $list->setOrderKey($tableName . '.' . $sortingSettings['orderKey'], false);
            }
        }
        if ($sortingSettings['order']) {
            $list->setOrder($sortingSettings['order']);
        }

        $list->setLimit($limit);
        $list->setOffset($offset);

        $filterParameters = [
            'filter' => $filter,
            'searchString' => $searchString,
        ];

        $conditions = $this->getGridFilterCondition($filterParameters, $tableName, false, $validLanguages);
        $filters = $this->getGridFilterCondition($filterParameters, $tableName, true, $validLanguages);

        if ($filters) {
            $joins = [...$joins, ...$filters['joins']];
        }

        if ($conditions !== []) {
            $list->setCondition($conditions['condition'], $conditions['params']);
        }

        $this->extendTranslationQuery($joins, $list, $tableName, $filters);

        $translations = [];
        foreach ($list->getTranslations() as $t) {
            if ($searchString && !strpos($searchString, (string) $t->getKey()) && !$t = Translation::getByKey($t->getKey(), $domain)) {
                continue;
            }

            $prefixed = [];
            foreach ($t->getTranslations() as $lang => $trans) {
                $prefixed['_' . $lang] = $trans;
            }

            $translations[] = [
                ...$prefixed,
                'key' => $t->getKey(),
                'creationDate' => $t->getCreationDate(),
                'modificationDate' => $t->getModificationDate(),
                'type' => $t->getType(),
            ];
        }

        return new GetTranslationsResult(
            translations: $translations,
            total: $list->getTotalCount(),
        );
    }
}

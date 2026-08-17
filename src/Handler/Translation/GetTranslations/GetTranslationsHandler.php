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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\GetTranslations;

use OpenDxp\Bundle\AdminBundle\Handler\Translation\TranslationPayload;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Translation\TranslationQueryService;
use OpenDxp\Model\Translation;
use OpenDxp\Tool;

final class GetTranslationsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly TranslationQueryService $translationQueryService,
    ) {}

    public function __invoke(TranslationPayload $payload): GetTranslationsResult
    {
        $admin = $payload->domain === Translation::DOMAIN_ADMIN;

        $validLanguages = $admin
            ? Tool\Admin::getLanguages()
            : $this->userContext->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();

        $translation = new Translation();
        $translation->setDomain($payload->domain);
        $tableName = $translation->getDao()->getDatabaseTableName();

        $list = new Translation\Listing();
        $list->setDomain($payload->domain);
        $list->setOrder('asc');
        $list->setOrderKey($tableName . '.key', false);
        $list->setLanguages($validLanguages);

        $list->setLimit($payload->limit);
        $list->setOffset($payload->offset ?? 0);

        $sortingSettings = QueryParams::extractSortingSettings($payload->requestParams ?? []);

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

        $filterParameters = [
            'filter' => $payload->filter,
            'searchString' => $payload->searchString,
        ];

        $conditions = $this->translationQueryService->getGridFilterCondition($filterParameters, $tableName, false, $validLanguages);
        $filters = $this->translationQueryService->getGridFilterCondition($filterParameters, $tableName, true, $validLanguages);

        if ($filters) {
            $joins = [...$joins, ...$filters['joins']];
        }

        if ($conditions !== []) {
            $list->setCondition($conditions['condition'], $conditions['params']);
        }

        $this->translationQueryService->extendTranslationQuery($joins, $list, $tableName, $filters);

        $translations = [];
        foreach ($list->getTranslations() as $t) {

            if (
                $payload->searchString &&
                !strpos($payload->searchString, (string) $t->getKey()) &&
                !$t = Translation::getByKey($t->getKey(), $payload->domain)
            ) {
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
            data: $translations,
            total: $list->getTotalCount(),
        );
    }
}

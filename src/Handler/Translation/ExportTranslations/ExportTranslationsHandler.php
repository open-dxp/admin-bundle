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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\ExportTranslations;

use Doctrine\DBAL\Exception\SyntaxErrorException;
use InvalidArgumentException;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Translation\TranslationQueryService;
use OpenDxp\Model\Element;
use OpenDxp\Model\Translation;
use OpenDxp\Tool;
use OpenDxp\Tool\Text;

final class ExportTranslationsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly TranslationQueryService $translationQueryService,
    ) {}

    public function __invoke(ExportTranslationsPayload $payload): ExportTranslationsResult
    {
        $admin = $payload->domain === Translation::DOMAIN_ADMIN;

        $allowedLanguages = $admin
            ? Tool\Admin::getLanguages()
            : $this->userContext->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();

        $translation = new Translation();
        $translation->setDomain($payload->domain);
        $tableName = $translation->getDao()->getDatabaseTableName();

        $list = new Translation\Listing();
        $list->setDomain($payload->domain);
        $list->setOrder('asc');
        $list->setOrderKey($tableName . '.key', false);

        $joins = [];

        $filterParameters = [
            'filter'       => $payload->filter,
            'searchString' => $payload->searchString,
        ];

        $conditions = $this->translationQueryService->getGridFilterCondition($filterParameters, $tableName, false, $allowedLanguages);
        $filters = $this->translationQueryService->getGridFilterCondition($filterParameters, $tableName, true, $allowedLanguages);

        if ($filters) {
            $joins = [...$joins, ...$filters['joins']];
        }

        if ($conditions !== []) {
            $list->setCondition($conditions['condition'], $conditions['params']);
        }

        $this->translationQueryService->extendTranslationQuery($joins, $list, $tableName, $filters);

        try {
            $list->load();
        } catch (SyntaxErrorException) {
            throw new InvalidArgumentException('Check your arguments.');
        }

        $translations = [];
        $translationObjects = $list->getTranslations();

        if ($translationObjects === []) {
            if ($admin) {
                $t = new Translation();
                $t->setDomain(Translation::DOMAIN_ADMIN);
                $languages = Tool\Admin::getLanguages();
            } else {
                $t = new Translation();
                $languages = $allowedLanguages;
            }

            foreach ($languages as $language) {
                $t->addTranslation($language, '');
            }

            $translationObjects[] = $t;
        }

        foreach ($translationObjects as $t) {
            $row = $t->getTranslations();
            $row = Element\Service::escapeCsvRecord($row);
            $translations[] = [
                'key' => $t->getKey(),
                'creationDate' => $t->getCreationDate(),
                'modificationDate' => $t->getModificationDate(),
                ...$row
            ];
        }

        $columns = array_keys($translations[0]);

        if ($admin) {
            $languages = Tool\Admin::getLanguages();
        } else {
            $languages = $allowedLanguages;
        }

        foreach ($languages as $l) {
            if (!in_array($l, $columns)) {
                $columns[] = $l;
            }
        }

        foreach ($columns as $key => $column) {
            if (strtolower(trim($column)) !== 'key' && !in_array($column, $languages)) {
                unset($columns[$key]);
            }
        }
        $columns = array_values($columns);

        $headerRow = [];
        foreach ($columns as $value) {
            $headerRow[] = '"' . $value . '"';
        }
        $csv = implode(';', $headerRow) . "\r\n";

        foreach ($translations as $t) {
            $tempRow = [];
            foreach ($columns as $key) {
                $value = $t[$key] ?? null;
                if (is_string($value)) {
                    $value = Text::removeLineBreaks($value);
                    $value = str_replace('"', '&quot;', $value);
                    $tempRow[$key] = '"' . $value . '"';
                } else {
                    $tempRow[$key] = $value;
                }
            }
            $csv .= implode(';', $tempRow) . "\r\n";
        }

        return new ExportTranslationsResult(csv: $csv, domain: $payload->domain ?? '');
    }
}

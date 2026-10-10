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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\GetTranslations;

use OpenDxp\Bundle\AdminBundle\Handler\Translation\TranslationPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Translation\TranslationQueryService;
use OpenDxp\Model\Translation;
use OpenDxp\Tool;

final class GetTranslationsHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly TranslationQueryService $translationQueryService,
    ) {
    }

    public function __invoke(TranslationPayload $payload): GetTranslationsResult
    {
        $admin = $payload->domain === Translation::DOMAIN_ADMIN;

        $validLanguages = $admin
            ? Tool\Admin::getLanguages()
            : $this->userContext->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();

        $list = $this->translationQueryService->createListing(
            $payload->domain,
            $validLanguages,
            $payload->requestParams ?? [],
            $payload->filter,
            $payload->searchString,
        );

        $list->setLimit($payload->limit);
        $list->setOffset($payload->offset ?? 0);

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

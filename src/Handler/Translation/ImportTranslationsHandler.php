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

use Locale;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Translation;
use OpenDxp\Tool;

final class ImportTranslationsHandler
{
    public function __construct(
        private readonly LocaleServiceInterface $localeService,
        private readonly AdminUserContextInterface $userContext,
    ) {}

    public function __invoke(
        string $tmpFile,
        string $domain,
        bool $overwrite,
        ?object $dialect,
        bool $enrichDelta,
        string $flagUrlTemplate,
    ): ImportTranslationsResult {
        $admin = $domain === Translation::DOMAIN_ADMIN;
        $allowedLanguages = $admin
            ? Tool\Admin::getLanguages()
            : $this->userContext->getAdminUser()->getAllowedLanguagesForEditingWebsiteTranslations();
        $delta = Translation::importTranslationsFromFile(
            $tmpFile,
            $domain,
            $overwrite,
            $allowedLanguages,
            $dialect
        );

        if (is_file($tmpFile)) {
            @unlink($tmpFile);
        }

        if ($enrichDelta) {
            $enrichedDelta = [];
            foreach ($delta as $item) {
                $lg = $item['lg'];
                $currentLocale = $this->localeService->findLocale();
                $item['lgname'] = Locale::getDisplayLanguage($lg, $currentLocale);
                $item['icon'] = str_replace('{language}', $lg, $flagUrlTemplate);
                $item['current'] = $item['text'];
                $enrichedDelta[] = $item;
            }

            return new ImportTranslationsResult(delta: $enrichedDelta);
        }

        return new ImportTranslationsResult(delta: []);
    }
}

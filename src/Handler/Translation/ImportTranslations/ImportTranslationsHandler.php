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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\ImportTranslations;

use Locale;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\TranslationImportSessionGateway;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\Translation;
use OpenDxp\Tool;
use Symfony\Component\Routing\RouterInterface;

final class ImportTranslationsHandler
{
    public function __construct(
        private readonly LocaleServiceInterface $localeService,
        private readonly AdminUserContextInterface $userContext,
        private readonly RouterInterface $router,
        private readonly TranslationImportSessionGateway $translationImportSession,
    ) {
    }

    public function __invoke(ImportTranslationsPayload $payload): ImportTranslationsResult
    {
        $tmpFile = $this->translationImportSession->getImportFile();
        $admin = $payload->domain === Translation::DOMAIN_ADMIN;
        $allowedLanguages = $admin
            ? Tool\Admin::getLanguages()
            : $this->userContext->getAdminUser()->getAllowedLanguagesForEditingWebsiteTranslations();
        $delta = Translation::importTranslationsFromFile(
            $tmpFile,
            $payload->domain,
            $payload->overwrite,
            $allowedLanguages,
            $payload->dialect
        );

        if (is_file($tmpFile)) {
            @unlink($tmpFile);
        }

        if ($payload->enrichDelta) {
            $flagUrlTemplate = $this->router->generate('opendxp_admin_misc_getlanguageflag', ['language' => '{language}']);
            $flagUrlTemplate = str_replace('%7Blanguage%7D', '{language}', $flagUrlTemplate);
            $enrichedDelta = [];
            foreach ($delta as $item) {
                $lg = $item['lg'];
                $currentLocale = $this->localeService->findLocale();
                $item['lgname'] = Locale::getDisplayLanguage($lg, $currentLocale);
                $item['icon'] = str_replace('{language}', $lg, $flagUrlTemplate);
                $item['current'] = $item['text'];
                $enrichedDelta[] = $item;
            }

            return new ImportTranslationsResult(delta: base64_encode(json_encode($enrichedDelta) ?: ''));
        }

        return new ImportTranslationsResult();
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc\GetJsonTranslations;

use Locale;
use OpenDxp\Translation\Translator;

final class GetJsonTranslationsHandler
{
    public function __construct(
        private readonly Translator $translator,
    ) {}

    public function __invoke(GetJsonTranslationsPayload $payload): GetJsonTranslationsResult
    {
        $this->translator->lazyInitialize('admin', $payload->language);

        $translations = [];

        $fallbackLanguages = [];
        if (null !== Locale::getRegion($payload->language)) {
            $fallbackLanguages[] = Locale::getPrimaryLanguage($payload->language);
        }
        if ($payload->language !== 'en') {
            $fallbackLanguages[] = 'en';
        }

        foreach (['admin', 'admin_ext'] as $domain) {
            $translations = array_replace($translations, $this->translator->getCatalogue($payload->language)->all($domain));

            foreach ($fallbackLanguages as $fallbackLanguage) {
                $this->translator->lazyInitialize($domain, $fallbackLanguage);
                foreach ($this->translator->getCatalogue($fallbackLanguage)->all($domain) as $key => $value) {
                    if (empty($translations[$key])) {
                        $translations[$key] = $value;
                    }
                }
            }
        }

        return new GetJsonTranslationsResult(translations: $translations);
    }
}

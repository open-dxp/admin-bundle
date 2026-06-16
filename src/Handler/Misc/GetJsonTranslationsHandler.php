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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc;

use Locale;
use OpenDxp\Translation\Translator;

final class GetJsonTranslationsHandler
{
    public function __invoke(Translator $translator, ?string $language): GetJsonTranslationsResult
    {
        $translator->lazyInitialize('admin', $language);

        $translations = [];

        $fallbackLanguages = [];
        if (null !== Locale::getRegion($language)) {
            $fallbackLanguages[] = Locale::getPrimaryLanguage($language);
        }
        if ($language !== 'en') {
            $fallbackLanguages[] = 'en';
        }

        foreach (['admin', 'admin_ext'] as $domain) {
            $translations = array_replace($translations, $translator->getCatalogue($language)->all($domain));

            foreach ($fallbackLanguages as $fallbackLanguage) {
                $translator->lazyInitialize($domain, $fallbackLanguage);
                foreach ($translator->getCatalogue($fallbackLanguage)->all($domain) as $key => $value) {
                    if (empty($translations[$key])) {
                        $translations[$key] = $value;
                    }
                }
            }
        }

        return new GetJsonTranslationsResult(translations: $translations);
    }
}

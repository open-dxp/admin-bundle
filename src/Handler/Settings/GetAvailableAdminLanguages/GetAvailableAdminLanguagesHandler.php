<?php

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableAdminLanguages;

use OpenDxp\Tool;

final class GetAvailableAdminLanguagesHandler
{
    public function __invoke(): GetAvailableAdminLanguagesResult
    {
        $langs = [];
        $availableLanguages = Tool\Admin::getLanguages();
        $locales = Tool::getSupportedLocales();

        foreach ($availableLanguages as $lang) {
            if (array_key_exists($lang, $locales)) {
                $langs[] = ['language' => $lang, 'display' => $locales[$lang]];
            }
        }

        usort($langs, static fn ($a, $b) => strcmp($a['display'], $b['display']));

        return new GetAvailableAdminLanguagesResult(langs: $langs);
    }
}

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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle;

final class Tool
{
    /**
     * @internal
     */
    public static function getLanguageFlagFile(string $language, bool $absolutePath = true, bool $includeUnknown = true): string
    {
        $basePath = '/bundles/opendxpadmin/img/flags';
        $iconFsBasePath = OPENDXP_WEB_ROOT . $basePath;

        if ($absolutePath) {
            $basePath = OPENDXP_WEB_ROOT . $basePath;
        }

        $code = strtolower($language);
        $code = str_replace('_', '-', $code);
        $countryCode = null;
        $fallbackLanguageCode = null;

        $parts = explode('-', $code);
        if (count($parts) > 1) {
            $countryCode = array_pop($parts);
            $fallbackLanguageCode = $parts[0];
        }

        $languageFsPath = $iconFsBasePath . '/languages/' . $code . '.svg';
        $countryFsPath = $iconFsBasePath . '/countries/' . $countryCode . '.svg';
        $fallbackFsLanguagePath = $iconFsBasePath . '/languages/' . $fallbackLanguageCode . '.svg';

        $iconPath = '';
        if ($includeUnknown) {
            $iconPath = ($absolutePath ? $iconFsBasePath : $basePath) . '/countries/_unknown.svg';
        }

        $languageCountryMapping = [
            'aa' => 'er', 'af' => 'za', 'am' => 'et', 'as' => 'in', 'ast' => 'es', 'asa' => 'tz',
            'az' => 'az', 'bas' => 'cm', 'eu' => 'es', 'be' => 'by', 'bem' => 'zm', 'bez' => 'tz', 'bg' => 'bg',
            'bm' => 'ml', 'bn' => 'bd', 'br' => 'fr', 'brx' => 'in', 'bs' => 'ba', 'cs' => 'cz', 'da' => 'dk',
            'de' => 'de', 'dz' => 'bt', 'el' => 'gr', 'en' => 'gb', 'es' => 'es', 'et' => 'ee', 'fi' => 'fi',
            'fo' => 'fo', 'fr' => 'fr', 'ga' => 'ie', 'gv' => 'im', 'he' => 'il', 'hi' => 'in', 'hr' => 'hr',
            'hu' => 'hu', 'hy' => 'am', 'id' => 'id', 'ig' => 'ng', 'is' => 'is', 'it' => 'it', 'ja' => 'jp',
            'ka' => 'ge', 'os' => 'ge', 'kea' => 'cv', 'kk' => 'kz', 'kl' => 'gl', 'km' => 'kh', 'ko' => 'kr',
            'lg' => 'ug', 'lo' => 'la', 'lt' => 'lt', 'mg' => 'mg', 'mk' => 'mk', 'mn' => 'mn', 'ms' => 'my',
            'mt' => 'mt', 'my' => 'mm', 'nb' => 'no', 'ne' => 'np', 'nl' => 'nl', 'nn' => 'no', 'pl' => 'pl',
            'pt' => 'pt', 'ro' => 'ro', 'ru' => 'ru', 'sg' => 'cf', 'sk' => 'sk', 'sl' => 'si', 'sq' => 'al',
            'sr' => 'rs', 'sv' => 'se', 'swc' => 'cd', 'th' => 'th', 'to' => 'to', 'tr' => 'tr', 'tzm' => 'ma',
            'uk' => 'ua', 'uz' => 'uz', 'vi' => 'vn', 'zh' => 'cn', 'gd' => 'gb-sct', 'gd-gb' => 'gb-sct',
            'cy' => 'gb-wls', 'cy-gb' => 'gb-wls', 'fy' => 'nl', 'xh' => 'za', 'yo' => 'bj', 'zu' => 'za',
            'ta' => 'lk', 'te' => 'in', 'ss' => 'za', 'sw' => 'ke', 'so' => 'so', 'si' => 'lk', 'ii' => 'cn',
            'zh-hans' => 'cn',  'zh-hant' => 'cn', 'sn' => 'zw', 'rm' => 'ch', 'pa' => 'in', 'fa' => 'ir', 'lv' => 'lv', 'gl' => 'es',
            'fil' => 'ph',
        ];

        if (array_key_exists($code, $languageCountryMapping)) {
            $iconPath = $basePath . '/countries/' . $languageCountryMapping[$code] . '.svg';
        } elseif (file_exists($languageFsPath)) {
            $iconPath = $basePath . '/languages/' . $code . '.svg';
        } elseif ($countryCode && file_exists($countryFsPath)) {
            $iconPath = $basePath . '/countries/' . $countryCode . '.svg';
        } elseif ($fallbackLanguageCode && file_exists($fallbackFsLanguagePath)) {
            $iconPath = $basePath . '/languages/' . $fallbackLanguageCode . '.svg';
        }

        return $iconPath;
    }
}

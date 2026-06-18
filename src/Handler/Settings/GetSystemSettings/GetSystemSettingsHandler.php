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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\GetSystemSettings;

use OpenDxp\SystemSettingsConfig;
use OpenDxp\Tool;

final class GetSystemSettingsHandler
{
    public function __construct(private readonly SystemSettingsConfig $config)
    {
    }

    public function __invoke(): GetSystemSettingsResult
    {
        $config = $this->config->getSystemSettingsConfig();

        // If required languages is empty it's the same as if all languages are required. Therefore, we
        // need to overwrite the value with the valid languages value to have all languages required
        if (empty($config['general']['required_languages'])) {
            $config['general']['required_languages'] = $config['general']['valid_languages'];
        }

        $values = [
            'general' => $config['general'],
            'documents' => $config['documents'],
            'assets' => $config['assets'],
            'objects' => $config['objects'],
            'email' => $config['email'],
            'writeable' => $config['writeable'],
        ];

        $locales = Tool::getSupportedLocales();
        $languageOptions = [];
        $validLanguages = [];

        foreach ($locales as $short => $translation) {
            if (!empty($short)) {
                $languageOptions[] = ['language' => $short, 'display' => $translation . " ($short)"];
                $validLanguages[] = $short;
            }
        }

        foreach ($values['general']['valid_languages'] as $existingValue) {
            if (!in_array($existingValue, $validLanguages, true)) {
                $languageOptions[] = ['language' => $existingValue, 'display' => $existingValue];
            }
        }

        return new GetSystemSettingsResult(values: $values, languages: $languageOptions);
    }
}

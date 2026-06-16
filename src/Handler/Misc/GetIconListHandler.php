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

use OpenDxp\Bundle\AdminBundle\Tool as AdminTool;
use OpenDxp\Helper\FileSystemHelper;
use OpenDxp\Tool;

final class GetIconListHandler
{
    public function __invoke(?string $type): GetIconListResult
    {
        $publicDir = OPENDXP_WEB_ROOT . '/bundles/opendxpadmin';
        $iconDir = $publicDir . '/img';

        $icons = match ($type) {
            'color' => FileSystemHelper::scanDirectory($iconDir . '/flat-color-icons/'),
            'white' => FileSystemHelper::scanDirectory($iconDir . '/flat-white-icons/'),
            'twemoji' => FileSystemHelper::scanDirectory($iconDir . '/twemoji/'),
            'flags' => $this->getFlags(),
            default => []
        };

        $source = match ($type) {
            'color', 'white' =>
                'based on the ' .
                '<a href="https://github.com/google/material-design-icons/blob/master/LICENSE" target="_blank">Material Design Icons</a>',
            'twemoji' =>
                'based on the ' .
                '<a href="https://github.com/twitter/twemoji/blob/master/LICENSE" target="_blank">Twemoji icons</a>',
            default => ''
        };

        $extraInfo = null;
        if ($type === 'twemoji') {
            $extraInfo = 'ℹ Click on icon with green border to display all its related variants. Click on the letter to display flags with the clicked initial';
        }

        $iconsCss = file_get_contents($publicDir . '/css/icons.css');

        return new GetIconListResult(
            icons: $icons,
            iconsCss: $iconsCss !== false ? $iconsCss : '',
            type: $type,
            extraInfo: $extraInfo,
            source: $source,
        );
    }

    private function getFlags(): array
    {
        $locales = Tool::getSupportedLocales();
        $languageOptions = [];
        foreach (array_keys($locales) as $short) {
            if (!empty($short)) {
                $flag = AdminTool::getLanguageFlagFile($short, true, false);
                if ($flag) {
                    $languageOptions[] = $flag;
                }
            }
        }

        $languageOptions = array_unique($languageOptions);
        sort($languageOptions);

        return $languageOptions;
    }
}

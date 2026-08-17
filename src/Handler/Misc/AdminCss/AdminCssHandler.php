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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc\AdminCss;

use OpenDxp\Bundle\AdminBundle\CustomView\Config as CustomViewConfig;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Tool;
use OpenDxp\Tool\Admin as AdminTool;

final class AdminCssHandler
{
    public function __invoke(): AdminCssResult
    {
        $customviews = CustomViewConfig::get();

        $languages = Tool::getValidLanguages();
        $adminLanguages = AdminTool::getLanguages();
        $languages = array_unique([...$languages, ...$adminLanguages]);

        return new AdminCssResult(
            customviews: $customviews,
            adminSettings: AdminConfig::get(),
            languages: $languages,
        );
    }
}

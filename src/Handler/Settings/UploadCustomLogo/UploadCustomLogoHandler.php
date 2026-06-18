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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\UploadCustomLogo;

use Exception;
use OpenDxp\Tool;

final class UploadCustomLogoHandler
{
    private const string LOGO_PATH = 'custom-logo.image';

    private const array ALLOWED_EXTENSIONS = ['svg', 'png', 'jpg'];

    public function __invoke(string $pathname, string $extension): void
    {
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Unsupported file format.');
        }

        Tool\Storage::get('admin')->writeStream(self::LOGO_PATH, fopen($pathname, 'rb'));
    }
}

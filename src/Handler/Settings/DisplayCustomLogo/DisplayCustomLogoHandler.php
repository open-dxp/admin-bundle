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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\DisplayCustomLogo;

use Exception;
use OpenDxp\Tool;

final class DisplayCustomLogoHandler
{
    private const string LOGO_PATH = 'custom-logo.image';

    public function __invoke(DisplayCustomLogoPayload $payload): DisplayCustomLogoResult
    {
        $mime = 'image/svg+xml';
        $logoFile = OPENDXP_WEB_ROOT . '/bundles/opendxpadmin/img/' . ($payload->white ? 'logo-claim-white.svg' : 'logo-claim-gray.svg');
        $stream = fopen($logoFile, 'rb');

        $storage = Tool\Storage::get('admin');
        if ($storage->fileExists(self::LOGO_PATH)) {
            try {
                $mime = $storage->mimeType(self::LOGO_PATH);
                $stream = $storage->readStream(self::LOGO_PATH);
            } catch (Exception) {
                // keep default stream and mime on storage error
            }
        }

        return new DisplayCustomLogoResult(mime: $mime, stream: $stream);
    }
}

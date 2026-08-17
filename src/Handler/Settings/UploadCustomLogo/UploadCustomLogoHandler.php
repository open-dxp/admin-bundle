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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\UploadCustomLogo;

use Exception;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UploadCustomLogoHandler
{
    private const string LOGO_PATH = 'custom-logo.image';

    private const array ALLOWED_EXTENSIONS = ['svg', 'png', 'jpg'];

    public function __invoke(UploadCustomLogoPayload $payload): void
    {
        if (!$payload->logoFile instanceof UploadedFile) {
            throw new BadRequestHttpException('No file uploaded.');
        }

        $extension = $payload->logoFile->guessExtension() ?? '';
        if (!in_array($extension, self::ALLOWED_EXTENSIONS, true)) {
            throw new Exception('Unsupported file format.');
        }

        Tool\Storage::get('admin')->writeStream(self::LOGO_PATH, fopen($payload->logoFile->getPathname(), 'rb'));
    }
}

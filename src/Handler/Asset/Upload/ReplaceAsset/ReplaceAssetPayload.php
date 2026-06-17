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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAsset;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class ReplaceAssetPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly string $filePath = '',
        public readonly string $originalFilename = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');

        return new static(
            id:               $request->query->getInt('id'),
            filePath:         $file->getPathname(),
            originalFilename: $file->getClientOriginalName(),
        );
    }
}

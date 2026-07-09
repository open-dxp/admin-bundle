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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZip;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class ImportZipPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $parentId = 0,
        public readonly string $uploadedFilePath = '',
        public readonly ?string $allowOverwrite = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        /** @var ?UploadedFile $file */
        $file = $request->files->get('Filedata');
        if (!$file instanceof UploadedFile || !is_file($file->getPathname())) {
            throw new AdminOperationFailedException('Something went wrong, please check upload_max_filesize and post_max_size in your php.ini as well as the write permissions on the file system');
        }

        return new static(
            parentId:         $request->query->getInt('parentId'),
            uploadedFilePath: $file->getPathname(),
            allowOverwrite:   $request->query->getString('allowOverwrite') ?: null,
        );
    }
}

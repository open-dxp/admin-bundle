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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ImportUpload;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class ImportUploadPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $fileContents = '',
        public string $importId = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        /** @var UploadedFile|null $file */
        $file = $request->files->get('Filedata');

        return new static(
            fileContents: $file !== null ? (file_get_contents($file->getPathname()) ?: '') : '',
            importId: $request->request->getString('importId'),
        );
    }
}

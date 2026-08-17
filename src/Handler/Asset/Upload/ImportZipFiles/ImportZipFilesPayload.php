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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ImportZipFiles;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ImportZipFilesPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $parentId = 0,
        public readonly string $jobId = '',
        public readonly int $offset = 0,
        public readonly int $limit = 0,
        public readonly bool $allowOverwrite = false,
        public readonly bool $isLast = false,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            parentId:      $request->request->getInt('parentId'),
            jobId:         $request->request->getString('jobId'),
            offset:        $request->request->getInt('offset'),
            limit:         $request->request->getInt('limit'),
            allowOverwrite: $request->request->getString('allowOverwrite') === 'true',
            isLast:        (bool) $request->request->get('last'),
        );
    }
}

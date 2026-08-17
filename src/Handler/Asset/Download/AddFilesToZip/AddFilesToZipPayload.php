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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\AddFilesToZip;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddFilesToZipPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id = 0,
        public readonly ?string $selectedIds = null,
        public readonly int $offset = 0,
        public readonly int $limit = 0,
        public readonly string $jobId = '',
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id:          $request->query->getInt('id'),
            selectedIds: $request->query->getString('selectedIds') ?: null,
            offset:      $request->query->getInt('offset'),
            limit:       $request->query->getInt('limit'),
            jobId:       $request->query->getString('jobId'),
        );
    }
}

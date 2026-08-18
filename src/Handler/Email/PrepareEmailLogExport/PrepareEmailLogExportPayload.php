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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\PrepareEmailLogExport;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class PrepareEmailLogExportPayload implements ExtJsPayloadInterface
{
    /**
     * @param int[] $ids
     */
    public function __construct(
        public readonly ?int $documentId = null,
        public readonly ?string $filter = null,
        public readonly array $ids = [],
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            documentId: $request->request->has('documentId') ? (int) $request->request->getString('documentId') : null,
            filter: $request->request->has('filter') ? $request->request->getString('filter') : null,
            ids: array_map(intval(...), $request->request->all('ids')),
        );
    }
}

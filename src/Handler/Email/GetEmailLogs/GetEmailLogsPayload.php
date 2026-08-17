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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\GetEmailLogs;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetEmailLogsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?int $documentId = null,
        public readonly int $limit = 50,
        public readonly int $start = 0,
        public readonly ?string $filter = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            documentId: $request->request->has('documentId') ? (int) $request->request->get('documentId') : null,
            limit: (int) $request->request->get('limit', 50),
            start: (int) $request->request->get('start', 0),
            filter: $request->request->has('filter') ? $request->request->get('filter') : null,
        );
    }
}

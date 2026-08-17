<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow\ShowGraph;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ShowGraphPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $ctype,
        public readonly int $cid,
        public readonly ?string $workflowName = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            ctype: $request->query->getString('ctype'),
            cid: $request->query->getInt('cid'),
            workflowName: $request->query->has('workflow') ? $request->query->getString('workflow') : null,
        );
    }
}

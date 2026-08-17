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

namespace OpenDxp\Bundle\AdminBundle\Handler\Workflow\GetWorkflowForm;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetWorkflowFormPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $ctype,
        public readonly int $cid,
        public readonly string $workflowName,
        public readonly string $transitionName,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            ctype: $request->request->has('ctype') ? $request->request->getString('ctype') : $request->query->getString('ctype'),
            cid: (int) ($request->request->has('cid') ? $request->request->getString('cid') : $request->query->getString('cid')),
            workflowName: $request->request->getString('workflowName'),
            transitionName: $request->request->getString('transitionName'),
        );
    }
}

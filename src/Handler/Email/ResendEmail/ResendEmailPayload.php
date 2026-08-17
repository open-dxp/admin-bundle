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

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ResendEmail;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ResendEmailPayload implements ExtJsPayloadInterface
{
    /** @param array<string, string|null> $fieldOverrides */
    public function __construct(
        public readonly int $id,
        public readonly array $fieldOverrides = [],
        public readonly bool $useOriginalRecipients = false,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: (int) $request->request->get('id'),
            fieldOverrides: [
                'from' => $request->request->get('from') ?: null,
                'to' => $request->request->get('to') ?: null,
                'cc' => $request->request->get('cc') ?: null,
                'bcc' => $request->request->get('bcc') ?: null,
                'replyto' => $request->request->get('replyto') ?: null,
            ],
            useOriginalRecipients: $request->request->getBoolean('useOriginalRecipients'),
        );
    }
}

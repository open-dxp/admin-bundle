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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\DeleteDocument;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteDocumentPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $type,
        public int $id,
        public int $amount,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            type: $request->request->getString('type'),
            id: (int) $request->request->getString('id'),
            amount: (int) $request->request->getString('amount'),
        );
    }
}

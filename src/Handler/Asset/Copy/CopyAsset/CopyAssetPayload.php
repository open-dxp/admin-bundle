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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyAsset;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CopyAssetPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $sourceId,
        public readonly int $targetId,
        public readonly string $type,
        public readonly ?int $sourceParentId,
        public readonly ?int $targetParentId,
        public readonly string $transactionId,
        public readonly string $saveParentId,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            sourceId:       (int) $request->request->getString('sourceId'),
            targetId:       (int) $request->request->getString('targetId'),
            type:           $request->request->getString('type'),
            sourceParentId: $request->request->has('targetParentId') ? (int) $request->request->getString('sourceParentId') : null,
            targetParentId: $request->request->has('targetParentId') ? (int) $request->request->getString('targetParentId') : null,
            transactionId:  $request->request->getString('transactionId'),
            saveParentId:   $request->request->getString('saveParentId'),
        );
    }
}

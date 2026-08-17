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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\UpdateAsset;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateAssetPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $assetId = 0,
        public readonly array $updateData = [],
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            assetId:    (int) $request->request->getString('id'),
            updateData: [...$request->request->all(), ...$request->query->all()],
        );
    }
}

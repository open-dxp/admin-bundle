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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\ExecuteAssetBatch;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ExecuteAssetBatchPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?array $data = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $raw = $request->request->getString('data');

        return new static(
            data: $raw !== '' ? json_decode($raw, true) : null,
        );
    }
}

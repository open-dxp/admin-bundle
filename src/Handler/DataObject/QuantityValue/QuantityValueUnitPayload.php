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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final readonly class QuantityValueUnitPayload implements ExtJsPayloadInterface
{
    public function __construct(public readonly array $data)
    {
    }

    public static function fromRequest(Request $request): static
    {
        $data = json_decode($request->request->getString('data'), true) ?? [];
        if (!is_array($data)) {
            throw new BadRequestHttpException('Invalid data format');
        }

        return new static(data: $data);
    }
}

<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Payload\DataObject;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateDataObjectPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $ids = [],
        public array $values = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        $rawIds = json_decode($request->request->getString('id'), true);

        return new static(
            ids: is_array($rawIds) ? array_map('intval', $rawIds) : [(int) ($rawIds ?? 0)],
            values: json_decode($request->request->getString('values'), true) ?? [],
        );
    }
}

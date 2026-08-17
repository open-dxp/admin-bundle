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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SaveCustomLayout;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveCustomLayoutPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $id = '',
        public readonly array $configuration = [],
        public readonly array $values = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            id: $request->request->getString('id'),
            configuration: json_decode($request->request->getString('configuration'), true) ?? [],
            values: json_decode($request->request->getString('values'), true) ?? [],
        );
    }
}

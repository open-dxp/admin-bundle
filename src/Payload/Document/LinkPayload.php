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

namespace OpenDxp\Bundle\AdminBundle\Payload\Document;

use Symfony\Component\HttpFoundation\Request;

final class LinkPayload
{
    public function __construct(
        public readonly string $task,
        public readonly ?array $data,
        public readonly ?array $properties,
        public readonly ?array $scheduler,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            task: strtolower($request->query->getString('task')),
            data: $request->request->has('data')
                ? (json_decode($request->request->getString('data'), true) ?? null)
                : null,
            properties: $request->request->has('properties')
                ? (json_decode($request->request->getString('properties'), true) ?? null)
                : null,
            scheduler: $request->request->has('scheduler')
                ? (json_decode($request->request->getString('scheduler'), true) ?? null)
                : null,
        );
    }
}

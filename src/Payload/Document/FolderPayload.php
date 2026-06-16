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

final class FolderPayload
{
    public function __construct(
        public readonly ?array $properties,
    ) {}

    public static function fromRequest(Request $request): self
    {
        return new self(
            properties: $request->request->has('properties')
                ? (json_decode($request->request->getString('properties'), true) ?? null)
                : null,
        );
    }
}

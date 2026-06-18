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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\CopyInfo;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CopyInfoPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $type = null,
        public readonly int $sourceId = 0,
        public readonly ?string $targetId = null,
        public readonly ?string $language = null,
        public readonly ?string $enableInheritance = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            type:              $request->query->getString('type') ?: null,
            sourceId:          $request->query->getInt('sourceId'),
            targetId:          $request->query->getString('targetId') ?: null,
            language:          $request->query->getString('language') ?: null,
            enableInheritance: $request->query->getString('enableInheritance') ?: null,
        );
    }
}

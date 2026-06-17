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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\GetLanguageTree;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetLanguageTreePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $node = 0,
        public readonly array $languages = [],
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            node: $request->query->getInt('node'),
            languages: explode(',', $request->query->getString('languages')),
        );
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FormatUrlSlug;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class FormatUrlSlugPayload implements ExtJsPayloadInterface
{
    /**
     * @param array<string, mixed> $context
     */
    public function __construct(
        public int $objectId,
        public array $context,
        public int $siteId,
        public string $text,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            objectId: $request->request->getInt('objectId'),
            context: json_decode($request->request->getString('context'), true) ?? [],
            siteId: $request->request->getInt('siteId'),
            text: $request->request->getString('text'),
        );
    }
}

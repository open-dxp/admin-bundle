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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page\RenderAreabrickIndexEditmode;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class RenderAreabrickIndexEditmodePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $documentId,
        public readonly array $blockStateStack,
        public readonly string $realName,
        public readonly array $areaBlockConfig,
        public readonly array $areaBrickData,
        public readonly int $index,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            documentId: (int) $request->request->get('documentId'),
            blockStateStack: json_decode($request->request->getString('blockStateStack'), true),
            realName: $request->request->getString('realName'),
            areaBlockConfig: json_decode($request->request->getString('areablockConfig'), true),
            areaBrickData: json_decode($request->request->getString('areablockData'), true),
            index: (int) $request->request->get('index'),
        );
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\TreeGetDocumentChildren;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class TreeGetDocumentChildrenPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $node,
        public readonly int $inSearch,
        public readonly array $allParams,
    ) {
    }

    public function hasPagination(): bool
    {
        return !empty($this->allParams['limit']);
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            node:      (int) $request->query->getString('node'),
            inSearch:  (int) $request->query->getString('inSearch'),
            allParams: $request->query->all(),
        );
    }
}

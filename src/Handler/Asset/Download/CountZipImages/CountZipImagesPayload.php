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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Download\CountZipImages;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class CountZipImagesPayload implements ExtJsPayloadInterface
{
    /**
     * @param list<int> $selectedIds
     */
    public function __construct(
        public int $id = 0,
        public array $selectedIds = [],
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            id:          $request->query->getInt('id'),
            selectedIds: array_values(array_filter(array_map(intval(...), explode(',', $request->query->getString('selectedIds'))))),
        );
    }
}

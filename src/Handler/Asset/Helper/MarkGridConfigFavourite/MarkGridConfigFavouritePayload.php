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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\MarkGridConfigFavourite;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class MarkGridConfigFavouritePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $classId = null,
        public readonly int $gridConfigId = 0,
        public readonly ?string $searchType = null,
        public readonly ?string $type = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            classId:      $request->request->getString('classId') ?: null,
            gridConfigId: $request->request->getInt('gridConfigId'),
            searchType:   $request->request->getString('searchType') ?: null,
            type:         $request->request->getString('type') ?: null,
        );
    }
}

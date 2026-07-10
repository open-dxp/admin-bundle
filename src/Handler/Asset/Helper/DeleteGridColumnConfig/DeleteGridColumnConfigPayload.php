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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DeleteGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DeleteGridColumnConfigPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $gridConfigId = 0,
        public readonly bool $noSystemColumns = false,
        public readonly string $id = '',
        public readonly string $types = '',
        public readonly string $searchType = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            gridConfigId:    $request->request->getInt('gridConfigId'),
            noSystemColumns: (bool) $request->query->get('no_system_columns'),
            id:              $request->request->getString('id'),
            types:           $request->request->getString('types'),
            searchType:      $request->request->getString('searchType'),
        );
    }
}

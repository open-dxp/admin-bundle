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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\AddCollections;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddCollectionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $ids = [],
        public int $oid = 0,
        public string $fieldname = '',
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            ids: json_decode($request->request->getString('collectionIds'), true) ?: [],
            oid: $request->request->getInt('oid'),
            fieldname: $request->request->getString('fieldname'),
        );
    }
}

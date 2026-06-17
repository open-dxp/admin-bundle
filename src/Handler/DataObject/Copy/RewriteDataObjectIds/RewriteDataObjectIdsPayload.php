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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Copy\RewriteDataObjectIds;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\Request;

final readonly class RewriteDataObjectIdsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int    $objectId,
        public readonly array  $idMapping,
        public readonly string $transactionId,
        public readonly array  $updatedIdStore,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $transactionId = $request->request->getString('transactionId');
        $idStore = Session::getSessionBag($request->getSession(), 'opendxp_copy')->get($transactionId) ?? [];

        if (!array_key_exists('rewrite-stack', $idStore)) {
            $idStore['rewrite-stack'] = array_values($idStore['idMapping'] ?? []);
        }

        $objectId = (int) array_shift($idStore['rewrite-stack']);

        return new static(
            objectId:       $objectId,
            idMapping:      $idStore['idMapping'] ?? [],
            transactionId:  $transactionId,
            updatedIdStore: $idStore,
        );
    }
}

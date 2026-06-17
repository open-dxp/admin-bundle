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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\RewriteDocumentIds;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\Request;

final readonly class RewriteDocumentIdsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int    $documentId,
        public readonly array  $idMapping,
        public readonly bool   $enableInheritance,
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

        $documentId = (int) array_shift($idStore['rewrite-stack']);

        return new static(
            documentId:       $documentId,
            idMapping:        $idStore['idMapping'] ?? [],
            enableInheritance: $request->request->getString('enableInheritance') === 'true',
            transactionId:    $transactionId,
            updatedIdStore:   $idStore,
        );
    }
}

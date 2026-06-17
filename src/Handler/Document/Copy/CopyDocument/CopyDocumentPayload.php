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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Copy\CopyDocument;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\Request;

final readonly class CopyDocumentPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int     $sourceId,
        public readonly int     $targetId,
        public readonly string  $type,
        public readonly ?int    $sourceParentId,
        public readonly ?int    $targetParentId,
        public readonly ?int    $sessionParentId,
        public readonly bool    $enableInheritance,
        public readonly bool    $resetIndex,
        public readonly ?string $language,
        public readonly string  $transactionId,
        public readonly bool    $saveParentId,
        public readonly array   $sessionBag,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $transactionId = $request->request->getString('transactionId');
        $sessionBag = Session::getSessionBag($request->getSession(), 'opendxp_copy')->get($transactionId) ?? [];
        $hasTargetParentId = (bool) $request->request->getString('targetParentId');

        return new static(
            sourceId:          $request->request->getInt('sourceId'),
            targetId:          $request->request->getInt('targetId'),
            type:              $request->request->getString('type'),
            sourceParentId:    $hasTargetParentId ? $request->request->getInt('sourceParentId') : null,
            targetParentId:    $hasTargetParentId ? $request->request->getInt('targetParentId') : null,
            sessionParentId:   !empty($sessionBag['parentId']) ? (int) $sessionBag['parentId'] : null,
            enableInheritance: $request->request->getString('enableInheritance') === 'true',
            resetIndex:        $request->request->getString('resetIndex') === 'true',
            language:          $request->request->getString('language') ?: null,
            transactionId:     $transactionId,
            saveParentId:      (bool) $request->request->getString('saveParentId'),
            sessionBag:        $sessionBag,
        );
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Session\Gateway;

use OpenDxp\Bundle\AdminBundle\Session\SessionGatewayInterface;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Backs the multi-request asset/document/data-object copy-paste transaction.
 */
final class CopySessionGateway implements SessionGatewayInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function startTransaction(string $transactionId): void
    {
        $this->bag()->set($transactionId, ['idMapping' => []]);
    }

    public function getParentId(string $transactionId): ?int
    {
        $entry = $this->getEntry($transactionId);

        return isset($entry['parentId']) ? (int) $entry['parentId'] : null;
    }

    public function rememberParentId(string $transactionId, int $parentId): void
    {
        $entry = $this->getEntry($transactionId);
        $entry['parentId'] = $parentId;
        $this->bag()->set($transactionId, $entry);
    }

    public function getIdMapping(string $transactionId): array
    {
        return $this->getEntry($transactionId)['idMapping'] ?? [];
    }

    public function rememberCopiedId(string $transactionId, int $sourceId, int $newId, bool $alsoAsParent): void
    {
        $entry = $this->getEntry($transactionId);
        $entry['idMapping'][$sourceId] = $newId;

        if ($alsoAsParent) {
            $entry['parentId'] = $newId;
        }

        $this->bag()->set($transactionId, $entry);
    }

    /**
     * Pops and persists the next id off the transaction's rewrite stack, lazily seeding the
     * stack from the id mapping on first call.
     */
    public function popRewriteStackId(string $transactionId): int
    {
        $entry = $this->getEntry($transactionId);

        if (!array_key_exists('rewrite-stack', $entry)) {
            $entry['rewrite-stack'] = array_values($entry['idMapping'] ?? []);
        }

        $nextId = (int) array_shift($entry['rewrite-stack']);
        $this->bag()->set($transactionId, $entry);

        return $nextId;
    }

    private function getEntry(string $transactionId): array
    {
        return $this->bag()->get($transactionId, []);
    }

    private function bag(): AttributeBagInterface
    {
        return Tool\Session::getSessionBag($this->requestStack->getSession(), self::BAG_COPY);
    }
}

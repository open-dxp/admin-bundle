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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\Element;

final class FindUsagesHandler
{
    public function __invoke(FindUsagesPayload $payload): FindUsagesResult
    {
        $element = null;
        if ($payload->id) {
            $element = Element\Service::getElementById($payload->type, $payload->id);
        } elseif ($payload->path) {
            $element = Element\Service::getElementByPath($payload->type, $payload->path);
        }

        if (!$element instanceof Element\ElementInterface) {
            throw new AdminOperationFailedException('Element not found');
        }

        $total = $element->getDependencies()->getRequiredByTotalCount();
        $results = [];
        $hasHidden = false;

        if ($payload->sort !== null) {
            $sort = json_decode($payload->sort)[0];
            $orderBy = $sort->property;
            $orderDirection = $sort->direction;
        } else {
            $orderBy = null;
            $orderDirection = null;
        }

        $queryOffset = $payload->start;
        $queryLimit = $payload->limit;

        while (count($results) < min($payload->limit, $total) && $queryOffset < $total) {
            $elements = $element->getDependencies()
                ->getRequiredByWithPath($queryOffset, $queryLimit, $orderBy, $orderDirection);

            foreach ($elements as $el) {
                $item = Element\Service::getElementById($el['type'], (int) $el['id']);

                if ($item instanceof Element\ElementInterface) {
                    if ($item->isAllowed('list')) {
                        $results[] = $el;
                    } else {
                        $hasHidden = true;
                    }
                }
            }

            $queryOffset += count($elements);
            $queryLimit = $payload->limit - count($results);
        }

        return new FindUsagesResult(
            data: $results,
            total: $total,
            hasHidden: $hasHidden,
        );
    }
}

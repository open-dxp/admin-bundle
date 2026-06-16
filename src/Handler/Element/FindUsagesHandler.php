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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Model\Element;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class FindUsagesHandler
{
    public function __invoke(
        ?int $id,
        ?string $type,
        ?string $path,
        int $limit,
        int $offset,
        ?string $sort,
    ): FindUsagesResult {
        $element = null;
        if ($id) {
            $element = Element\Service::getElementById($type, $id);
        } elseif ($path) {
            $element = Element\Service::getElementByPath($type, $path);
        }

        if (!$element instanceof Element\ElementInterface) {
            throw new NotFoundHttpException('Element not found');
        }

        $total = $element->getDependencies()->getRequiredByTotalCount();
        $results = [];
        $hasHidden = false;

        if ($sort !== null) {
            $sort = json_decode($sort)[0];
            $orderBy = $sort->property;
            $orderDirection = $sort->direction;
        } else {
            $orderBy = null;
            $orderDirection = null;
        }

        $queryOffset = $offset;
        $queryLimit = $limit;

        while (count($results) < min($limit, $total) && $queryOffset < $total) {
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
            $queryLimit = $limit - count($results);
        }

        return new FindUsagesResult(
            data: $results,
            total: $total,
            hasHidden: $hasHidden,
        );
    }
}

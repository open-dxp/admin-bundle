<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\FindUsages;

use OpenDxp\Model\Element;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

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
            throw new NotFoundHttpException('Element not found');
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

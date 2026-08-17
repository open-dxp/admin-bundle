<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetRequiredByDependencies;

use OpenDxp\Model;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependenciesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependencies\GetDependenciesResult;

final class GetRequiredByDependenciesHandler
{
    public function __invoke(GetDependenciesPayload $payload): GetDependenciesResult
    {
        $allowedTypes = ['asset', 'document', 'object'];

        if (!$payload->id || !in_array($payload->type, $allowedTypes)) {
            return new GetDependenciesResult(data: false);
        }

        $element = Model\Element\Service::getElementById($payload->type, $payload->id);
        $dependencies = $element->getDependencies();

        if ($payload->filter) {
            $filters = json_decode($payload->filter, true) ?? [];
            $value = null;
            $elements = null;

            foreach ($filters as $filter) {
                if ($filter['type'] === 'string') {
                    $value = ($filter['value'] ?? '');
                }
                $elements = $element->getDependencies()->getFilterRequiredByPath($payload->start, $payload->limit, $value);
            }

            if ($elements !== null && count($elements) > 0) {
                $result = Model\Element\Service::getFilterRequiredByPathForFrontend($elements);
                $result['total'] = count($result['requiredBy']);

                return new GetDependenciesResult(data: $result);
            }

            return new GetDependenciesResult(data: $elements ?? []);
        }

        if ($element instanceof Model\Element\ElementInterface) {
            $dependenciesResult = Model\Element\Service::getRequiredByDependenciesForFrontend($dependencies, $payload->start, $payload->limit);

            $dependenciesResult['start'] = $payload->start;
            $dependenciesResult['limit'] = $payload->limit;
            $dependenciesResult['total'] = $dependencies->getRequiredByTotalCount();

            return new GetDependenciesResult(data: $dependenciesResult);
        }

        return new GetDependenciesResult(data: false);
    }
}

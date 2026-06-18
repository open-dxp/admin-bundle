<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetRequiresDependencies;

use OpenDxp\Model;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependenciesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetDependencies\GetDependenciesResult;

final class GetRequiresDependenciesHandler
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
                $elements = $element->getDependencies()->getFilterRequiresByPath($payload->start, $payload->limit, $value);
            }

            if ($elements !== null && count($elements) > 0) {
                $result = Model\Element\Service::getFilterRequiresForFrontend($elements);
                $result['total'] = count($result['requires']);

                return new GetDependenciesResult(data: $result);
            }

            return new GetDependenciesResult(data: $elements ?? []);
        }

        if ($element instanceof Model\Element\ElementInterface) {
            $dependenciesResult = Model\Element\Service::getRequiresDependenciesForFrontend($dependencies, $payload->start, $payload->limit);

            $dependenciesResult['start'] = $payload->start;
            $dependenciesResult['limit'] = $payload->limit;
            $dependenciesResult['total'] = $dependencies->getRequiresTotalCount();

            return new GetDependenciesResult(data: $dependenciesResult);
        }

        return new GetDependenciesResult(data: false);
    }
}

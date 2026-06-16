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

use OpenDxp\Model;

final class GetRequiredByDependenciesHandler
{
    public function __invoke(
        int $id,
        ?string $type,
        int $offset,
        int $limit,
        ?string $filterJson,
    ): GetRequiresDependenciesResult {
        $allowedTypes = ['asset', 'document', 'object'];

        if (!$id || !in_array($type, $allowedTypes)) {
            return new GetRequiresDependenciesResult(data: false);
        }

        $element = Model\Element\Service::getElementById($type, $id);
        $dependencies = $element->getDependencies();

        if ($filterJson) {
            $filters = json_decode($filterJson, true) ?? [];
            $value = null;
            $elements = null;

            foreach ($filters as $filter) {
                if ($filter['type'] === 'string') {
                    $value = ($filter['value'] ?? '');
                }
                $elements = $element->getDependencies()->getFilterRequiredByPath($offset, $limit, $value);
            }

            if ($elements !== null && count($elements) > 0) {
                $result = Model\Element\Service::getFilterRequiredByPathForFrontend($elements);
                $result['total'] = count($result['requiredBy']);

                return new GetRequiresDependenciesResult(data: $result);
            }

            return new GetRequiresDependenciesResult(data: $elements ?? []);
        }

        if ($element instanceof Model\Element\ElementInterface) {
            $dependenciesResult = Model\Element\Service::getRequiredByDependenciesForFrontend($dependencies, $offset, $limit);

            $dependenciesResult['start'] = $offset;
            $dependenciesResult['limit'] = $limit;
            $dependenciesResult['total'] = $dependencies->getRequiredByTotalCount();

            return new GetRequiresDependenciesResult(data: $dependenciesResult);
        }

        return new GetRequiresDependenciesResult(data: false);
    }
}

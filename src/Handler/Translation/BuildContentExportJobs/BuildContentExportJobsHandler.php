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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\BuildContentExportJobs;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Element;

final class BuildContentExportJobsHandler
{
    public function __invoke(BuildContentExportJobsPayload $payload): BuildContentExportJobsResult
    {
        $elements = [];
        $jobs = [];
        $exportId = uniqid('', false);

        if ($payload->data !== []) {
            foreach ($payload->data as $element) {
                $elements[$element['type'] . '_' . $element['id']] = [
                    'id' => $element['id'],
                    'type' => $element['type'],
                ];

                $el = null;

                if ($element['children']) {
                    $el = Element\Service::getElementById($element['type'], (int) $element['id']);
                    $baseClass = Element\Service::getBaseClassNameForElement($element['type']);
                    $listClass = '\\OpenDxp\\Model\\' . $baseClass . '\\Listing';
                    $list = new $listClass();
                    $list->setUnpublished(true);
                    if ($el instanceof AbstractObject) {
                        $list->setObjectTypes(
                            [DataObject::OBJECT_TYPE_VARIANT,
                                DataObject::OBJECT_TYPE_OBJECT,
                                DataObject::OBJECT_TYPE_FOLDER, ]
                        );
                    }
                    $list->setCondition(
                        'path LIKE ?',
                        [$list->escapeLike($el->getRealFullPath() . ($el->getRealFullPath() !== '/' ? '/' : '')) . '%']
                    );
                    $children = $list->load();

                    foreach ($children as $child) {
                        $childId = $child->getId();
                        $elements[$element['type'] . '_' . $childId] = [
                            'id' => $childId,
                            'type' => $element['type'],
                        ];

                        if (isset($element['relations']) && $element['relations']) {
                            $childDependencies = $child->getDependencies()->getRequires();
                            foreach ($childDependencies as $cd) {
                                if ($cd['type'] === 'object' || $cd['type'] === 'document') {
                                    $elements[$cd['type'] . '_' . $cd['id']] = $cd;
                                }
                            }
                        }
                    }
                }

                if (isset($element['relations']) && $element['relations']) {
                    if (!$el instanceof Element\ElementInterface) {
                        $el = Element\Service::getElementById($element['type'], (int) $element['id']);
                    }

                    $dependencies = $el->getDependencies()->getRequires();
                    foreach ($dependencies as $dependency) {
                        if ($dependency['type'] === 'object' || $dependency['type'] === 'document') {
                            $elements[$dependency['type'] . '_' . $dependency['id']] = $dependency;
                        }
                    }
                }
            }
        }

        $elements = array_values($elements);

        $elements = array_chunk($elements, $payload->elementsPerJob);
        foreach ($elements as $chunk) {
            $jobs[] = [[
                'url' => $payload->jobUrl,
                'method' => 'POST',
                'params' => [
                    'id' => $exportId,
                    'source' => $payload->source,
                    'target' => $payload->target,
                    'data' => json_encode($chunk, JSON_THROW_ON_ERROR),
                ],
            ]];
        }

        return new BuildContentExportJobsResult(jobs: $jobs, id: $exportId);
    }
}

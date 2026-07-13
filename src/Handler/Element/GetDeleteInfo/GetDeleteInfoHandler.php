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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo;

use Exception;
use OpenDxp\Bundle\AdminBundle\Event\AssetEvents;
use OpenDxp\Bundle\AdminBundle\Event\Model\AssetDeleteInfoEvent;
use OpenDxp\Bundle\AdminBundle\Event\Model\DataObjectDeleteInfoEvent;
use OpenDxp\Bundle\AdminBundle\Event\Model\DocumentDeleteInfoEvent;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetDeleteInfoHandler
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(GetDeleteInfoPayload $payload): GetDeleteInfoResult
    {
        $hasDependency = false;
        $errors = false;
        $deleteJobs = [];
        $itemResults = [];
        $totalChildren = 0;
        $ids = $payload->id ?? '';
        $type = $payload->type ?? '';
        $baseUrl = $payload->baseUrl;

        $idList = explode(',', $ids);

        foreach ($idList as $id) {
            try {
                $element = Service::getElementById($type, (int) $id);
                if (!$element) {
                    continue;
                }

                if (!$hasDependency) {
                    $hasDependency = $element->getDependencies()->isRequired();
                }
            } catch (Exception) {
                Logger::err('failed to access element with id: ' . $id);

                continue;
            }

            $event = null;
            $eventName = null;

            if ($element instanceof Asset) {
                $event = new AssetDeleteInfoEvent($element);
                $eventName = AssetEvents::DELETE_INFO;
            } elseif ($element instanceof Document) {
                $event = new DocumentDeleteInfoEvent($element);
                $eventName = DocumentEvents::DELETE_INFO;
            } elseif ($element instanceof AbstractObject) {
                $event = new DataObjectDeleteInfoEvent($element);
                $eventName = DataObjectEvents::DELETE_INFO;
            }

            if ($element->isLocked()) {
                $itemResults[] = [
                    'id' => $element->getId(),
                    'type' => $element->getType(),
                    'key' => $element->getKey(),
                    'reason' => 'Element is locked',
                    'allowed' => false,
                ];
                $errors |= true;

                continue;
            }

            $this->eventDispatcher->dispatch($event, $eventName);

            if (!$event->getDeletionAllowed()) {
                $itemResults[] = [
                    'id' => $element->getId(),
                    'type' => $element->getType(),
                    'key' => $element->getKey(),
                    'reason' => $event->getReason(),
                    'allowed' => false,
                ];
                $errors |= true;

                continue;
            }

            $itemResults[] = [
                'id' => $element->getId(),
                'type' => $element->getType(),
                'key' => $element->getKey(),
                'path' => $element->getPath(),
                'allowed' => true,
            ];

            $deleteJobs[] = [[
                'url' => $this->urlGenerator->generate('opendxp_admin_recyclebin_add'),
                'method' => 'POST',
                'params' => [
                    'type' => $type,
                    'id' => $element->getId(),
                ],
            ]];

            $hasChildren = $element->hasChildren();
            if (!$hasDependency) {
                $hasDependency = $hasChildren;
            }

            if ($hasChildren) {
                $list = $element::getList(['unpublished' => true]);
                $pathColumn = 'path';
                $list->setCondition($pathColumn . ' LIKE ?', [$element->getRealFullPath() . '/%']);
                $children = $list->getTotalCount();
                $totalChildren += $children;

                if ($children > 0) {
                    $deleteObjectsPerRequest = 5;
                    for ($i = 0, $iMax = ceil($children / $deleteObjectsPerRequest); $i < $iMax; $i++) {
                        $deleteJobs[] = [[
                            'url' => $baseUrl . '/admin/' . $type . '/delete',
                            'method' => 'DELETE',
                            'params' => [
                                'step' => $i,
                                'amount' => $deleteObjectsPerRequest,
                                'type' => 'children',
                                'id' => $element->getId(),
                            ],
                        ]];
                    }
                }
            }

            $deleteJobs[] = [[
                'url' => $baseUrl . '/admin/' . $type . '/delete',
                'method' => 'DELETE',
                'params' => [
                    'id' => $element->getId(),
                ],
            ]];
        }

        $elementKey = false;
        if (count($idList) === 1) {
            $element = Service::getElementById($type, (int) $idList[0]);

            if ($element instanceof ElementInterface) {
                $elementKey = $element->getKey();
            }
        }

        return new GetDeleteInfoResult(
            hasDependencies: $hasDependency,
            children: $totalChildren,
            deletejobs: $deleteJobs,
            batchDelete: count($idList) > 1,
            elementKey: $elementKey,
            errors: $errors,
            itemResults: $itemResults,
        );
    }
}

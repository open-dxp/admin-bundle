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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use Exception;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Event\AssetEvents;
use OpenDxp\Bundle\AdminBundle\Event\Model\AssetDeleteInfoEvent;
use OpenDxp\Bundle\AdminBundle\Event\Model\DataObjectDeleteInfoEvent;
use OpenDxp\Bundle\AdminBundle\Event\Model\DocumentDeleteInfoEvent;
use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Event\DataObjectEvents;
use OpenDxp\Event\DocumentEvents;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * @internal
 */
abstract class ElementControllerBase extends AdminAbstractController
{
    public function __construct(
        protected ElementServiceInterface $elementService
    ) {
    }

    /**
     * @return array<string, mixed>
     */
    protected function getTreeNodeConfig(ElementInterface $element): array
    {
        return [];
    }

    #[Route('/tree-get-root', name: 'treegetroot', methods: ['GET'])]
    public function treeGetRootAction(Request $request): JsonResponse
    {
        $type = $request->get('elementType');
        $allowedTypes = ['asset', 'document', 'object'];

        $id = 1;
        if ($request->get('id')) {
            $id = (int)$request->get('id');
        }

        if (in_array($type, $allowedTypes)) {
            $root = Service::getElementById($type, $id);
            if ($root?->isAllowed('list')) {
                return $this->adminJson($this->getTreeNodeConfig($root));
            }

            return $this->adminJson(['success' => false, 'id' =>  $id]);
        }

        return $this->adminJson(['success' => false, 'message' => 'missing_permission']);
    }

    /**
     * @throws Exception
     */
    #[Route('/delete-info', name: 'deleteinfo', methods: ['GET'])]
    public function deleteInfoAction(Request $request, EventDispatcherInterface $eventDispatcher): JsonResponse
    {
        $hasDependency = false;
        $errors = false;
        $deleteJobs = [];
        $itemResults = [];

        $totalChildren = 0;

        $ids = $request->get('id');
        $ids = explode(',', $ids);
        $type = $request->get('type');

        foreach ($ids as $id) {
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

            // check for children
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

            $eventDispatcher->dispatch($event, $eventName);

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
                'url' => $this->generateUrl('opendxp_admin_recyclebin_add'),
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
                // get amount of children
                $list = $element::getList(['unpublished' => true]);
                $pathColumn = 'path';
                $list->setCondition($pathColumn . ' LIKE ?', [$element->getRealFullPath() . '/%']);
                $children = $list->getTotalCount();
                $totalChildren += $children;

                if ($children > 0) {
                    $deleteObjectsPerRequest = 5;
                    for ($i = 0, $iMax = ceil($children / $deleteObjectsPerRequest); $i < $iMax; $i++) {
                        $deleteJobs[] = [[
                            'url' => $request->getBaseUrl() . '/admin/' . $type . '/delete',
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

            // the element itself is the last one
            $deleteJobs[] = [[
                'url' => $request->getBaseUrl() . '/admin/' . $type . '/delete',
                'method' => 'DELETE',
                'params' => [
                    'id' => $element->getId(),
                ],
            ]];
        }

        // get the element key in case of just one
        $elementKey = false;
        if (count($ids) === 1) {
            $element = Service::getElementById($type, (int) $ids[0]);

            if ($element instanceof ElementInterface) {
                $elementKey = $element->getKey();
            }
        }

        return $this->adminJson([
            'hasDependencies' => $hasDependency,
            'children' => $totalChildren,
            'deletejobs' => $deleteJobs,
            'batchDelete' => count($ids) > 1,
            'elementKey' => $elementKey,
            'errors' => $errors,
            'itemResults' => $itemResults,
        ]);
    }
}

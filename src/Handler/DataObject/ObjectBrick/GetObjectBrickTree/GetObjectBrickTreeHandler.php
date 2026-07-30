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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickTree;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\GetObjectBrickTree\GetObjectBrickTreePayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetObjectBrickTreeHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {}

    public function __invoke(GetObjectBrickTreePayload $payload): ObjectBrickTreeResult|ObjectBrickTreeEditorResult
    {
        $forObjectEditor = $payload->forObjectEditor;
        $objectId = $payload->objectId;
        $classId = $payload->classId;
        $fieldName = $payload->fieldName;
        $layoutId = $payload->layoutId;
        $adminUser = $this->userContext->getAdminUser();
        $list = (new DataObject\Objectbrick\Definition\Listing())->load();

        $layoutDefinitions = [];
        $groups = [];
        $definitions = [];
        $object = $objectId > 0 ? DataObject\Concrete::getById($objectId) : null;

        $className = null;
        $fieldname = null;

        if ($classId !== null && $fieldName !== null) {
            $fieldname = $fieldName;
            $className = DataObject\ClassDefinition::getById($classId)->getName();
        }

        foreach ($list as $item) {
            $context = [];
            if ($forObjectEditor) {
                $context = [
                    'containerType' => 'objectbrick',
                    'containerKey' => $item->getKey(),
                    'outerFieldname' => $fieldname,
                ];
            }

            if ($className !== null && $fieldname !== null) {
                $keep = false;
                foreach ($item->getClassDefinitions() as $cd) {
                    if ($cd['classname'] == $className && $cd['fieldname'] == $fieldname) {
                        $keep = true;
                        break;
                    }
                }
                if (!$keep) {
                    continue;
                }
            }

            $nodeData = [
                'id' => $item->getKey(),
                'text' => $item->getKey(),
                'title' => $item->getTitle(),
                'key' => $item->getKey(),
                'leaf' => true,
                'iconCls' => 'opendxp_icon_objectbricks',
            ];

            if ($item->getGroup()) {
                if (!isset($groups[$item->getGroup()])) {
                    $groups[$item->getGroup()] = [
                        'id' => 'group_' . $item->getKey(),
                        'text' => htmlspecialchars($item->getGroup()),
                        'expandable' => true,
                        'leaf' => false,
                        'allowChildren' => true,
                        'iconCls' => 'opendxp_icon_folder',
                        'group' => $item->getGroup(),
                        'children' => [],
                    ];
                }
                if ($forObjectEditor) {
                    $itemLayoutDefinitions = null;
                    if ($layoutId) {
                        $layout = DataObject\ClassDefinition\CustomLayout::getById($layoutId . '.brick.' . $item->getKey());
                        if ($layout instanceof DataObject\ClassDefinition\CustomLayout) {
                            $itemLayoutDefinitions = $layout->getLayoutDefinitions();
                        }
                    }
                    if (!$itemLayoutDefinitions instanceof DataObject\ClassDefinition\Layout) {
                        $itemLayoutDefinitions = $item->getLayoutDefinitions();
                    }
                    DataObject\Service::enrichLayoutDefinition($itemLayoutDefinitions, $object, $context);
                    $layoutDefinitions[$item->getKey()] = $itemLayoutDefinitions;
                }
                $groups[$item->getGroup()]['children'][] = $nodeData;
            } else {
                if ($forObjectEditor) {
                    $layout = $item->getLayoutDefinitions();
                    if ($layoutId == -1 && $adminUser->isAdmin()) {
                        DataObject\Service::createSuperLayout($layout);
                    } elseif ($layoutId) {
                        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($layoutId . '.brick.' . $item->getKey());
                        if ($customLayout instanceof DataObject\ClassDefinition\CustomLayout) {
                            $layout = $customLayout->getLayoutDefinitions();
                        }
                    }
                    DataObject\Service::enrichLayoutDefinition($layout, $object, $context);
                    $layoutDefinitions[$item->getKey()] = $layout;
                }
                $definitions[] = $nodeData;
            }
        }

        foreach ($groups as $group) {
            $definitions[] = $group;
        }

        $event = new GenericEvent($this->currentControllerContext->getController(), [
            'list' => $definitions,
            'objectId' => $objectId,
            'forObjectEditor' => $forObjectEditor,
            'layoutDefinitions' => $layoutDefinitions,
            'fieldName' => $fieldName,
            'object' => $object,
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_OBJECTBRICK_LIST_PRE_SEND_DATA);

        $definitions = $event->getArgument('list');
        $layoutDefinitions = $event->getArgument('layoutDefinitions');

        if ($forObjectEditor) {
            return new ObjectBrickTreeEditorResult(
                objectbricks: $definitions,
                layoutDefinitions: $layoutDefinitions,
            );
        }

        return new ObjectBrickTreeResult(definitions: $definitions);
    }
}

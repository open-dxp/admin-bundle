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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionTree;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionTree\GetFieldCollectionTreePayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetFieldCollectionTreeHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(GetFieldCollectionTreePayload $payload): FieldCollectionTreeResult|FieldCollectionTreeEditorResult
    {
        $forObjectEditor = $payload->forObjectEditor;
        $allowedTypes = $payload->allowedTypes;
        $objectId = $payload->objectId;
        $layoutId = $payload->layoutId;
        $adminUser = $this->userContext->getAdminUser();
        $list = (new DataObject\Fieldcollection\Definition\Listing())->load();

        $layoutDefinitions = [];
        $definitions = [];
        $currentLayoutId = $layoutId;
        $object = $objectId > 0 ? DataObject\Concrete::getById($objectId) : null;

        $groups = [];
        foreach ($list as $item) {
            if ($allowedTypes && !in_array($item->getKey(), $allowedTypes)) {
                continue;
            }

            $nodeData = [
                'id' => $item->getKey(),
                'text' => $item->getKey(),
                'title' => $item->getTitle(),
                'key' => $item->getKey(),
                'leaf' => true,
                'iconCls' => 'opendxp_icon_fieldcollection',
            ];

            if ($forObjectEditor) {
                $itemLayoutDefinitions = $item->getLayoutDefinitions();
                DataObject\Service::enrichLayoutDefinition($itemLayoutDefinitions, $object);
                if ($currentLayoutId == -1 && $adminUser->isAdmin()) {
                    DataObject\Service::createSuperLayout($itemLayoutDefinitions);
                }
                $layoutDefinitions[$item->getKey()] = $itemLayoutDefinitions;
            }

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
                $groups[$item->getGroup()]['children'][] = $nodeData;
            } else {
                $definitions[] = $nodeData;
            }
        }

        foreach ($groups as $group) {
            $definitions[] = $group;
        }

        $event = new GenericEvent(null, [
            'list' => $definitions,
            'objectId' => $objectId,
            'layoutDefinitions' => $layoutDefinitions,
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_FIELDCOLLECTION_LIST_PRE_SEND_DATA);

        $definitions = $event->getArgument('list');
        $layoutDefinitions = $event->getArgument('layoutDefinitions');

        if ($forObjectEditor) {
            return new FieldCollectionTreeEditorResult(
                fieldcollections: $definitions,
                layoutDefinitions: $layoutDefinitions,
            );
        }

        return new FieldCollectionTreeResult(definitions: $definitions);
    }
}

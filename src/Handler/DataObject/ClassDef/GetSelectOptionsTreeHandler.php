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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Model\DataObject;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetSelectOptionsTreeHandler
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher) {}

    public function __invoke(GetSelectOptionsTreePayload $payload): GetSelectOptionsTreeResult
    {
        $grouped = $payload->grouped;
        $configurations = $groups = [];

        $selectOptionConfigs = new DataObject\SelectOptions\Config\Listing();
        foreach ($selectOptionConfigs as $selectOptionConfig) {
            $id = $selectOptionConfig->getId();
            $configurationData = [
                'id' => $id,
                'text' => $id,
                'leaf' => true,
                'iconCls' => 'opendxp_icon_select',
            ];

            if ($grouped === 0 || !$selectOptionConfig->hasGroup()) {
                $configurations[] = $configurationData;

                continue;
            }

            $group = $selectOptionConfig->getGroup();
            if (!isset($groups[$group])) {
                $groups[$group] = [
                    'id' => 'group_' . $id,
                    'text' => htmlspecialchars($group ?? ''),
                    'expandable' => true,
                    'leaf' => false,
                    'allowChildren' => true,
                    'iconCls' => 'opendxp_icon_folder',
                    'group' => $group,
                    'children' => [],
                ];
            }
            $groups[$group]['children'][] = $configurationData;
        }

        foreach ($groups as $group) {
            $configurations[] = $group;
        }

        $event = new GenericEvent(null, ['list' => $configurations]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_SELECTOPTIONS_LIST_PRE_SEND_DATA);
        $configurations = $event->getArgument('list');

        return new GetSelectOptionsTreeResult(configurations: $configurations);
    }
}

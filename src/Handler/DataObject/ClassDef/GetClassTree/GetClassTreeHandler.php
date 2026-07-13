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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassTree;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\Translation;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Logger;

final class GetClassTreeHandler
{
    private const DEFAULT_ICON = '/bundles/opendxpadmin/img/flat-color-icons/class.svg';

    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(GetClassTreePayload $payload): GetClassTreeResult
    {
        $adminUser = $this->userContext->getAdminUser();

        if ($adminUser === null || !$adminUser->isAllowed('objects')) {
            Logger::log('[Startup] Object types are not loaded as "objects" permission is missing');

            return new GetClassTreeResult(nodes: []);
        }

        $createAllowed = $payload->createAllowed;
        $withId = $payload->withId;
        $useTitle = $payload->useTitle;
        $grouped = $payload->grouped;
        $classesList = new DataObject\ClassDefinition\Listing();
        $classesList->setOrderKey('name');
        $classesList->setOrder('asc');
        $classes = $classesList->load();

        if ($createAllowed) {
            $classes = array_filter($classes, fn ($class) => $adminUser->isAllowed($class->getId(), 'class'));
            $classes = array_values($classes);
        }

        $getClassConfig = static function ($class) use ($withId, $useTitle): array {
            $text = $class->getName();
            if ($useTitle) {
                $text = $class->getTitle() ?: $class->getName();
            }
            if ($withId) {
                $text .= ' (' . $class->getId() . ')';
            }

            $hasBrickField = false;
            foreach ($class->getFieldDefinitions() as $fieldDefinition) {
                if ($fieldDefinition instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                    $hasBrickField = true;
                    break;
                }
            }

            return [
                'id' => $class->getId(),
                'text' => $text,
                'leaf' => true,
                'icon' => $class->getIcon() ? htmlspecialchars($class->getIcon()) : self::DEFAULT_ICON,
                'cls' => 'opendxp_class_icon',
                'propertyVisibility' => $class->getPropertyVisibility(),
                'enableGridLocking' => $class->isEnableGridLocking(),
                'hasBrickField' => $hasBrickField,
            ];
        };

        $groups = [];
        foreach ($classes as $class) {
            $groupName = null;

            if ($class->getGroup()) {
                $type = 'manual';
                $groupName = $class->getGroup();
            } else {
                $type = 'auto';
                if (preg_match('@^([A-Za-z])([^A-Z]+)@', $class->getName(), $matches)) {
                    $groupName = $matches[0];
                }
                if (!$groupName) {
                    $groupName = $class->getName();
                }
            }

            $groupName = Translation::getByKeyLocalized($groupName, Translation::DOMAIN_ADMIN, true, true);

            if (!isset($groups[$groupName])) {
                $groups[$groupName] = [
                    'classes' => [],
                    'type' => $type,
                ];
            }
            $groups[$groupName]['classes'][] = $class;
        }

        $treeNodes = [];
        if ($groups !== []) {
            $types = array_column($groups, 'type');
            array_multisort($types, SORT_ASC, array_keys($groups), SORT_ASC, $groups);
        }

        if (!$grouped) {
            foreach ($groups as $groupName => $groupData) {
                foreach ($groupData['classes'] as $class) {
                    $node = $getClassConfig($class);
                    if (count($groupData['classes']) > 1 || $groupData['type'] === 'manual') {
                        $node['group'] = $groupName;
                    }
                    $treeNodes[] = $node;
                }
            }
        } else {
            foreach ($groups as $groupName => $groupData) {
                if (count($groupData['classes']) === 1 && $groupData['type'] === 'auto') {
                    $node = $getClassConfig($groupData['classes'][0]);
                } else {
                    $node = [
                        'id' => 'folder_' . $groupName,
                        'text' => $groupName,
                        'leaf' => false,
                        'expandable' => true,
                        'allowChildren' => true,
                        'iconCls' => 'opendxp_icon_folder',
                        'children' => [],
                    ];

                    foreach ($groupData['classes'] as $class) {
                        $node['children'][] = $getClassConfig($class);
                    }
                }

                $treeNodes[] = $node;
            }
        }

        return new GetClassTreeResult(nodes: $treeNodes);
    }
}

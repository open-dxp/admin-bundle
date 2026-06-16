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

use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

final class BulkImportHandler
{
    public function __construct(private readonly RequestStack $requestStack) {}

    public function __invoke(string $json): BulkImportResult
    {
        $tmpName = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/bulk-import-' . uniqid('', false) . '.tmp';
        file_put_contents($tmpName, $json);

        Session::useBag(
            $this->requestStack->getCurrentRequest()->getSession(),
            static function (AttributeBagInterface $session) use ($tmpName): void {
                $session->set('class_bulk_import_file', $tmpName);
            },
            'opendxp_objects'
        );

        $parsed = json_decode($json, true);
        $result = [];

        foreach ($parsed as $groupName => $group) {
            foreach ($group as $groupItem) {
                $displayName = null;
                $icon = null;

                if ($groupName === 'class') {
                    $name = $groupItem['name'];
                    $icon = 'class';
                } elseif ($groupName === 'customlayout') {
                    $className = $groupItem['className'];
                    $layoutData = ['className' => $className, 'name' => $groupItem['name']];
                    $name = base64_encode(json_encode($layoutData));
                    $displayName = $className . ' / ' . $groupItem['name'];
                    $icon = 'custom_views';
                } else {
                    if ($groupName === 'objectbrick') {
                        $icon = 'objectbricks';
                    } elseif ($groupName === 'fieldcollection') {
                        $icon = 'fieldcollection';
                    }
                    $name = $groupItem['key'];
                }

                if (!$displayName) {
                    $displayName = $name;
                }

                $result[] = [
                    'icon' => $icon,
                    'checked' => true,
                    'type' => $groupName,
                    'name' => $name,
                    'displayName' => $displayName,
                ];
            }
        }

        return new BulkImportResult(items: $result);
    }
}

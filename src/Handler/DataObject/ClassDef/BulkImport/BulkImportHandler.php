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

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\BulkImport;

use OpenDxp\Bundle\AdminBundle\Session\Gateway\BulkOperationSessionGateway;

final class BulkImportHandler
{
    public function __construct(private readonly BulkOperationSessionGateway $bulkOperationSession)
    {
    }

    public function __invoke(BulkImportPayload $payload): BulkImportResult
    {
        $tmpName = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/bulk-import-' . uniqid('', false) . '.tmp';
        file_put_contents($tmpName, $payload->json);

        $parsed = json_decode($payload->json, true);
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
                    $name = base64_encode(json_encode($layoutData) ?: '');
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

        $this->bulkOperationSession->storeImportFile($tmpName);

        return new BulkImportResult(data: $result);
    }
}

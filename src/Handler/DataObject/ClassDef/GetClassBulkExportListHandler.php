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

use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class GetClassBulkExportListHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(): GetClassBulkExportListResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $result = [];

        if ($adminUser->isAllowed('fieldcollections')) {
            $fieldCollections = new DataObject\Fieldcollection\Definition\Listing();
            $fieldCollections = $fieldCollections->load();

            foreach ($fieldCollections as $fieldCollection) {
                $result[] = [
                    'icon' => 'fieldcollection',
                    'checked' => true,
                    'type' => 'fieldcollection',
                    'name' => $fieldCollection->getKey(),
                    'displayName' => $fieldCollection->getKey(),
                ];
            }
        }

        if ($adminUser->isAllowed('classes')) {
            $classes = new DataObject\ClassDefinition\Listing();
            $classes->setOrder('ASC');
            $classes->setOrderKey('id');
            $classes = $classes->load();

            foreach ($classes as $class) {
                $result[] = [
                    'icon' => 'class',
                    'checked' => true,
                    'type' => 'class',
                    'name' => $class->getName(),
                    'displayName' => $class->getName(),
                ];
            }
        }

        if ($adminUser->isAllowed('objectbricks')) {
            $objectBricks = new DataObject\Objectbrick\Definition\Listing();
            $objectBricks = $objectBricks->loadNames();

            foreach ($objectBricks as $brickName) {
                $result[] = [
                    'icon' => 'objectbricks',
                    'checked' => true,
                    'type' => 'objectbrick',
                    'name' => $brickName,
                    'displayName' => $brickName,
                ];
            }
        }

        if ($adminUser->isAllowed('classes')) {
            $customLayouts = new DataObject\ClassDefinition\CustomLayout\Listing();
            $customLayouts = $customLayouts->load();
            foreach ($customLayouts as $customLayout) {
                $class = DataObject\ClassDefinition::getById($customLayout->getClassId());
                $displayName = $class->getName() . ' / ' . $customLayout->getName();

                $result[] = [
                    'icon' => 'custom_views',
                    'checked' => true,
                    'type' => 'customlayout',
                    'name' => $customLayout->getId(),
                    'displayName' => $displayName,
                ];
            }
        }

        return new GetClassBulkExportListResult(data: $result);
    }
}

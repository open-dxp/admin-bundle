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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout;

use OpenDxp\Bundle\AdminBundle\Payload\Common\StringIdBodyPayload;
use OpenDxp\Model\DataObject;

final class DeleteCustomLayoutHandler
{
    public function __invoke(StringIdBodyPayload $payload): void
    {
        $id = $payload->id;
        $customLayouts = new DataObject\ClassDefinition\CustomLayout\Listing();
        $customLayouts->setFilter(function (DataObject\ClassDefinition\CustomLayout $layout) use ($id) {
            $currentLayoutId = $layout->getId();

            return $currentLayoutId === $id || str_starts_with($currentLayoutId, $id . '.brick.');
        });

        foreach ($customLayouts->getLayoutDefinitions() as $customLayout) {
            $customLayout->delete();
        }
    }
}

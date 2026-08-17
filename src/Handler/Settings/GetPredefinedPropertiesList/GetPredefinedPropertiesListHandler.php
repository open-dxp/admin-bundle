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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\GetPredefinedPropertiesList;

use OpenDxp\Bundle\AdminBundle\Handler\Settings\PredefinedPropertyPayload;
use OpenDxp\Model\Property;

final class GetPredefinedPropertiesListHandler
{
    public function __invoke(PredefinedPropertyPayload $payload): GetPredefinedPropertiesListResult
    {
        $list = new Property\Predefined\Listing();

        if ($payload->filter) {
            $list->setFilter(function (Property\Predefined $predefined) use ($payload) {
                foreach ($predefined->getObjectVars() as $value) {
                    if ($value) {
                        $cellValues = is_array($value) ? $value : [$value];

                        foreach ($cellValues as $cellValue) {
                            if (stripos((string) $cellValue, $payload->filter) !== false) {
                                return true;
                            }
                        }
                    }
                }

                return false;
            });
        }

        $properties = [];
        foreach ($list->getProperties() as $property) {
            $data = $property->getObjectVars();
            $data['writeable'] = $property->isWriteable();
            $properties[] = $data;
        }

        return new GetPredefinedPropertiesListResult(data: $properties, total: $list->getTotalCount());
    }
}

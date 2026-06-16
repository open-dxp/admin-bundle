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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Model\Metadata;

final class GetPredefinedMetadataListHandler
{
    public function __invoke(?string $filter): GetPredefinedMetadataListResult
    {
        $list = new Metadata\Predefined\Listing();

        if ($filter) {
            $list->setFilter(function (Metadata\Predefined $predefined) use ($filter) {
                foreach ($predefined->getObjectVars() as $value) {
                    if (stripos((string) $value, $filter) !== false) {
                        return true;
                    }
                }

                return false;
            });
        }

        $properties = [];
        foreach ($list->getDefinitions() as $metadata) {
            $metadata->expand();
            $data = $metadata->getObjectVars();
            $data['writeable'] = $metadata->isWriteable();
            $properties[] = $data;
        }

        return new GetPredefinedMetadataListResult(data: $properties, total: $list->getTotalCount());
    }
}

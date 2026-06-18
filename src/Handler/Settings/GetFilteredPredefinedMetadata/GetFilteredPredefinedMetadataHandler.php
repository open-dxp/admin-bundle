<?php

declare(strict_types=1);

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\GetFilteredPredefinedMetadata;

use OpenDxp\Model\Metadata;

final class GetFilteredPredefinedMetadataHandler
{
    public function __invoke(?string $type, ?string $subType, ?string $group): GetFilteredPredefinedMetadataResult
    {
        $list = Metadata\Predefined\Listing::getByTargetType($type, [$subType]);
        $result = [];

        foreach ($list as $item) {
            $itemGroup = $item->getGroup() ?? '';
            if ($group === 'default' || $group === $itemGroup) {
                $item->expand();
                $data = $item->getObjectVars();
                $data['writeable'] = $item->isWriteable();
                $result[] = $data;
            }
        }

        return new GetFilteredPredefinedMetadataResult(data: $result);
    }
}

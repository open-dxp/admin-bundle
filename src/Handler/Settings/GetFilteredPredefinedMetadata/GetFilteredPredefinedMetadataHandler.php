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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\GetFilteredPredefinedMetadata;

use OpenDxp\Model\Metadata;

final class GetFilteredPredefinedMetadataHandler
{
    public function __invoke(GetFilteredPredefinedMetadataPayload $payload): GetFilteredPredefinedMetadataResult
    {
        $list = Metadata\Predefined\Listing::getByTargetType($payload->type, [$payload->subType]);
        $result = [];

        foreach ($list as $item) {
            $itemGroup = $item->getGroup() ?? '';
            if ($payload->group === 'default' || $payload->group === $itemGroup) {
                $item->expand();
                $data = $item->getObjectVars();
                $data['writeable'] = $item->isWriteable();
                $result[] = $data;
            }
        }

        return new GetFilteredPredefinedMetadataResult(data: $result);
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetAssetTypes;

use OpenDxp\Model\Asset;

final class GetAssetTypesHandler
{
    public function __invoke(): GetAssetTypesResult
    {
        $typeItems = [];
        foreach (Asset::getTypes() as $assetType) {
            $typeItems[] = ['text' => $assetType];
        }

        return new GetAssetTypesResult(types: $typeItems);
    }
}

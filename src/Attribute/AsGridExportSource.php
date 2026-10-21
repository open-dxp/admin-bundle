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

namespace OpenDxp\Bundle\AdminBundle\Attribute;

use Attribute;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * @see GridExportSourceInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsGridExportSource extends AutoconfigureTag
{
    public function __construct(string $name, string $permission, int $batchSize = 500)
    {
        parent::__construct(GridExportSourceRegistry::TAG, [
            'name' => $name,
            'permission' => $permission,
            'batch_size' => $batchSize,
        ]);
    }
}

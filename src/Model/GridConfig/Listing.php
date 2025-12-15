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

namespace OpenDxp\Bundle\AdminBundle\Model\GridConfig;

use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Model;

/**
 * @method GridConfig\Listing\Dao getDao()
 * @method GridConfig[] load()
 * @method GridConfig|false current()
 *
 * @internal
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return GridConfig[]
     */
    public function getGridConfigs(): array
    {
        return $this->getData();
    }

    /**
     * @param GridConfig[]|null $gridConfigs
     *
     * @return $this
     */
    public function setGridConfigs(?array $gridConfigs): static
    {
        return $this->setData($gridConfigs);
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Model\GridConfigShare;

use OpenDxp\Bundle\AdminBundle\Model\GridConfigShare;
use OpenDxp\Model;

/**
 * @method GridConfigShare\Listing\Dao getDao()
 * @method GridConfigShare[] load()
 * @method GridConfigShare|false current()
 *
 * @internal
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return GridConfigShare[]
     */
    public function getGridconfigShares(): array
    {
        return $this->getData();
    }

    /**
     * @param GridConfigShare[]|null $gridconfigShares
     *
     * @return $this
     */
    public function setGridconfigShares(?array $gridconfigShares): static
    {
        return $this->setData($gridconfigShares);
    }
}

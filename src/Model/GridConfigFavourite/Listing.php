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

namespace OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;

use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Model;

/**
 * @method GridConfigFavourite\Listing\Dao getDao()
 * @method GridConfigFavourite[] load()
 * @method GridConfigFavourite|false current()
 *
 * @internal
 */
class Listing extends Model\Listing\AbstractListing
{
    /**
     * @return GridConfigFavourite[]
     */
    public function getGridconfigFavourites(): array
    {
        return $this->getData();
    }

    /**
     * @param GridConfigFavourite[]|null $gridconfigFavourites
     *
     * @return $this
     */
    public function setGridconfigFavourites(?array $gridconfigFavourites): static
    {
        return $this->setData($gridconfigFavourites);
    }
}

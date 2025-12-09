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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite\Listing;

use Exception;
use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigFavourite;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property GridConfigFavourite\Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of gridconfigs for the specicified parameters, returns an array of GridConfigFavourite elements
     */
    public function load(): array
    {
        $gridConfigsFavourites = [];
        $data = $this->db->fetchAllAssociative('SELECT * FROM gridconfig_favourites' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());
        $gridConfigs = [];

        foreach ($data as $configData) {
            $gridConfig = new GridConfig();
            $gridConfig->setValues($configData);
            $gridConfigs[] = $gridConfig;
        }

        $this->model->setGridconfigFavourites($gridConfigsFavourites);

        return $gridConfigs;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM gridconfig_favourites ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception) {
            return 0;
        }
    }
}

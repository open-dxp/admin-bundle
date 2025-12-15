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

namespace OpenDxp\Bundle\AdminBundle\Model\GridConfigShare\Listing;

use Exception;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigShare;
use OpenDxp\Bundle\AdminBundle\Model\GridConfigShare\Listing;
use OpenDxp\Model;

/**
 * @internal
 *
 * @property Listing $model
 */
class Dao extends Model\Listing\Dao\AbstractDao
{
    /**
     * Loads a list of gridconfigs for the specicified parameters, returns an array of GridConfigFavourite elements
     */
    public function load(): array
    {
        $gridConfigs = [];
        $data = $this->db->fetchAllAssociative('SELECT * FROM gridconfig_shares' . $this->getCondition() . $this->getOrder() . $this->getOffsetLimit(), $this->model->getConditionVariables());

        foreach ($data as $configData) {
            $gridConfig = new GridConfigShare();
            $gridConfig->setValues($configData);
            $gridConfigs[] = $gridConfig;
        }

        $this->model->setGridconfigShares($gridConfigs);

        return $gridConfigs;
    }

    public function getTotalCount(): int
    {
        try {
            return (int) $this->db->fetchOne('SELECT COUNT(*) FROM gridconfig_shares ' . $this->getCondition(), $this->model->getConditionVariables());
        } catch (Exception) {
            return 0;
        }
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Model\GridConfig;

use OpenDxp\Bundle\AdminBundle\Model\GridConfig;
use OpenDxp\Db\Helper;
use OpenDxp\Model;
use OpenDxp\Model\Exception\NotFoundException;

/**
 * @internal
 *
 * @property GridConfig $model
 */
class Dao extends Model\Dao\AbstractDao
{
    /**
     * @throws NotFoundException
     */
    public function getById(int $id): void
    {
        $data = $this->db->fetchAssociative('SELECT * FROM gridconfigs WHERE id = ?', [$id]);

        if (!$data) {
            throw new NotFoundException('gridconfig with id ' . $id . ' not found');
        }

        $this->assignVariablesToModel($data);
    }

    /**
     * Save object to database
     */
    public function save(): int
    {
        $gridconfigs = $this->model->getObjectVars();
        $data = [];

        foreach ($gridconfigs as $key => $value) {
            if (in_array($key, $this->getValidTableColumns('gridconfigs'))) {
                if (is_bool($value)) {
                    $value = (int) $value;
                }

                $data[$key] = $value;
            }
        }

        Helper::upsert($this->db, 'gridconfigs', $data, $this->getPrimaryKey('gridconfigs'));

        $lastInsertId = $this->db->lastInsertId();
        if (!$this->model->getId() && $lastInsertId) {
            $this->model->setId((int) $lastInsertId);
        }

        return $this->model->getId();
    }

    /**
     * Deletes object from database
     */
    public function delete(): void
    {
        $this->db->delete('gridconfigs', ['id' => $this->model->getId()]);
    }
}

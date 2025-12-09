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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Model;

use Exception;
use OpenDxp\Model\AbstractModel;
use OpenDxp\Model\Exception\NotFoundException;

/**
 * @method GridConfigShare\Dao getDao()
 *
 * @internal
 */
class GridConfigShare extends AbstractModel
{
    protected int $gridConfigId;

    protected int $sharedWithUserId;

    public static function getByGridConfigAndSharedWithId(int $gridConfigId, int $sharedWithUserId): ?GridConfigShare
    {
        try {
            $share = new self();
            $share->getDao()->getByGridConfigAndSharedWithId($gridConfigId, $sharedWithUserId);

            return $share;
        } catch (NotFoundException) {
            return null;
        }
    }

    /**
     * @throws Exception
     */
    public function save(): void
    {
        $this->getDao()->save();
    }

    /**
     * Delete this share
     */
    public function delete(): void
    {
        $this->getDao()->delete();
    }

    public function getGridConfigId(): int
    {
        return $this->gridConfigId;
    }

    public function setGridConfigId(int $gridConfigId): void
    {
        $this->gridConfigId = $gridConfigId;
    }

    public function getSharedWithUserId(): int
    {
        return $this->sharedWithUserId;
    }

    public function setSharedWithUserId(int $sharedWithUserId): void
    {
        $this->sharedWithUserId = $sharedWithUserId;
    }
}

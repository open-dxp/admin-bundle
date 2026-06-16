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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue;

use Exception;
use OpenDxp\Db;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use OpenDxp\Model\Translation;

final class GetQuantityValueUnitListHandler
{
    public function __invoke(?string $filter): GetQuantityValueUnitListResult
    {
        $list = new Unit\Listing();
        $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
        $list->setOrder(['ASC', 'ASC', 'ASC']);

        if ($filter) {
            $array = explode(',', $filter);
            $quotedArray = [];
            $db = Db::get();
            foreach ($array as $a) {
                $quotedArray[] = $db->quote($a);
            }
            $list->setCondition('id IN (' . implode(',', $quotedArray) . ')');
        }

        $result = [];
        foreach ($list->getUnits() as $unit) {
            try {
                if ($unit->getAbbreviation()) {
                    $unit->setAbbreviation(Translation::getByKeyLocalized($unit->getAbbreviation(), Translation::DOMAIN_ADMIN, true, true));
                }
                if ($unit->getLongname()) {
                    $unit->setLongname(Translation::getByKeyLocalized($unit->getLongname(), Translation::DOMAIN_ADMIN, true, true));
                }
                $result[] = $unit->getObjectVars();
            } catch (Exception) {
                // nothing to do
            }
        }

        return new GetQuantityValueUnitListResult($result, $list->getTotalCount());
    }
}

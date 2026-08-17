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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\DeleteQuantityValueUnit;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ManageQuantityValueUnitResult;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\QuantityValueUnitPayload;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteQuantityValueUnitHandler
{
    public function __invoke(QuantityValueUnitPayload $payload): ManageQuantityValueUnitResult
    {
        $unit = Unit::getById($payload->data['id']);
        if (!$unit) {
            throw new NotFoundHttpException('Unit with id ' . $payload->data['id'] . ' not found.');
        }

        $unit->delete();

        return new ManageQuantityValueUnitResult([]);
    }
}

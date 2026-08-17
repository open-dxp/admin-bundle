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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\CreateQuantityValueUnit;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ManageQuantityValueUnitResult;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\QuantityValueUnitPayload;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateQuantityValueUnitHandler
{
    public function __invoke(QuantityValueUnitPayload $payload): ManageQuantityValueUnitResult
    {
        $data = $payload->data;

        if (isset($data['baseunit']) && $data['baseunit'] === -1) {
            $data['baseunit'] = null;
        }

        $id = $data['id'];

        if (Unit::getById($id)) {
            throw new BadRequestHttpException('unit with ID [' . $id . '] already exists');
        }

        if (mb_strlen($id) > 50) {
            throw new BadRequestHttpException('The maximal character length for the unit ID is 50 characters, the provided ID has ' . mb_strlen($id) . ' characters.');
        }

        $unit = new Unit();
        $unit->setValues($data);
        $unit->save();

        return new ManageQuantityValueUnitResult($unit->getObjectVars());
    }
}

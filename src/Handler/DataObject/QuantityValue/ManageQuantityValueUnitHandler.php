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

use OpenDxp\Model\DataObject\QuantityValue\Unit;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ManageQuantityValueUnitHandler
{
    public function __invoke(string $xaction, array $data): ManageQuantityValueUnitResult
    {
        if ($xaction === 'destroy') {
            $unit = Unit::getById($data['id']);
            if (!$unit) {
                throw new NotFoundHttpException('Unit with id ' . $data['id'] . ' not found.');
            }
            $unit->delete();

            return new ManageQuantityValueUnitResult([]);
        }

        if ($xaction === 'update') {
            $unit = Unit::getById($data['id']);
            if (!$unit) {
                throw new NotFoundHttpException('Unit with id ' . $data['id'] . ' not found.');
            }
            if (($data['baseunit'] ?? null) == -1) {
                $data['baseunit'] = null;
            }
            $unit->setValues($data);
            $unit->save();

            return new ManageQuantityValueUnitResult($unit->getObjectVars());
        }

        if ($xaction === 'create') {
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

        throw new BadRequestHttpException('Unknown xaction: ' . $xaction);
    }
}

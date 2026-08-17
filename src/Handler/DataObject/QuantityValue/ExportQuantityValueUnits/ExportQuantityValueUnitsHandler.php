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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ExportQuantityValueUnits;

use OpenDxp\Model\DataObject\QuantityValue\Service as QuantityValueService;

final class ExportQuantityValueUnitsHandler
{
    public function __construct(private readonly QuantityValueService $service) {}

    public function __invoke(): string
    {
        return $this->service->generateDefinitionJson();
    }
}

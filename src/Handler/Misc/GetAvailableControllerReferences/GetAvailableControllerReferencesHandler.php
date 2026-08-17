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

namespace OpenDxp\Bundle\AdminBundle\Handler\Misc\GetAvailableControllerReferences;

use OpenDxp\Controller\Config\ControllerDataProvider;

final class GetAvailableControllerReferencesHandler
{
    public function __construct(private readonly ControllerDataProvider $provider)
    {
    }

    public function __invoke(): GetAvailableControllerReferencesResult
    {
        $controllerReferences = $this->provider->getControllerReferences();

        $data = array_map(static fn ($controller) => [
            'name' => $controller,
        ], $controllerReferences);

        return new GetAvailableControllerReferencesResult(
            data: $data,
            total: count($data),
        );
    }
}

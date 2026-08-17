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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Session\Gateway;

use OpenDxp\Bundle\AdminBundle\Session\SessionGatewayInterface;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Backs the "calculated"/operator grid columns a user builds ad hoc in the asset and data-object grids.
 */
final class GridColumnConfigSessionGateway implements SessionGatewayInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function getHelperColumns(): array
    {
        return $this->bag()->get('helpercolumns', []);
    }

    public function setHelperColumns(array $helperColumns): void
    {
        $this->bag()->set('helpercolumns', $helperColumns);
    }

    public function prependHelperColumns(array $helperColumns): void
    {
        $existing = $this->getHelperColumns();
        $this->setHelperColumns([...$helperColumns, ...$existing]);
    }

    private function bag(): AttributeBagInterface
    {
        return Tool\Session::getSessionBag($this->requestStack->getSession(), self::BAG_GRID_COLUMN_CONFIG);
    }
}

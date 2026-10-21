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

namespace OpenDxp\Bundle\AdminBundle\GridExport;

/**
 * Takes the values of a grid export source from the session of the admin. A job run of the export has no session.
 */
interface SessionGridExportSourceInterface
{
    /**
     * Returns no object. The TmpStore keeps the query and with it each value.
     *
     * @return array<string, mixed>
     */
    public function getSessionParameters(GridExportQuery $query): array;
}

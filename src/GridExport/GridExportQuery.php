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

final readonly class GridExportQuery
{
    /**
     * @param array<string, mixed> $parameters
     * @param list<int|string> $selectedIds
     * @param array<string, mixed> $sessionParameters
     */
    public function __construct(
        public int $userId,
        public string $language,
        public string $timezone,
        public array $parameters = [],
        public array $selectedIds = [],
        public array $sessionParameters = [],
    ) {
    }
}

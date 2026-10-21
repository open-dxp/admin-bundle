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

enum GridExportHeader: string
{
    case LABEL = 'label';
    case KEY = 'key';
    case NONE = 'none';

    /**
     * @param list<GridExportColumn> $columns
     *
     * @return list<string>|null
     */
    public function getTitles(array $columns): ?array
    {
        return match ($this) {
            self::LABEL => array_map(static fn (GridExportColumn $column): string => $column->label, $columns),
            self::KEY => array_map(static fn (GridExportColumn $column): string => $column->key, $columns),
            self::NONE => null,
        };
    }
}

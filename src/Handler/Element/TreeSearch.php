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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Db\Helper;

final class TreeSearch
{
    /**
     * Converts the search of a tree folder into a LIKE pattern.
     *
     * The pattern matches:
     * - a key that contains the search anywhere
     * - any text in place of an asterisk in the search
     */
    public static function toLikePattern(string $search): string
    {
        return '%' . str_replace('*', '%', Helper::escapeLike($search)) . '%';
    }
}

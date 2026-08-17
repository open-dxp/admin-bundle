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

namespace OpenDxp\Bundle\AdminBundle\Model\DataObject;

/**
 * Accumulates field data and metadata during a single getDataById request.
 * Replaces the former request-scoped instance variables on DataObjectController.
 */
final class DataObjectLoadContext
{
    public array $objectData = [];

    public array $metaData = [];
}

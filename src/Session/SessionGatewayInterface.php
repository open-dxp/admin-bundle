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

namespace OpenDxp\Bundle\AdminBundle\Session;

/**
 * Marker for the only classes allowed to read Symfony's session storage on behalf of a Handler or Service.
 */
interface SessionGatewayInterface
{
    public const string BAG_COPY = 'opendxp_copy';

    public const string BAG_BULK_OPERATION = 'opendxp_objects';

    public const string BAG_GRID_COLUMN_CONFIG = 'opendxp_gridconfig';

    public const string BAG_TRANSLATION_IMPORT = 'opendxp_importconfig';

    public const string BAG_ADMIN = 'opendxp_admin';
}

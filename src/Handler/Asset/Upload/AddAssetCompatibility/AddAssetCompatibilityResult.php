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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAssetCompatibility;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class AddAssetCompatibilityResult implements ResultInterface
{
    public function __construct(
        public readonly string $msg,
        public readonly int $id,
        public readonly string $fullpath,
        public readonly string $type,
    ) {
    }
}

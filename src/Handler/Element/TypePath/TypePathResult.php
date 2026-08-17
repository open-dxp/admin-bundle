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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\TypePath;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class TypePathResult implements ResultInterface
{
    public function __construct(
        public readonly int $index,
        public readonly string $idPath,
        public readonly string $typePath,
        public readonly string $fullpath,
        public readonly ?string $sortIndexPath,
    ) {
    }
}

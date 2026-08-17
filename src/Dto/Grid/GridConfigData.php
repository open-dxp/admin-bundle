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

namespace OpenDxp\Bundle\AdminBundle\Dto\Grid;

final class GridConfigData
{
    public function __construct(
        public readonly int $id = 0,
        public readonly array $config = [],
        public readonly ?string $name = null,
        public readonly ?string $description = null,
        public readonly bool $sharedGlobally = false,
        public readonly bool $setAsFavourite = false,
        public readonly bool $isShared = true,
        public readonly int $ownerId = 0,
        public readonly ?int $modificationDate = null,
        public readonly bool $saveFilters = false,
    ) {
    }

    public function isEmpty(): bool
    {
        return $this->id === 0;
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Tests\Value;

final readonly class ZipDownload
{
    /**
     * @param array<string, string> $entries the content of each entry by its path
     */
    public function __construct(
        public string $filename,
        public array $entries,
    ) {
    }

    /**
     * @return list<string>
     */
    public function paths(): array
    {
        return array_map(strval(...), array_keys($this->entries));
    }
}

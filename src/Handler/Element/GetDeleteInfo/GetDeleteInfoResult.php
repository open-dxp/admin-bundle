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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetDeleteInfo;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetDeleteInfoResult implements ResultInterface
{
    public function __construct(
        public bool $hasDependencies,
        public int $children,
        public array $deletejobs,
        public bool $batchDelete,
        public string|false $elementKey,
        public bool $errors,
        public array $itemResults,
    ) {
    }
}

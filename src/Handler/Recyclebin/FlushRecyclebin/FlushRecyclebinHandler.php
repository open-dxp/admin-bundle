<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\FlushRecyclebin;

use OpenDxp\Model\Element\Recyclebin;

final class FlushRecyclebinHandler
{
    public function __invoke(): void
    {
        $bin = new Recyclebin();
        $bin->flush();
    }
}

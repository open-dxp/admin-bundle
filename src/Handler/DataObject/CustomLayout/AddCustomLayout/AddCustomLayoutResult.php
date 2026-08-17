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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\AddCustomLayout;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class AddCustomLayoutResult implements ResultInterface
{
    public function __construct(
        public ?string $id,
        public string $name,
        public array $data,
    ) {
    }
}

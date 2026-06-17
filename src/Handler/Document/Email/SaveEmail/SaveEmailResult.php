<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Email\SaveEmail;

use OpenDxp\Model\Document\Email;
use OpenDxp\Model\Version;

final class SaveEmailResult
{
    public function __construct(
        public readonly Email $email,
        public readonly string $task,
        public readonly ?Version $version,
        public readonly array $treeData,
    ) {}
}

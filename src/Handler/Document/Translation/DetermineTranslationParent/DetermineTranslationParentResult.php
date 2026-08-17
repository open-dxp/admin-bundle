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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Translation\DetermineTranslationParent;

use OpenDxp\Bundle\AdminBundle\Handler\ConditionalResultInterface;

final readonly class DetermineTranslationParentResult implements ConditionalResultInterface
{
    public function __construct(
        public readonly bool $found,
        public readonly ?string $targetPath,
        public readonly ?int $targetId,
    ) {
    }

    public function isSuccessful(): bool
    {
        return $this->found;
    }
}

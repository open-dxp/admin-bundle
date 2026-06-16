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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Model;

final class DeleteAllVersionsHandler
{
    public function __invoke(int $elementId, string $elementModificationdate, string $elementType): void
    {
        $versions = new Model\Version\Listing();
        $versions->setCondition(
            'cid = ' . $versions->quote($elementId) .
            ' AND date <> ' . $versions->quote($elementModificationdate) .
            ' AND ctype = ' . $versions->quote($elementType)
        );
        foreach ($versions->load() as $version) {
            $version->delete();
        }
    }
}

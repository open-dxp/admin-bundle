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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SuggestClassIdentifier;

use OpenDxp\Db;

final class SuggestClassIdentifierHandler
{
    public function __invoke(): SuggestClassIdentifierResult
    {
        $db = Db::get();
        $maxId = $db->fetchOne('SELECT MAX(CAST(id AS SIGNED)) FROM classes');
        $existingIds = $db->fetchFirstColumn('SELECT LOWER(id) FROM classes');

        return new SuggestClassIdentifierResult(
            suggestedIdentifier: $maxId ? $maxId + 1 : 1,
            existingIds: $existingIds,
        );
    }
}

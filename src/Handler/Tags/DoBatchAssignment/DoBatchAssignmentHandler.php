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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags\DoBatchAssignment;

use OpenDxp\Model\Element\Tag;

final class DoBatchAssignmentHandler
{
    public function __invoke(DoBatchAssignmentPayload $payload): void
    {
        Tag::batchAssignTagsToElement($payload->elementType, $payload->childrenIds, $payload->assignedTags, $payload->doCleanupTags);
    }
}

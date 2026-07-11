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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags\RemoveTagFromElement;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\Element\Tag;

final class RemoveTagFromElementHandler
{
    public function __invoke(RemoveTagFromElementPayload $payload): RemoveTagFromElementResult
    {
        $tag = Tag::getById($payload->tagId);
        if (!$tag) {
            throw new AdminOperationFailedException('Tag with ID ' . $payload->tagId . ' not found.');
        }

        Tag::removeTagFromElement($payload->elementType, $payload->elementId, $tag);

        return new RemoveTagFromElementResult(id: $tag->getId() ?? 0);
    }
}

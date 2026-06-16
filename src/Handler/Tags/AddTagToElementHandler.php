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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags;

use OpenDxp\Model\Element\Tag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AddTagToElementHandler
{
    public function __invoke(int $tagId, string $elementType, int $elementId): AddTagToElementResult
    {
        $tag = Tag::getById($tagId);
        if (!$tag) {
            throw new NotFoundHttpException('Tag with ID ' . $tagId . ' not found.');
        }

        Tag::addTagToElement($elementType, $elementId, $tag);

        return new AddTagToElementResult(id: $tag->getId());
    }
}

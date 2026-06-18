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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags\UpdateTag;

use OpenDxp\Model\Element\Tag;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UpdateTagHandler
{
    public function __invoke(UpdateTagPayload $payload): void
    {
        $tag = Tag::getById($payload->id);
        if (!$tag) {
            throw new NotFoundHttpException('Tag with ID ' . $payload->id . ' not found.');
        }

        if ($payload->parentId !== null) {
            $tag->setParentId($payload->parentId);
        }

        if ($payload->name !== null) {
            $tag->setName($payload->name);
        }

        $tag->save();
    }
}

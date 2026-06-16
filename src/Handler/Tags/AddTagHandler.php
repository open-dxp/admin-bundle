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

final class AddTagHandler
{
    public function __invoke(string $name, int $parentId): AddTagResult
    {
        $tag = new Tag();
        $tag->setName($name);
        $tag->setParentId($parentId);
        $tag->save();

        return new AddTagResult(id: $tag->getId());
    }
}

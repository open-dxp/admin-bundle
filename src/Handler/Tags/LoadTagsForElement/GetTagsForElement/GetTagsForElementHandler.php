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

namespace OpenDxp\Bundle\AdminBundle\Handler\Tags\LoadTagsForElement\GetTagsForElement;

use OpenDxp\Model\Element\Tag;

final class GetTagsForElementHandler
{
    public function __invoke(LoadTagsForElementPayload $payload): GetTagsForElementResult
    {
        $assignedTagArray = [];
        $assignedTags = Tag::getTagsForElement($payload->assignmentCType, $payload->assignmentCId);

        foreach ($assignedTags as $assignedTag) {
            $assignedTagArray[] = $this->convertTagToArray($assignedTag);
        }

        return new GetTagsForElementResult(tags: $assignedTagArray);
    }

    private function convertTagToArray(Tag $tag): array
    {
        $hasChildren = $tag->hasChildren();

        return [
            'id' => $tag->getId(),
            'text' => $tag->getName(),
            'path' => $tag->getNamePath(),
            'expandable' => $hasChildren,
            'leaf' => !$hasChildren,
            'iconCls' => 'opendxp_icon_element_tags',
            'qtipCfg' => [
                'title' => 'ID: ' . $tag->getId(),
            ],
        ];
    }
}

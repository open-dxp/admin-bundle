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

final class GetTagTreeChildrenHandler
{
    public function __invoke(
        bool $showSelection,
        ?int $assignmentCId,
        string $assignmentCType,
        ?string $node,
        ?string $filter,
    ): GetTagTreeChildrenResult {
        $assignedTagIds = [];
        if ($assignmentCId && $assignmentCType) {
            $assignedTags = Tag::getTagsForElement($assignmentCType, $assignmentCId);
            foreach ($assignedTags as $assignedTag) {
                $assignedTagIds[$assignedTag->getId()] = $assignedTag;
            }
        }

        $tagList = new Tag\Listing();
        if ($node) {
            $tagList->setCondition('parentId = ?', $node);
        } else {
            $tagList->setCondition('ISNULL(parentId) OR parentId = 0');
        }
        $tagList->setOrderKey('name');

        $recursiveChildren = false;
        if (!empty($filter)) {
            $filterIds = [0];
            $filterTagList = new Tag\Listing();
            $filterTagList->setCondition('LOWER(`name`) LIKE ?', ['%' . $filterTagList->escapeLike(mb_strtolower($filter)) . '%']);
            foreach ($filterTagList->load() as $filterTag) {
                if ($filterTag->getParentId() === 0) {
                    $filterIds[] = $filterTag->getId();
                } else {
                    $ids = explode('/', $filterTag->getIdPath());
                    if (isset($ids[1])) {
                        $filterIds[] = (int)$ids[1];
                    }
                }
            }

            $filterIds = array_unique($filterIds);
            $tagList->setCondition('id IN(' . implode(',', $filterIds) . ')');
            $recursiveChildren = true;
        }

        $tags = [];
        foreach ($tagList->load() as $tag) {
            $tags[] = $this->convertTagToArray($tag, $showSelection, $assignedTagIds, true, $recursiveChildren);
        }

        return new GetTagTreeChildrenResult(tags: $tags);
    }

    private function convertTagToArray(Tag $tag, bool $showSelection, array $assignedTagIds, bool $loadChildren = false, bool $recursiveChildren = false): array
    {
        $hasChildren = $tag->hasChildren();

        $tagArray = [
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

        if ($showSelection) {
            $tagArray['checked'] = isset($assignedTagIds[$tag->getId()]);
        }

        if ($hasChildren && $loadChildren) {
            $children = $tag->getChildren();
            $loadChildren = $recursiveChildren;
            foreach ($children as $child) {
                $tagArray['children'][] = $this->convertTagToArray($child, $showSelection, $assignedTagIds, $loadChildren, $recursiveChildren);
            }
        }

        return $tagArray;
    }
}

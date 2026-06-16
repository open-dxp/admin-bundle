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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject;

use OpenDxp\Bundle\AdminBundle\Service\ElementServiceInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class GetDataObjectChildrenHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementServiceInterface $elementService,
    ) {}

    public function __invoke(
        DataObject\AbstractObject $object,
        DataObject\Listing $childrenList,
        string $view,
        ?string $filter,
        int $limit,
        int $offset,
        int $fromPaging,
        array $objectTypes,
    ): GetDataObjectChildrenResult {
        $adminUser = $this->userContext->getAdminUser();
        $objects = [];

        $cv = $view ? ($this->elementService->getCustomViewById($view) ?? []) : [];

        if (!is_null($filter)) {
            // When filter is applied, limit was capped to 100 by caller
            $limit = 100;
        }

        $children = $childrenList->load();
        $filteredTotalCount = $childrenList->getTotalCount();

        foreach ($children as $child) {
            $objectTreeNode = $this->elementService->getElementTreeNodeConfig($child);
            // this if is obsolete since as long as the change with #11714 about list on line 175-179 are working fine, we already filter the list=1 there
            if ($objectTreeNode['permissions']['list'] == 1) {
                $objects[] = $objectTreeNode;
            }
        }

        //pagination for custom view
        $total = $cv
            ? $filteredTotalCount
            : $object->getChildAmount(null, $adminUser);

        return new GetDataObjectChildrenResult(
            objects: $objects,
            offset: $offset,
            limit: $limit,
            total: $total,
            filteredTotalCount: $filteredTotalCount,
            filter: $filter,
            fromPaging: $fromPaging,
        );
    }
}

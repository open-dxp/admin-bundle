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

namespace OpenDxp\Bundle\AdminBundle\Handler\Recyclebin;

use OpenDxp\Model\Element\Recyclebin;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\User;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class AddToRecyclebinHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(string $type, int $id): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $element = Service::getElementById($type, $id);

        if (!$element) {
            return;
        }

        $list = $element::getList(['unpublished' => true]);
        $list->setCondition('`path` LIKE ' . $list->quote($list->escapeLike($element->getRealFullPath()) . '/%'));
        $children = $list->getTotalCount();

        if ($children <= 100) {
            Recyclebin\Item::create($element, $adminUser);
        }
    }
}

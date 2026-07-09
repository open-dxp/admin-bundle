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

namespace OpenDxp\Bundle\AdminBundle\Handler\Recyclebin\AddToRecyclebin;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Model\Element\Recyclebin;
use OpenDxp\Model\Element\Service;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class AddToRecyclebinHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    /**
     * 1.x swallowed any failure here into a success:false@200 response - this runs as a
     * background housekeeping call on essentially every element delete across the admin UI.
     */
    public function __invoke(AddToRecyclebinPayload $payload): void
    {
        try {
            $this->addToRecyclebin($payload);
        } catch (\Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }
    }

    private function addToRecyclebin(AddToRecyclebinPayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $element = Service::getElementById($payload->type, $payload->id);

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

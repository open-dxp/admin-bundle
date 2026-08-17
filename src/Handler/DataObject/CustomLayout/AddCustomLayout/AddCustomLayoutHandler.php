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

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\AddCustomLayout;

use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Exception\ConfigWriteException;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class AddCustomLayoutHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(AddCustomLayoutPayload $payload): AddCustomLayoutResult
    {
        $layoutId = $payload->layoutIdentifier;
        $layoutName = $payload->layoutName;
        $classId = $payload->classId;

        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        if (DataObject\ClassDefinition\CustomLayout::getById($layoutId)) {
            throw new BadRequestHttpException('Custom Layout identifier already exists');
        }

        $customLayout = DataObject\ClassDefinition\CustomLayout::create([
            'name' => $layoutName,
            'userOwner' => $userId,
            'classId' => $classId,
        ]);

        $customLayout->setId($layoutId);

        if (!$customLayout->isWriteable()) {
            throw new ConfigWriteException();
        }

        $customLayout->save();

        $data = $customLayout->getObjectVars();
        $data['isWriteable'] = $customLayout->isWriteable();

        return new AddCustomLayoutResult(id: $customLayout->getId(), name: $customLayout->getName(), data: $data);
    }
}

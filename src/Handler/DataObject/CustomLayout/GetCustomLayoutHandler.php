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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout;

use OpenDxp\Model\DataObject;
use OpenDxp\Model\Exception\ConfigWriteException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class GetCustomLayoutHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(string $id): GetCustomLayoutResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($id);

        if (!$customLayout) {
            $brickLayoutSeparator = strpos($id, '.brick.');
            if ($brickLayoutSeparator !== false) {
                $parentLayout = DataObject\ClassDefinition\CustomLayout::getById(substr($id, 0, $brickLayoutSeparator));
                if ($parentLayout instanceof DataObject\ClassDefinition\CustomLayout) {
                    $customLayout = DataObject\ClassDefinition\CustomLayout::create([
                        'name' => $parentLayout->getName() . ' ' . substr($id, $brickLayoutSeparator + strlen('.brick.')),
                        'userOwner' => $userId,
                        'classId' => $parentLayout->getClassId(),
                    ]);
                    $customLayout->setId($id);
                    if (!$customLayout->isWriteable()) {
                        throw new ConfigWriteException();
                    }
                    $customLayout->save();
                }
            }

            if (!$customLayout) {
                throw new NotFoundHttpException();
            }
        }

        return new GetCustomLayoutResult($customLayout->getObjectVars(), $customLayout->isWriteable());
    }
}

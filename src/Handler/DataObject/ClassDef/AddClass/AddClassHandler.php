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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\AddClass;

use Exception;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Model\DataObject;

final class AddClassHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(AddClassPayload $payload): AddClassResult
    {
        $className = preg_replace('/[^a-zA-Z0-9_]+/', '', $payload->className);
        $className = preg_replace('/^\d+/', '', $className);

        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $existingClass = DataObject\ClassDefinition::getById($payload->classId);
        if ($existingClass) {
            throw new Exception('Class identifier already exists');
        }

        $class = DataObject\ClassDefinition::create([
            'name' => $className,
            'userOwner' => $userId,
        ]);

        $class->setId($payload->classId);
        $class->save(true);

        return new AddClassResult(id: $class->getId());
    }
}

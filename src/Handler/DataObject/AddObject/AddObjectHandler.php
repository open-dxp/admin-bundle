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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\AddObject;

use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class AddObjectHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly Model\FactoryInterface $modelFactory,
    ) {}

    public function __invoke(AddObjectPayload $payload): AddObjectResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $className = $payload->className;
        $classId = $payload->classId;
        $parentId = $payload->parentId;
        $key = $payload->key;
        $objectType = $payload->objectType;
        $variantViaTree = $payload->variantViaTree;
        $parent = DataObject::getById($parentId);
        if ($parent === null) {
            throw new NotFoundHttpException("Parent object not found: $parentId");
        }

        if (!$parent->isAllowed('create')) {
            throw new AdminOperationFailedException('prevented adding object because of missing permissions');
        }

        if (DataObject\Service::pathExists($parent->getRealFullPath() . '/' . $key)) {
            throw new AdminOperationFailedException('prevented creating object because object with same path+key already exists');
        }

        if ($variantViaTree) {
            if (!$parent instanceof DataObject\Concrete) {
                throw new BadRequestHttpException('Parent must be a concrete object for variant creation');
            }
            $classId = $parent->getClass()->getId();
        }

        $fqcn = 'OpenDxp\\Model\\DataObject\\' . ucfirst($className);
        /** @var DataObject\Concrete $object */
        $object = $this->modelFactory->build($fqcn);
        $object->setOmitMandatoryCheck(true);
        $object->setClassId($classId);
        $object->setClassName($className);
        $object->setParentId($parentId);
        $object->setKey($key);
        $object->setCreationDate(time());
        $object->setUserOwner($userId);
        $object->setUserModification($userId);
        $object->setPublished(false);

        if (in_array($objectType, [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_VARIANT])) {
            $object->setType($objectType);
        }

        $object->save();

        return new AddObjectResult(id: $object->getId() ?? 0, type: $object->getType());
    }
}

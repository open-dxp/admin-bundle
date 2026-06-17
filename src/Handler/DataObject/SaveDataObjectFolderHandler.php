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

use Exception;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\User;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Payload\DataObject\SaveDataObjectFolderPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class SaveDataObjectFolderHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(SaveDataObjectFolderPayload $payload): void
    {
        $adminUser = $this->userContext->getAdminUser();
        $id = $payload->id;
        $general = $payload->general;
        $propertiesData = $payload->propertiesData;
        $object = DataObject::getById($id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('DataObject with id %d not found', $id));
        }

        if (!$object->isAllowed('publish')) {
            throw new AccessDeniedHttpException('Missing permission to publish object');
        }

        $object->setValues($general);
        $object->setUserModification($adminUser->getId());

        $this->applyProperties($object, $propertiesData);

        $object->save();
    }

    private function applyProperties(DataObject\AbstractObject $object, ?array $propertiesData): void
    {
        if ($propertiesData === null) {
            return;
        }

        $properties = [];

        // preserve inherited properties
        foreach ($object->getProperties() as $p) {
            if ($p->isInherited()) {
                $properties[$p->getName()] = $p;
            }
        }

        foreach ($propertiesData as $propertyName => $propertyData) {
            $value = $propertyData['data'];

            try {
                $property = new Model\Property();
                $property->setType($propertyData['type']);
                $property->setName($propertyName);
                $property->setCtype('object');
                $property->setDataFromEditmode($value);
                $property->setInheritable($propertyData['inheritable']);

                $properties[$propertyName] = $property;
            } catch (Exception) {
                Logger::err("Can't add " . $propertyName . ' to object ' . $object->getRealFullPath());
            }
        }

        $object->setProperties($properties);
    }
}

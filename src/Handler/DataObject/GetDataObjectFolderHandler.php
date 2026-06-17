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

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizer;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetDataObjectFolderHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly ElementResponseNormalizer $normalizer,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(IdQueryPayload $payload): GetDataObjectFolderResult
    {
        $adminUser = $this->userContext->getAdminUser();
        $object = DataObject::getById($payload->id);

        if (!$object) {
            throw new NotFoundHttpException(sprintf('DataObject with id %d not found', $payload->id));
        }

        if (!$object->isAllowed('view')) {
            throw new AccessDeniedHttpException('Missing permission to view object');
        }

        $objectData = [];

        $objectData['general'] = [];
        $objectData['idPath'] = Element\Service::getIdPath($object);
        $objectData['type'] = $object->getType();
        $allowedKeys = ['published', 'key', 'id', 'type', 'path', 'modificationDate', 'creationDate', 'userOwner', 'userModification'];
        foreach ($object->getObjectVars() as $key => $value) {
            if (in_array($key, $allowedKeys)) {
                $objectData['general'][$key] = $value;
            }
        }
        $objectData['general']['fullpath'] = $object->getRealFullPath();
        $objectData['general']['locked'] = $object->isLocked();

        $objectData['properties'] = Element\Service::minimizePropertiesForEditmode($object->getProperties());
        $objectData['userPermissions'] = $object->getUserPermissions($adminUser);
        $objectData['classes'] = $this->prepareChildClasses($object->getDao()->getClasses());

        $this->normalizer->normalize($object, $objectData, self::class);

        $event = new GenericEvent($this, ['data' => $objectData, 'object' => $object]);
        $this->eventDispatcher->dispatch($event, AdminEvents::OBJECT_GET_PRE_SEND_DATA);
        $objectData = $event->getArgument('data');

        return new GetDataObjectFolderResult(data: $objectData);
    }

    /**
     * @param DataObject\ClassDefinition[] $classes
     */
    private function prepareChildClasses(array $classes): array
    {
        $reduced = [];
        foreach ($classes as $class) {
            $reduced[] = [
                'id' => $class->getId(),
                'name' => $class->getName(),
                'inheritance' => $class->getAllowInherit(),
            ];
        }

        return $reduced;
    }
}

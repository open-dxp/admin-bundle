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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrick;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrick\UpdateObjectBrickPayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UpdateObjectBrickHandler
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {}

    public function __invoke(UpdateObjectBrickPayload $payload): UpdateObjectBrickResult
    {
        $key = $payload->key;
        $title = $payload->title;
        $group = $payload->group;
        $isAdd = $payload->isAdd;
        $values = $payload->values;
        $configuration = $payload->configuration;

        if ($isAdd) {
            $list = new DataObject\Objectbrick\Definition\Listing();
            foreach ($list->loadNames() as $brickName) {
                if (strtolower($key) === strtolower($brickName)) {
                    throw new AdminOperationFailedException('Brick with the same name already exists (lower/upper cases may be different)');
                }
            }
        }

        $brickDef = new DataObject\Objectbrick\Definition();
        $brickDef->setKey($key);
        $brickDef->setTitle($title);
        $brickDef->setGroup($group);

        if ($values !== null) {
            $brickDef->setParentClass($values['parentClass']);
            $brickDef->setImplementsInterfaces($values['implementsInterfaces']);
            $brickDef->setClassDefinitions($values['classDefinitions']);
        }

        if ($configuration !== null) {
            $configuration['datatype'] = 'layout';
            $configuration['fieldtype'] = 'panel';

            $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
            $brickDef->setLayoutDefinitions($layout);
        }

        $event = new GenericEvent($this->currentControllerContext->getController(), ['brickDefinition' => $brickDef]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_OBJECTBRICK_UPDATE_DEFINITION);
        $brickDef = $event->getArgument('brickDefinition');

        $brickDef->save();

        return new UpdateObjectBrickResult(id: $brickDef->getKey());
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Model\DataObject;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SaveSelectOptionsHandler
{
    public function __construct(private readonly EventDispatcherInterface $eventDispatcher) {}

    public function __invoke(SaveSelectOptionsPayload $payload): SaveSelectOptionsResult
    {
        if ($payload->task === 'add' && (new DataObject\SelectOptions\Config\Listing())->hasConfig($payload->id)) {
            throw new BadRequestHttpException('Select options with the same ID already exists (lower/upper cases may be different)');
        }

        $selectOptionsConfiguration = DataObject\SelectOptions\Config::createFromData([
            DataObject\SelectOptions\Config::PROPERTY_ID => $payload->id,
            DataObject\SelectOptions\Config::PROPERTY_GROUP => $payload->group,
            DataObject\SelectOptions\Config::PROPERTY_USE_TRAITS => $payload->useTraits,
            DataObject\SelectOptions\Config::PROPERTY_IMPLEMENTS_INTERFACES => $payload->implementsInterfaces,
            DataObject\SelectOptions\Config::PROPERTY_SELECT_OPTIONS => $payload->selectOptionsData,
        ]);

        $event = new GenericEvent(null, ['selectOptionsConfiguration' => $selectOptionsConfiguration]);
        $this->eventDispatcher->dispatch($event, AdminEvents::CLASS_SELECTOPTIONS_UPDATE_CONFIGURATION);
        /** @var DataObject\SelectOptions\Config $selectOptionsConfiguration */
        $selectOptionsConfiguration = $event->getArgument('selectOptionsConfiguration');

        $selectOptionsConfiguration->save();

        return new SaveSelectOptionsResult(id: $selectOptionsConfiguration->getId());
    }
}

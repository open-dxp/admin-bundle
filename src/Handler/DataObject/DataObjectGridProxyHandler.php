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
use OpenDxp\Bundle\AdminBundle\Service\DataObject\DataObjectGridService;
use OpenDxp\Model\DataObject;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DataObjectGridProxyHandler
{
    public function __construct(
        private readonly DataObjectGridService $gridService,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(array $allParams, string $fallbackLocale): DataObjectGridProxyResult
    {
        $filterPrepareEvent = new GenericEvent($this, ['requestParams' => $allParams]);
        $this->eventDispatcher->dispatch($filterPrepareEvent, AdminEvents::OBJECT_LIST_BEFORE_FILTER_PREPARE);
        $allParams = $filterPrepareEvent->getArgument('requestParams');

        $requestedLanguage = $allParams['language'] ?? null;
        if (!$requestedLanguage) {
            $requestedLanguage = $fallbackLocale;
        }

        return new DataObjectGridProxyResult(
            data: $this->gridService->gridProxy($allParams, DataObject::OBJECT_TYPE_OBJECT, $requestedLanguage),
            requestedLanguage: $requestedLanguage,
        );
    }
}

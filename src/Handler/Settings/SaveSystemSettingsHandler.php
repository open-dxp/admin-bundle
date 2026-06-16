<?php

declare(strict_types=1);

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Helper\StopMessengerWorkersTrait;
use OpenDxp\SystemSettingsConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;

final class SaveSystemSettingsHandler
{
    use StopMessengerWorkersTrait;

    public function __construct(
        private readonly SystemSettingsConfig $config,
        private readonly ClearSymfonyCacheHandler $clearSymfonyCache,
        private readonly ClearOpenDxpCacheHandler $clearOpenDxpCache,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(array $values, string $env): void
    {
        $this->config->save($values);
        ($this->clearSymfonyCache)($env);
        $this->stopMessengerWorkers();

        $clearOpenDxpCache = $this->clearOpenDxpCache;
        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, static function (TerminateEvent $event) use ($clearOpenDxpCache): void {
            // delay to ensure messenger:stop-workers signal has been processed before cache is cleared
            sleep(2);
            $clearOpenDxpCache();
        });
    }
}

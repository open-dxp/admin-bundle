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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\SaveSystemSettings;

use OpenDxp\Bundle\AdminBundle\Handler\Settings\SaveSettingsPayload;
use OpenDxp\Bundle\AdminBundle\Service\Cache\OpenDxpCacheClearingService;
use OpenDxp\Bundle\AdminBundle\Service\Cache\SymfonyCacheClearingService;
use OpenDxp\Helper\StopMessengerWorkersTrait;
use OpenDxp\SystemSettingsConfig;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\Event\TerminateEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class SaveSystemSettingsHandler
{
    use StopMessengerWorkersTrait;

    public function __construct(
        private readonly SystemSettingsConfig $config,
        private readonly SymfonyCacheClearingService $symfonyCache,
        private readonly OpenDxpCacheClearingService $openDxpCache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly KernelInterface $kernel,
    ) {
    }

    public function __invoke(SaveSettingsPayload $payload): void
    {
        $env = $payload->env ?: $this->kernel->getEnvironment();

        $this->config->save($payload->values);
        $this->symfonyCache->clear($env);
        $this->stopMessengerWorkers();

        $openDxpCache = $this->openDxpCache;
        $this->eventDispatcher->addListener(KernelEvents::TERMINATE, static function (TerminateEvent $event) use ($openDxpCache): void {
            // delay to ensure messenger:stop-workers signal has been processed before cache is cleared
            sleep(2);
            $openDxpCache->clear();
        });
    }
}

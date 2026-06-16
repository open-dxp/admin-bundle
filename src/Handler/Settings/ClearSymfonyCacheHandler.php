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

use OpenDxp\Cache\Symfony\CacheClearer;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpKernel\KernelInterface;

final class ClearSymfonyCacheHandler
{
    public function __construct(
        private readonly KernelInterface $kernel,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly CacheClearer $cacheClearer,
    ) {}

    public function __invoke(string $environment): void
    {
        if ($this->kernel->getEnvironment() === $environment) {
            foreach ($this->eventDispatcher->getListeners(KernelEvents::TERMINATE) as $listener) {
                $this->eventDispatcher->removeListener(KernelEvents::TERMINATE, $listener);
            }

            foreach ($this->eventDispatcher->getListeners(KernelEvents::EXCEPTION) as $listener) {
                $this->eventDispatcher->removeListener(KernelEvents::EXCEPTION, $listener);
            }
        }

        $this->cacheClearer->clear($environment);
    }
}

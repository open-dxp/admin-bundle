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

namespace OpenDxp\Service\Cache;

use OpenDxp\Cache\Core\CoreCacheHandler;
use OpenDxp\Event\SystemEvents;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\Filesystem\Filesystem;

final class OpenDxpCacheClearingService
{
    public function __construct(
        private readonly CoreCacheHandler $cache,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Filesystem $filesystem,
    ) {}

    public function clear(): void
    {
        $this->cache->clearAll();

        if ($this->filesystem->exists(OPENDXP_CACHE_DIRECTORY)) {
            $this->filesystem->remove(OPENDXP_CACHE_DIRECTORY);
        }

        $this->filesystem->dumpFile(OPENDXP_CACHE_DIRECTORY . '/.gitkeep', '');

        $this->eventDispatcher->dispatch(new GenericEvent(), SystemEvents::CACHE_CLEAR);
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Tests\TestCase;

use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\EnvironmentTestCase;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\Messenger\Event\WorkerRunningEvent;
use Symfony\Component\Messenger\Worker;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

abstract class GenericExecutionEngineTestCase extends EnvironmentTestCase
{
    protected static function environment(): string
    {
        return 'generic_execution_engine';
    }

    protected function loadJobRun(int $id): JobRun
    {
        return Container::get(JobRunRepositoryInterface::class)->getJobRunById($id, forceReload: true);
    }

    /**
     * Handles the messages of the engine like a worker, until none is left.
     *
     * A worker has no logged-in user. A request of the test leaves its user in the token storage, so it is removed.
     */
    protected function consumeJobMessages(): void
    {
        Container::get(TokenStorageInterface::class)->setToken(null);

        $dispatcher = Container::get(EventDispatcherInterface::class);
        $stopWhenIdle = static function (WorkerRunningEvent $event): void {
            if ($event->isWorkerIdle()) {
                $event->getWorker()->stop();
            }
        };

        $transport = Container::get('messenger.transport.opendxp_generic_execution_engine');
        $worker = new Worker(
            ['opendxp_generic_execution_engine' => $transport],
            Container::get('generic.execution.engine.bus'),
            $dispatcher,
        );

        $dispatcher->addListener(WorkerRunningEvent::class, $stopWhenIdle);

        try {
            $worker->run(['sleep' => 0]);
        } finally {
            $dispatcher->removeListener(WorkerRunningEvent::class, $stopWhenIdle);
        }
    }
}

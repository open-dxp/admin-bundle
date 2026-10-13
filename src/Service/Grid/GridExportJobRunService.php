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

namespace OpenDxp\Bundle\AdminBundle\Service\Grid;

use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\Messenger\WriteGridExportMessage;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Installer;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\Job;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Model\JobStep;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Repository\JobRunRepositoryInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Utils\Enums\SelectionProcessingMode;
use OpenDxp\Model\Exception\NotFoundException;
use OpenDxp\Model\Tool\TmpStore;
use Symfony\Component\Lock\LockFactory;

/**
 * Exists only when the engine is in the kernel and the option opendxp_admin.background_jobs is on.
 */
final class GridExportJobRunService
{
    private const string JOB_NAME = 'grid_export';

    private const string EXECUTION_CONTEXT = 'admin_grid_export';

    private const string STORE_PREFIX = 'grid_export_job_run_';

    private const string STORE_TAG = 'grid_export_job_run';

    private const int LIFETIME = 86400;

    public function __construct(
        private readonly JobExecutionAgentInterface $jobExecutionAgent,
        private readonly JobRunRepositoryInterface $jobRunRepository,
        private readonly Installer $installer,
        private readonly LockFactory $lockFactory,
    ) {
    }

    public function canCreateJobRun(): bool
    {
        return $this->installer->isInstalled();
    }

    /**
     * Returns the job run of the first call on a second call for the same export.
     */
    public function createJobRun(GridExport $export): JobRun
    {
        $lock = $this->lockFactory->createLock(self::STORE_PREFIX . $export->id);
        $lock->acquire(blocking: true);

        try {
            $jobRun = $this->findStartedJobRun($export);
            if ($jobRun instanceof JobRun) {
                return $jobRun;
            }

            $job = new Job(self::JOB_NAME, [
                new JobStep(
                    'Write the grid export',
                    WriteGridExportMessage::class,
                    '',
                    ['exportId' => $export->id],
                    SelectionProcessingMode::ONCE,
                ),
            ]);

            $jobRun = $this->jobExecutionAgent->startJobExecution(
                $job,
                $export->query->userId,
                self::EXECUTION_CONTEXT,
            );
            TmpStore::set(self::STORE_PREFIX . $export->id, $jobRun->getId(), self::STORE_TAG, self::LIFETIME);

            return $jobRun;
        } finally {
            $lock->release();
        }
    }

    private function findStartedJobRun(GridExport $export): ?JobRun
    {
        // The TmpStore hands a number back as a string.
        $jobRunId = TmpStore::get(self::STORE_PREFIX . $export->id)?->getData();
        if ($jobRunId === null) {
            return null;
        }

        try {
            return $this->jobRunRepository->getJobRunById((int) $jobRunId);
        } catch (NotFoundException) {
            return null;
        }
    }
}

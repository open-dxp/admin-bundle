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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExportJobRun;

use OpenDxp\Bundle\AdminBundle\Test\GridExport\GridExportFile;
use OpenDxp\Bundle\AdminBundle\Tests\Factory\InheritanceFactory;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Agent\JobExecutionAgentInterface;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Entity\JobRun;
use OpenDxp\Bundle\GenericExecutionEngineBundle\Storage\JobRunArtifactStorage;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\DataObjectFolderFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\Tool\Storage;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
});

afterEach(function () {
    Storage::get('temp')->deleteDirectory('job-run');
});

/**
 * @return array<string, mixed>
 */
function startExport(User $user, string $source): array
{
    $response = requestGridExport($user, $source)->content();

    return json_decode($response, true, flags: JSON_THROW_ON_ERROR);
}

function createJobRunOfExport(User $user, string $exportId): int
{
    $response = createGridExportJobRun($user, $exportId)->content();

    return json_decode($response, true, flags: JSON_THROW_ON_ERROR)['jobRunId'];
}

function getArtifact(JobRun $jobRun): GridExportFile
{
    $filename = $jobRun->getResult()->artifacts[0];
    $stream = Container::get(JobRunArtifactStorage::class)->getStream($jobRun->getId(), $filename);

    return new GridExportFile($filename, stream_get_contents($stream));
}

it('offers a job run for the export while the engine is installed', function () {
    $response = startExport(UserFactory::new()->withPermissions('objects')->create(), 'mock');

    expect($response)->toHaveKey('canCreateJobRun', true);
});

it('hands the file of the export over as the artifact of the job run', function () {
    $jobRunId = createJobRunOfExport($this->admin, startExport($this->admin, 'mock')['id']);

    $this->consumeJobMessages();

    $jobRun = $this->loadJobRun($jobRunId);
    expect($jobRun->getResult())
        ->message
        ->toBe('grid_export_job_run_finished')
        ->artifacts
        ->toBe([sprintf('mock-%s.csv', date('Y-m-d'))])
        ->and(getArtifact($jobRun)->getColumn('ID'))
        ->toBe(['1', '2', '3']);
});

it('reports the rows of the export as the progress of the job run', function () {
    $jobRunId = createJobRunOfExport($this->admin, startExport($this->admin, 'mock')['id']);

    $this->consumeJobMessages();

    expect($this->loadJobRun($jobRunId))
        ->getTotal()
        ->toBe(3)
        ->getProgress()
        ->toBe(3);
});

it('starts a new job run for an export whose job run is gone', function () {
    $exportId = startExport($this->admin, 'mock')['id'];
    $jobRunId = createJobRunOfExport($this->admin, $exportId);
    Container::get(JobExecutionAgentInterface::class)->cancelJobRun($jobRunId);

    expect(createJobRunOfExport($this->admin, $exportId))->not->toBe($jobRunId);
});

it('writes an operator column of the object grid in the job run', function () {
    $folder = DataObjectFolderFactory::createOne();
    InheritanceFactory::new()
        ->withParent($folder)
        ->sequence([
            ['normalinput' => 'First'],
            ['normalinput' => 'Second'],
        ])
        ->create();
    $exportId = startObjectGridExportWithAnyGetter($this->admin, $folder->getId(), 'Any getter', 'normalinput');
    $jobRunId = createJobRunOfExport($this->admin, $exportId);

    $this->consumeJobMessages();

    expect(getArtifact($this->loadJobRun($jobRunId))->getColumn('Any getter'))
        ->toEqualCanonicalizing(['First', 'Second']);
});

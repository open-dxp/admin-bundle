<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetReplaceAssignmentsBatchJobs;

final readonly class GetReplaceAssignmentsBatchJobsResult
{
    public function __construct(
        public readonly array $jobs,
    ) {}
}

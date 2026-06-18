<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetReplaceAssignmentsBatchJobs;

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetReplaceAssignmentsBatchJobsHandler
{
    public function __invoke(GetReplaceAssignmentsBatchJobsPayload $payload): GetReplaceAssignmentsBatchJobsResult
    {
        $element = null;

        if ($payload->id) {
            $element = Service::getElementById($payload->type, $payload->id);
        } elseif ($payload->path) {
            $element = Service::getElementByPath($payload->type, $payload->path);
        }

        if (!$element instanceof ElementInterface) {
            throw new NotFoundHttpException();
        }

        return new GetReplaceAssignmentsBatchJobsResult(jobs: $element->getDependencies()->getRequiredBy());
    }
}

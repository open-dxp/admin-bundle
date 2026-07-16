<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogDetails;

use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Model\Tool\Email\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetEmailLogDetailsHandler
{
    public function __invoke(IdQueryPayload $payload): GetEmailLogDetailsResult
    {
        $log = Log::getById($payload->id);

        if (!$log) {
            throw new NotFoundHttpException(sprintf('Email log with id %d not found', $payload->id));
        }

        return new GetEmailLogDetailsResult(objectVars: $log->getObjectVars());
    }
}
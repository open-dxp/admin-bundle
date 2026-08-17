<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogHtml;

use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Model\Tool\Email\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetEmailLogHtmlHandler
{
    public function __invoke(IdQueryPayload $payload): GetEmailLogHtmlResult
    {
        $log = Log::getById($payload->id);

        if (!$log) {
            throw new NotFoundHttpException(sprintf('Email log with id %d not found', $payload->id));
        }

        return new GetEmailLogHtmlResult(htmlLog: $log->getHtmlLog() ?: null);
    }
}
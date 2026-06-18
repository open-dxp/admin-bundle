<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog;

use OpenDxp\Model\Tool\Email\Log;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ShowEmailLogHandler
{
    public function __invoke(int $id): GetEmailLogResult
    {
        $log = Log::getById($id);

        if (!$log) {
            throw new NotFoundHttpException(sprintf('Email log with id %d not found', $id));
        }

        return new GetEmailLogResult(
            textLog: $log->getTextLog() ?: null,
            htmlLog: $log->getHtmlLog() ?: null,
            objectVars: $log->getObjectVars(),
        );
    }
}

<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\DeleteEmailLog;

use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Model\Tool;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class DeleteEmailLogHandler
{
    public function __invoke(IdBodyPayload $payload): void
    {
        $emailLog = Tool\Email\Log::getById($payload->id);
        if (!$emailLog instanceof Tool\Email\Log) {
            throw new NotFoundHttpException('Email log with ID ' . $payload->id . ' not found.');
        }

        $emailLog->delete();
    }
}

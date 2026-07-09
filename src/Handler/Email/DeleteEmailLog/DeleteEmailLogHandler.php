<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\DeleteEmailLog;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Model\Tool;

final class DeleteEmailLogHandler
{
    public function __invoke(IdBodyPayload $payload): void
    {
        $emailLog = Tool\Email\Log::getById($payload->id);
        if (!$emailLog instanceof Tool\Email\Log) {
            throw new AdminOperationFailedException('Email log with ID ' . $payload->id . ' not found.');
        }

        $emailLog->delete();
    }
}

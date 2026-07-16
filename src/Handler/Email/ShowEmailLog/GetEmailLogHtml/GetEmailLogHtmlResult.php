<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\ShowEmailLog\GetEmailLogHtml;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class GetEmailLogHtmlResult implements ResultInterface
{
    public function __construct(
        public readonly ?string $htmlLog,
    ) {}
}
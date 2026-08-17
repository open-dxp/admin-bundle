<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\CreateBlocklistEntry;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class CreateBlocklistEntryResult implements ResultInterface
{
    public function __construct(
        public array $data,
    ) {}
}

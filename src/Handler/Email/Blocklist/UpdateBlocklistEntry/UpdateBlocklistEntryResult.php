<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\UpdateBlocklistEntry;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class UpdateBlocklistEntryResult implements ResultInterface
{
    public function __construct(
        public array $data,
    ) {}
}

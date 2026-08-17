<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\DeleteBlocklistEntry;

use OpenDxp\Bundle\AdminBundle\Handler\ResultInterface;

final readonly class DeleteBlocklistEntryResult implements ResultInterface
{
    public function __construct(
        public array $data = [],
    ) {}
}

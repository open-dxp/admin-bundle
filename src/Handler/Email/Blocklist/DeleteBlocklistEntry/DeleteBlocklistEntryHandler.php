<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\Blocklist\DeleteBlocklistEntry;

use OpenDxp\Model\Tool;
use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;

final class DeleteBlocklistEntryHandler
{
    public function __invoke(BlocklistPayload $payload): void
    {
        $entry = Tool\Email\Blocklist::getByAddress($payload->data['address']);
        $entry->delete();
    }
}

<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Email\GetBlocklist;

use OpenDxp\Model\Tool;
use OpenDxp\Bundle\AdminBundle\Handler\Email\BlocklistPayload;

final class GetBlocklistHandler
{
    public function __invoke(BlocklistPayload $payload): GetBlocklistResult
    {
        $list = new Tool\Email\Blocklist\Listing();

        $list->setLimit($payload->limit);
        $list->setOffset($payload->offset);

        if ($payload->sortingSettings['orderKey']) {
            $list->setOrderKey($payload->sortingSettings['orderKey']);
            $list->setOrder($payload->sortingSettings['order']);
        }

        if ($payload->filter !== null) {
            $list->setCondition('`address` LIKE ' . $list->quote('%' . $payload->filter . '%'));
        }

        $data = $list->load();
        $jsonData = [];
        foreach ($data as $entry) {
            $jsonData[] = $entry->getObjectVars();
        }

        return new GetBlocklistResult(data: $jsonData, total: $list->getTotalCount());
    }
}

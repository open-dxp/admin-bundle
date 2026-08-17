<?php

declare(strict_types=1);

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Copy\CopyInfo;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\CopySessionGateway;
use OpenDxp\Model\Asset;
use Symfony\Component\Routing\RouterInterface;

final class CopyInfoHandler
{
    public function __construct(
        private readonly RouterInterface $router,
        private readonly CopySessionGateway $copySession,
    ) {
    }

    public function __invoke(CopyInfoPayload $payload): CopyInfoResult
    {
        $transactionId = time();
        $this->copySession->startTransaction((string) $transactionId);
        $pasteJobs = [];

        if ($payload->type === 'recursive') {
            $pasteJobs[] = [[
                'url' => $this->router->generate('opendxp_admin_asset_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $payload->sourceId,
                    'targetId' => $payload->targetId,
                    'type' => 'child',
                    'transactionId' => $transactionId,
                    'saveParentId' => true,
                ],
            ]];

            $asset = Asset::getById($payload->sourceId) ?? throw new AssetNotFoundException($payload->sourceId);

            if ($asset->hasChildren()) {
                $list = new Asset\Listing();
                $list->setCondition('`path` LIKE ?', [$list->escapeLike($asset->getRealFullPath()) . '/%']);
                $list->setOrderKey('LENGTH(`path`)', false);
                $list->setOrder('ASC');

                foreach ($list->loadIdList() as $id) {
                    $pasteJobs[] = [[
                        'url' => $this->router->generate('opendxp_admin_asset_copy'),
                        'method' => 'POST',
                        'params' => [
                            'sourceId' => $id,
                            'targetParentId' => $payload->targetId,
                            'sourceParentId' => $payload->sourceId,
                            'type' => 'child',
                            'transactionId' => $transactionId,
                        ],
                    ]];
                }
            }
        } elseif ($payload->type === 'child' || $payload->type === 'replace') {
            $pasteJobs[] = [[
                'url' => $this->router->generate('opendxp_admin_asset_copy'),
                'method' => 'POST',
                'params' => [
                    'sourceId' => $payload->sourceId,
                    'targetId' => $payload->targetId,
                    'type' => $payload->type,
                    'transactionId' => $transactionId,
                ],
            ]];
        }

        return new CopyInfoResult($pasteJobs);
    }
}

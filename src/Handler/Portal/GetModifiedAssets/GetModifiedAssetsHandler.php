<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\GetModifiedAssets;

use OpenDxp\Bundle\AdminBundle\Payload\Common\EmptyPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Asset;

final class GetModifiedAssetsHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(EmptyPayload $payload): GetModifiedAssetsResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $list = Asset::getList([
            'limit' => 10,
            'order' => 'DESC',
            'orderKey' => 'modificationDate',
            'condition' => 'userModification = ' . $userId,
        ]);

        $assets = [];
        foreach ($list as $doc) {
            /** @var Asset $doc */
            if ($doc->isAllowed('view')) {
                $assets[] = [
                    'id' => $doc->getId(),
                    'type' => $doc->getType(),
                    'path' => $doc->getRealFullPath(),
                    'date' => $doc->getModificationDate(),
                ];
            }
        }

        return new GetModifiedAssetsResult(assets: $assets);
    }
}

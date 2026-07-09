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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\ExecuteAssetBatch;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\ExecuteAssetBatch\ExecuteAssetBatchPayload;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridBatchService;
use OpenDxp\Model\User;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class ExecuteAssetBatchHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly GridBatchService $gridBatchService,
    ) {}

    public function __invoke(ExecuteAssetBatchPayload $payload): void
    {
        $data = $payload->data ?? [];
        $adminUser = $this->userContext->getAdminUser();
        // Returns false when there is no asset to update (job already completed) — not an error.
        // Throws on permission denied or save failure.
        try {
            $this->gridBatchService->executeAssetBatch($data, $adminUser);
        } catch (\Exception $e) {
            throw new AdminOperationFailedException($e->getMessage());
        }
    }
}

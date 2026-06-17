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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use Exception;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\GetHardlinkData\GetHardlinkDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\SaveHardlink\SaveHardlinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\SaveHardlink\SaveHardlinkPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/hardlink', name: 'opendxp_admin_document_hardlink_')]
class HardlinkController extends DocumentControllerBase
{
    /**
     * @throws Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetHardlinkDataHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse
    {
        try {
            $result = $handler($payload);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        return $this->preSendDataActions($result->data, $result->link);
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(SaveHardlinkPayload $payload, SaveHardlinkHandler $handler): JsonResponse
    {
        $result = $handler($payload);

        return $this->adminJson(ApiResponse::ok([
            'data' => [
                'versionDate' => $result->link->getModificationDate(),
                'versionCount' => $result->link->getVersionCount(),
            ],
            'treeData' => $result->treeData,
        ]));
    }
}

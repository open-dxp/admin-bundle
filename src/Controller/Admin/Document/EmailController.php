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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\Document;

use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Exception\ElementLockedException;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Email\GetEmailData\GetEmailDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Email\SaveEmail\SaveEmailHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Email\SaveEmail\SaveEmailPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/email', name: 'opendxp_admin_document_email_')]
class EmailController extends DocumentControllerBase
{
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetEmailDataHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse
    {
        try {
            $result = $handler($payload);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        return $this->preSendDataActions($result->data, $result->email);
    }

    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(SaveEmailPayload $payload, SaveEmailHandler $handler): JsonResponse
    {
        try {
            $result = $handler($payload);
        } catch (ElementLockedException $e) {
            return $this->getEditLockResponse($e->getElementId(), $e->getElementType());
        }

        if ($result->task === self::TASK_PUBLISH || $result->task === self::TASK_UNPUBLISH) {
            return $this->adminJson(ApiResponse::ok([
                'data' => [
                    'versionDate' => $result->email->getModificationDate(),
                    'versionCount' => $result->email->getVersionCount(),
                ],
                'treeData' => $result->treeData,
            ]));
        }

        $draftData = [];
        if ($result->version) {
            $draftData = [
                'id' => $result->version->getId(),
                'modificationDate' => $result->version->getDate(),
                'isAutoSave' => $result->version->isAutoSave(),
            ];
        }

        return $this->adminJson(ApiResponse::ok(['draft' => $draftData]));
    }
}

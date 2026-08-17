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
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\GetFolderData\GetFolderDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\GetFolderData\GetFolderDataPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\SaveFolder\SaveFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\SaveFolder\SaveFolderPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/folder', name: 'opendxp_admin_document_folder_')]
class FolderController extends DocumentControllerBase
{
    /**
     * @throws Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetFolderDataHandler $handler,
        GetFolderDataPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(SaveFolderPayload $payload, SaveFolderHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }
}

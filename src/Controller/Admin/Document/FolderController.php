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
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\GetFolderDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\SaveFolderHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Document\FolderPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
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
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse
    {
        $result = $handler($id);
        return $this->preSendDataActions($result->data, $result->folder);
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(Request $request, SaveFolderHandler $handler): JsonResponse
    {
        $result = $handler((int) $request->request->get('id'), FolderPayload::fromRequest($request));

        return $this->adminJson(ApiResponse::ok(['treeData' => $result->treeData]));
    }
}

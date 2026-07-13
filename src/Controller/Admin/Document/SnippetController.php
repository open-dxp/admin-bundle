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
use OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\GetSnippetData\GetSnippetDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\SaveSnippet\SaveSnippetHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\SaveSnippet\SaveSnippetPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/snippet', name: 'opendxp_admin_document_snippet_')]
class SnippetController extends DocumentControllerBase
{
    /**
     * @throws Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetSnippetDataHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse
    {
        $result = $handler($payload);

        return $this->preSendDataActions($result->data, $result->snippet);
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(SaveSnippetPayload $payload, SaveSnippetHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }
}

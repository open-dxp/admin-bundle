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
use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Link\GetLinkData\GetLinkDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Link\SaveLink\SaveLinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Link\SaveLink\SaveLinkPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/link', name: 'opendxp_admin_document_link_')]
class LinkController extends DocumentControllerBase
{
    /**
     * @throws Exception
     */
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    #[SessionIdentityAware]
    public function getDataByIdAction(
        GetLinkDataHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    /**
     * @throws Exception
     */
    #[Route('/save', name: 'save', methods: ['POST', 'PUT'])]
    public function saveAction(SaveLinkPayload $payload, SaveLinkHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }
}

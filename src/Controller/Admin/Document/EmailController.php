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

use OpenDxp\Bundle\AdminBundle\Attribute\SessionIdentityAware;
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
    #[SessionIdentityAware]
    #[Route('/get-data-by-id', name: 'getdatabyid', methods: ['GET'])]
    public function getDataByIdAction(
        GetEmailDataHandler $handler,
        IdQueryPayload $payload,
    ): JsonResponse
    {
        return $this->apiJson($handler($payload), rootProperty: 'data');
    }

    #[SessionIdentityAware]
    #[Route('/save', name: 'save', methods: ['PUT', 'POST'])]
    public function saveAction(SaveEmailPayload $payload, SaveEmailHandler $handler): JsonResponse
    {
        return $this->apiJson($handler($payload));
    }
}

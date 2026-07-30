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
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Version\DiffVersions\DiffVersionsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Version\DiffVersions\DiffVersionsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Version\PublishVersion\PublishVersionHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Version\SaveVersionToSession\SaveVersionToSessionHandler;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdBodyPayload;
use OpenDxp\Security\CorePermission;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/document')]
class DocumentVersionController extends AdminAbstractController
{
    #[IsGranted(CorePermission::Documents->value)]
    #[SessionIdentityAware]
    #[Route('/version-to-session', name: 'opendxp_admin_document_document_versiontosession', methods: ['POST'])]
    public function versionToSessionAction(
        IdBodyPayload              $payload,
        SaveVersionToSessionHandler $handler,
    ): Response {
        $handler($payload);

        return new Response();
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[SessionIdentityAware]
    #[Route('/publish-version', name: 'opendxp_admin_document_document_publishversion', methods: ['POST'])]
    public function publishVersionAction(
        IdBodyPayload          $payload,
        PublishVersionHandler  $handler,
    ): JsonResponse {
        return $this->apiJson($handler($payload));
    }

    #[IsGranted(CorePermission::Documents->value)]
    #[Route('/diff-versions/from/{from}/to/{to}', name: 'opendxp_admin_document_document_diffversions', requirements: ['from' => "\d+", 'to' => "\d+"], methods: ['GET'])]
    public function diffVersionsAction(
        DiffVersionsPayload $payload,
        DiffVersionsHandler $handler,
    ): Response {
        $result = $handler($payload);

        if (!$result->supported) {
            return $this->render('@OpenDxpAdmin/admin/document/document/diff_versions_unsupported.html.twig');
        }

        return $this->render('@OpenDxpAdmin/admin/document/document/diff_versions.html.twig', [
            'image' => $result->image,
            'image1' => $result->image1,
            'image2' => $result->image2,
        ]);
    }

    public function diffVersionsHtmlAction(
        #[MapQueryParameter] ?string $id = null,
    ): BinaryFileResponse {
        $file = OPENDXP_SYSTEM_TEMP_DIRECTORY . '/' . basename($id ?? '');
        if (file_exists($file)) {
            return new BinaryFileResponse($file);
        }

        throw $this->createNotFoundException('Version diff file not found');
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Controller\GDPR;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\OpenDxpUsers\ExportUserData\ExportUserDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\OpenDxpUsers\SearchUsers\SearchUsersHandler;
use OpenDxp\Bundle\AdminBundle\Handler\GDPR\SearchDataPayload;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Class OpenDxpController
 *
 * @internal
 */
#[Route('/opendxp-users')]
#[IsGranted(AdminPermission::GdprDataExtractor->value)]
class OpenDxpUsersController extends AdminAbstractController
{
    #[Route('/search-users', name: 'opendxp_admin_gdpr_opendxpusers_searchusers', methods: ['GET'])]
    public function searchUsersAction(SearchUsersHandler $handler, SearchDataPayload $payload): JsonResponse
    {
        return $this->adminJson($handler($payload)->data);
    }

    #[Route('/export-user-data', name: 'opendxp_admin_gdpr_opendxpusers_exportuserdata', methods: ['GET'])]
    public function exportUserDataAction(ExportUserDataHandler $handler, IdQueryPayload $payload): JsonResponse
    {
        $this->checkPermission('users');
        $result = $handler($payload);

        $json = $this->encodeJson($result->data, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-userdata-' . $result->data['id'] . '.json"',
        ], true);
    }
}

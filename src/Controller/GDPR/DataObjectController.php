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

use Exception;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\DataObjects;
use OpenDxp\Bundle\AdminBundle\Security\Permission\AdminPermission;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/data-object')]
#[IsGranted(AdminPermission::GdprDataExtractor->value)]
class DataObjectController extends AdminAbstractController
{
    #[Route('/search-data-objects', name: 'opendxp_admin_gdpr_dataobject_searchdataobjects', methods: ['GET'])]
    public function searchDataObjectsAction(Request $request, DataObjects $service): JsonResponse
    {
        $allParams = $request->query->all();

        $result = $service->searchData(
            (int)$allParams['id'],
            strip_tags($allParams['firstname']),
            strip_tags($allParams['lastname']),
            strip_tags($allParams['email']),
            (int)$allParams['start'],
            (int)$allParams['limit'],
            $allParams['sort'] ?? null
        );

        return $this->adminJson($result);
    }

    /**
     * @throws Exception
     */
    #[Route('/export', name: 'opendxp_admin_gdpr_dataobject_exportdataobject', methods: ['GET'])]
    public function exportDataObjectAction(
        DataObjects $service,
        #[MapQueryParameter] int $id = 0,
    ): JsonResponse
    {
        $object = DataObject::getById($id);

        if (!$object) {
            throw $this->createNotFoundException('Object not found');
        }

        if (!$object->isAllowed('view')) {
            throw $this->createAccessDeniedException('Export denied');
        }

        $exportResult = $service->doExportData($object);

        $json = $this->encodeJson($exportResult, [], JsonResponse::DEFAULT_ENCODING_OPTIONS | JSON_PRETTY_PRINT);

        return new JsonResponse($json, 200, [
            'Content-Disposition' => 'attachment; filename="export-data-object-' . $object->getId() . '.json"',
        ], true);
    }
}

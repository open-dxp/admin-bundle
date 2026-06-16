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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin\DataObject;

use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Dto\Response\ApiResponse;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertAllQuantityValuesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertQuantityValueHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ExportQuantityValueUnitsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\GetQuantityValueUnitListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\GetQuantityValueUnitsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ImportQuantityValueUnitsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ManageQuantityValueUnitHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * @internal
 */
#[Route('/quantity-value', name: 'opendxp_admin_dataobject_quantityvalue_')]
class QuantityValueController extends AdminAbstractController
{
    #[Route('/unit-import', name: 'unitimport', methods: ['POST', 'PUT'])]
    public function unitImportAction(Request $request, ImportQuantityValueUnitsHandler $importUnits): JsonResponse
    {
        /** @var UploadedFile $uploadFile */
        $uploadFile = $request->files->get('Filedata');

        $success = $importUnits(file_get_contents($uploadFile->getPathname()));
        $response = $this->adminJson(ApiResponse::fromBool($success));
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/unit-export', name: 'unitexport', methods: ['GET'])]
    public function unitExportAction(ExportQuantityValueUnitsHandler $exportUnits): Response
    {
        $response = new Response($exportUnits());
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment;filename: "quantityvalue_unit_export.json"');

        return $response;
    }

    #[Route('/unit-proxy', name: 'unitproxyget', methods: ['GET'])]
    #[IsGranted(CorePermission::QuantityValueUnits->value)]
    public function unitProxyGetAction(
        GetQuantityValueUnitsHandler $getUnits,
        Request $request,
        #[MapQueryParameter] int $limit = 25,
        #[MapQueryParameter] int $start = 0,
        #[MapQueryParameter] ?string $filter = null,
    ): JsonResponse {
        $result = $getUnits($request->query->all(), $limit, $start, $filter);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[Route('/unit-proxy', name: 'unitproxy', methods: ['POST', 'PUT'])]
    #[IsGranted(CorePermission::QuantityValueUnits->value)]
    public function unitProxyAction(
        ManageQuantityValueUnitHandler $manageUnit,
        Request $request,
        #[MapQueryParameter] ?string $xaction = null,
    ): JsonResponse {
        if (!$request->request->has('data')) {
            throw new BadRequestHttpException();
        }

        $data = json_decode($request->request->get('data'), true);
        $result = $manageUnit((string) $xaction, $data);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data]));
    }

    #[Route('/unit-list', name: 'unitlist', methods: ['GET'])]
    public function unitListAction(
        GetQuantityValueUnitListHandler $getUnitList,
        #[MapQueryParameter] ?string $filter = null,
    ): JsonResponse {
        $result = $getUnitList($filter);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[Route('/convert', name: 'convert', methods: ['GET'])]
    #[IsGranted(CorePermission::Objects->value)]
    public function convertAction(
        ConvertQuantityValueHandler $convert,
        #[MapQueryParameter] ?string $fromUnit = null,
        #[MapQueryParameter] ?string $toUnit = null,
        #[MapQueryParameter] ?string $value = null,
    ): JsonResponse {
        $result = $convert($fromUnit, $toUnit, $value);

        return $this->adminJson(ApiResponse::ok(['value' => $result->value]));
    }

    #[Route('/convert-all', name: 'convertall', methods: ['GET'])]
    #[IsGranted(CorePermission::Objects->value)]
    public function convertAllAction(
        ConvertAllQuantityValuesHandler $convertAll,
        #[MapQueryParameter] ?string $unit = null,
        #[MapQueryParameter] ?string $value = null,
    ): JsonResponse {
        $result = $convertAll($unit, $value);

        return $this->adminJson(ApiResponse::ok([
            'value' => $result->value,
            'fromUnit' => $result->fromUnit,
            'values' => $result->values,
        ]));
    }
}

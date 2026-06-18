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
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertAllQuantityValues\ConvertAllQuantityValuesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertAllQuantityValues\ConvertAllQuantityValuesPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertQuantityValue\ConvertQuantityValueHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ConvertQuantityValue\ConvertQuantityValuePayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\CreateQuantityValueUnit\CreateQuantityValueUnitHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\DeleteQuantityValueUnit\DeleteQuantityValueUnitHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ExportQuantityValueUnits\ExportQuantityValueUnitsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\GetQuantityValueUnitList\GetQuantityValueUnitListHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\GetQuantityValueUnitList\GetQuantityValueUnitListPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\GetQuantityValueUnits\GetQuantityValueUnitsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\GetQuantityValueUnits\GetQuantityValueUnitsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ImportQuantityValueUnits\ImportQuantityValueUnitsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\ImportQuantityValueUnits\ImportQuantityValueUnitsPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\QuantityValueUnitPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\QuantityValue\UpdateQuantityValueUnit\UpdateQuantityValueUnitHandler;
use OpenDxp\Bundle\AdminBundle\Security\Permission\CorePermission;
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
    public function unitImportAction(ImportQuantityValueUnitsPayload $payload, ImportQuantityValueUnitsHandler $importUnits): JsonResponse
    {
        $success = $importUnits($payload);
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
    public function unitProxyGetAction(GetQuantityValueUnitsHandler $getUnits, GetQuantityValueUnitsPayload $payload): JsonResponse
    {
        $result = $getUnits($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[Route('/unit-proxy', name: 'unitproxy', methods: ['POST', 'PUT'])]
    #[IsGranted(CorePermission::QuantityValueUnits->value)]
    public function unitProxyAction(
        Request $request,
        #[MapQueryParameter] ?string $xaction = null,
    ): Response {
        return match ($xaction) {
            'destroy' => $this->forward(self::class . '::unitProxyDestroyAction', [], $request->query->all()),
            'update'  => $this->forward(self::class . '::unitProxyUpdateAction', [], $request->query->all()),
            'create'  => $this->forward(self::class . '::unitProxyCreateAction', [], $request->query->all()),
            default   => throw new BadRequestHttpException(),
        };
    }

    #[Route('/unit-proxy-destroy', name: 'unitproxy_destroy', methods: ['POST', 'PUT'])]
    #[IsGranted(CorePermission::QuantityValueUnits->value)]
    public function unitProxyDestroyAction(
        QuantityValueUnitPayload $payload,
        DeleteQuantityValueUnitHandler $deleteUnit,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $deleteUnit($payload)->data]));
    }

    #[Route('/unit-proxy-update', name: 'unitproxy_update', methods: ['POST', 'PUT'])]
    #[IsGranted(CorePermission::QuantityValueUnits->value)]
    public function unitProxyUpdateAction(
        QuantityValueUnitPayload $payload,
        UpdateQuantityValueUnitHandler $updateUnit,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $updateUnit($payload)->data]));
    }

    #[Route('/unit-proxy-create', name: 'unitproxy_create', methods: ['POST', 'PUT'])]
    #[IsGranted(CorePermission::QuantityValueUnits->value)]
    public function unitProxyCreateAction(
        QuantityValueUnitPayload $payload,
        CreateQuantityValueUnitHandler $createUnit,
    ): JsonResponse {
        return $this->adminJson(ApiResponse::ok(['data' => $createUnit($payload)->data]));
    }

    #[Route('/unit-list', name: 'unitlist', methods: ['GET'])]
    public function unitListAction(GetQuantityValueUnitListHandler $getUnitList, GetQuantityValueUnitListPayload $payload): JsonResponse
    {
        $result = $getUnitList($payload);

        return $this->adminJson(ApiResponse::ok(['data' => $result->data, 'total' => $result->total]));
    }

    #[Route('/convert', name: 'convert', methods: ['GET'])]
    #[IsGranted(CorePermission::Objects->value)]
    public function convertAction(ConvertQuantityValueHandler $convert, ConvertQuantityValuePayload $payload): JsonResponse
    {
        $result = $convert($payload);

        return $this->adminJson(ApiResponse::ok(['value' => $result->value]));
    }

    #[Route('/convert-all', name: 'convertall', methods: ['GET'])]
    #[IsGranted(CorePermission::Objects->value)]
    public function convertAllAction(ConvertAllQuantityValuesHandler $convertAll, ConvertAllQuantityValuesPayload $payload): JsonResponse
    {
        $result = $convertAll($payload);

        return $this->adminJson(ApiResponse::ok([
            'value' => $result->value,
            'fromUnit' => $result->fromUnit,
            'values' => $result->values,
        ]));
    }
}

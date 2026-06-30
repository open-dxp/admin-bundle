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

use Exception;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Model\DataObject\Data\QuantityValue;
use OpenDxp\Model\DataObject\QuantityValue\Service as QuantityValueService;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use OpenDxp\Model\DataObject\QuantityValue\UnitConversionService;
use OpenDxp\Model\Translation;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * @internal
 */
#[Route('/quantity-value', name: 'opendxp_admin_dataobject_quantityvalue_')]
class QuantityValueController extends AdminAbstractController
{
    public function __construct(protected QuantityValueService $service)
    {
    }

    #[Route('/unit-import', name: 'unitimport', methods: ['POST', 'PUT'])]
    public function unitImportAction(Request $request): JsonResponse
    {
        /** @var UploadedFile $uploadFile */
        $uploadFile = $request->files->get('Filedata');

        $json = file_get_contents($uploadFile->getPathname());
        $success = $this->service->importDefinitionFromJson($json);
        $response = $this->adminJson(['success' => $success]);
        $response->headers->set('Content-Type', 'text/html');

        return $response;
    }

    #[Route('/unit-export', name: 'unitexport', methods: ['GET'])]
    public function unitExportAction(Request $request): Response
    {
        $result = $this->service->generateDefinitionJson();
        $response = new Response($result);
        $response->headers->set('Content-Type', 'application/json');
        $response->headers->set('Content-Disposition', 'attachment; filename="quantityvalue_unit_export.json"');

        return $response;
    }

    /**
     * @throws Exception
     */
    #[Route('/unit-proxy', name: 'unitproxyget', methods: ['GET'])]
    public function unitProxyGetAction(Request $request): JsonResponse
    {
        $this->checkPermission('quantityValueUnits');

        $list = new Unit\Listing();

        $order = ['ASC', 'ASC', 'ASC'];
        $orderKey = ['baseunit', 'factor', 'abbreviation'];

        $sortingSettings = \OpenDxp\Bundle\AdminBundle\Helper\QueryParams::extractSortingSettings($request->query->all());

        // Prepend user-requested sorting settings but keep the others to keep secondary order of quantity values in respective order
        if ($sortingSettings['orderKey']) {
            array_unshift($orderKey, $sortingSettings['orderKey']);
        }
        if ($sortingSettings['order']) {
            array_unshift($order, $sortingSettings['order']);
        }

        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $list->setLimit((int)$request->query->get('limit', '25'));
        $list->setOffset((int)$request->query->get('start', '0'));

        $condition = '1 = 1';
        if ($request->query->get('filter')) {
            $filterString = $request->query->get('filter');
            $filters = json_decode($filterString);
            $db = \OpenDxp\Db::get();
            foreach ($filters as $f) {
                if ($f->type === 'string') {
                    $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%');
                } elseif ($f->type === 'numeric') {
                    $operator = $this->getOperator($f->comparison);
                    $condition .= ' AND ' . $db->quoteIdentifier($f->property) . ' ' . $operator . ' ' . $db->quote($f->value);
                }
            }
            $list->setCondition($condition);
        }

        $units = [];
        foreach ($list->getUnits() as $u) {
            $units[] = $u->getObjectVars();
        }

        return $this->adminJson(['data' => $units, 'success' => true, 'total' => $list->getTotalCount()]);
    }

    /**
     * @throws Exception
     */
    #[Route('/unit-proxy', name: 'unitproxy', methods: ['POST', 'PUT'])]
    public function unitProxyAction(Request $request): JsonResponse
    {
        $this->checkPermission('quantityValueUnits');

        if ($request->request->has('data')) {
            if ($request->query->get('xaction') === 'destroy') {
                $data = json_decode($request->request->get('data'), true);
                $id = $data['id'];
                $unit = \OpenDxp\Model\DataObject\QuantityValue\Unit::getById($id);
                if (!empty($unit)) {
                    $unit->delete();

                    return $this->adminJson(['data' => [], 'success' => true]);
                }

                throw new Exception('Unit with id ' . $id . ' not found.');
            }
            if ($request->query->get('xaction') === 'update') {
                $data = json_decode($request->request->get('data'), true);
                $unit = Unit::getById($data['id']);
                if (!empty($unit)) {
                    if (($data['baseunit'] ?? null) == -1) {
                        $data['baseunit'] = null;
                    }
                    $unit->setValues($data);
                    $unit->save();

                    return $this->adminJson(['data' => $unit->getObjectVars(), 'success' => true]);
                }

                throw new Exception('Unit with id ' . $data['id'] . ' not found.');
            }
            if ($request->query->get('xaction') === 'create') {
                $data = json_decode($request->request->get('data'), true);
                if (isset($data['baseunit']) && $data['baseunit'] === -1) {
                    $data['baseunit'] = null;
                }
                $id = $data['id'];
                if (Unit::getById($id)) {
                    throw new Exception('unit with ID [' . $id . '] already exists');
                }
                if (mb_strlen($id) > 50) {
                    throw new Exception('The maximal character length for the unit ID is 50 characters, the provided ID has ' . mb_strlen($id) . ' characters.');
                }
                $unit = new Unit();
                $unit->setValues($data);
                $unit->save();

                return $this->adminJson(['data' => $unit->getObjectVars(), 'success' => true]);
            }
        }

        return $this->adminJson(['success' => false]);
    }

    private function getOperator(string $comparison): string
    {
        $mapper = [
            'lt' => '<',
            'gt' => '>',
            'eq' => '=',
        ];

        return $mapper[$comparison];
    }

    #[Route('/unit-list', name: 'unitlist', methods: ['GET'])]
    public function unitListAction(Request $request): JsonResponse
    {
        $list = new Unit\Listing();
        $list->setOrderKey(['baseunit', 'factor', 'abbreviation']);
        $list->setOrder(['ASC', 'ASC', 'ASC']);
        if ($request->query->get('filter')) {
            $array = explode(',', $request->query->get('filter'));
            $quotedArray = [];
            $db = \OpenDxp\Db::get();
            foreach ($array as $a) {
                $quotedArray[] = $db->quote($a);
            }
            $string = implode(',', $quotedArray);
            $list->setCondition('id IN (' . $string . ')');
        }

        $result = [];
        $units = $list->getUnits();
        foreach ($units as &$unit) {
            try {
                if ($unit->getAbbreviation()) {
                    $unit->setAbbreviation(Translation::getByKeyLocalized($unit->getAbbreviation(), Translation::DOMAIN_ADMIN,
                        true, true));
                }
                if ($unit->getLongname()) {
                    $unit->setLongname(Translation::getByKeyLocalized($unit->getLongname(), Translation::DOMAIN_ADMIN, true,
                        true));
                }
                $result[] = $unit->getObjectVars();
            } catch (Exception) {
                // nothing to do ...
            }
        }

        return $this->adminJson(['data' => $result, 'success' => true, 'total' => $list->getTotalCount()]);
    }

    #[Route('/convert', name: 'convert', methods: ['GET'])]
    public function convertAction(Request $request, UnitConversionService $conversionService): JsonResponse
    {
        $this->checkPermission('objects');

        $fromUnitId = $request->query->get('fromUnit');
        $toUnitId = $request->query->get('toUnit');

        $fromUnit = Unit::getById($fromUnitId);
        $toUnit = Unit::getById($toUnitId);
        if (!$fromUnit instanceof Unit || !$toUnit instanceof Unit) {
            return $this->adminJson(['success' => false]);
        }

        try {
            $convertedValue = $conversionService->convert(new QuantityValue($request->query->get('value'), $fromUnit), $toUnit);
        } catch (Exception) {
            return $this->adminJson(['success' => false]);
        }

        return $this->adminJson(['value' => $convertedValue->getValue(), 'success' => true]);
    }

    #[Route('/convert-all', name: 'convertall', methods: ['GET'])]
    public function convertAllAction(Request $request, UnitConversionService $conversionService): JsonResponse
    {
        $this->checkPermission('objects');

        $unitId = $request->query->get('unit');

        $fromUnit = Unit::getById($unitId);
        if (!$fromUnit instanceof Unit) {
            return $this->adminJson(['success' => false]);
        }
        $baseUnit = $fromUnit->getBaseunit() ?? $fromUnit;

        $units = new Unit\Listing();
        $units->setCondition('baseunit = '.$units->quote($baseUnit->getId()).' AND id != '.$units->quote($fromUnit->getId()));

        $convertedValues = [];
        foreach ($units->getUnits() as $targetUnit) {
            try {
                $convertedValue = $conversionService->convert(new QuantityValue($request->query->get('value'), $fromUnit), $targetUnit);

                $convertedValues[] = ['unit' => $targetUnit->getAbbreviation(), 'unitName' => $targetUnit->getLongname(), 'value' => round($convertedValue->getValue(), 4)];
            } catch (Exception $e) {
                return $this->adminJson(['success' => false, 'message' => $e->getMessage()]);
            }
        }

        return $this->adminJson([
            'value' => $request->query->get('value'),
            'fromUnit' => $fromUnit->getAbbreviation(),
            'values' => $convertedValues, 'success' => true]
        );
    }
}

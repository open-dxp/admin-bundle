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

namespace OpenDxp\Bundle\AdminBundle\GDPR\DataProvider;

use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Service\GridData;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\DataObject\Data\ElementMetadata;
use OpenDxp\Model\DataObject\Data\ObjectMetadata;
use OpenDxp\Model\Element;
use OpenDxp\Model\Element\ElementInterface;

/**
 * @internal
 */
class DataObjects extends Elements implements DataProviderInterface
{
    protected array $exportIds = [];

    public function __construct(protected array $config)
    {
    }

    public function getName(): string
    {
        return 'dataObjects';
    }

    public function getJsClassName(): string
    {
        return 'opendxp.settings.gdpr.dataproviders.dataObjects';
    }

    /**
     * Exports data of given object as json including all references that are configured to be included
     */
    public function doExportData(AbstractObject $object): array
    {
        $this->exportIds = [];

        $this->fillIds($object);

        $exportResult = [];

        if (!empty($this->exportIds['object'])) {
            foreach (array_keys($this->exportIds['object']) as $id) {
                $object = AbstractObject::getById((int)$id);
                if ($object instanceof AbstractObject) {
                    $exportResult[] = Exporter::exportObject($object);
                }
            }
        }
        if (!empty($this->exportIds['image'])) {
            foreach (array_keys($this->exportIds['image']) as $id) {
                $theAsset = Asset::getById((int) $id);
                if ($theAsset instanceof Asset) {
                    $exportResult[] = Exporter::exportAsset($theAsset);
                }
            }
        }

        return $exportResult;
    }

    protected function fillIds(ElementInterface $element): void
    {
        $this->exportIds[$element->getType()][$element->getId()] = true;

        if ($element instanceof Concrete) {
            $subFields = $this->config['classes'][$element->getClass()->getName()]['includedRelations'] ?? [];
            if ($subFields) {
                foreach ($subFields as $field) {
                    $getter = 'get' . ucfirst($field);

                    $subElements = $element->$getter();

                    if ($subElements) {
                        if (!is_array($subElements)) {
                            $subElements = [$subElements];
                        }

                        foreach ($subElements as $subElement) {
                            if ($subElement instanceof ObjectMetadata) {
                                $subElement = $subElement->getObject();
                            } elseif ($subElement instanceof ElementMetadata) {
                                $subElement = $subElement->getElement();
                            }

                            $this->fillIds($subElement);
                        }
                    }
                }
            }
        }
    }

    public function searchData(int $id, string $firstname, string $lastname, string $email, int $start, int $limit, ?string $sort = null): array
    {
        if (empty($id) && empty($firstname) && empty($lastname) && empty($email)) {
            return ['data' => [], 'success' => true, 'total' => 0];
        }

        $db = \OpenDxp\Db::get();
        $queryBuilder = $db->createQueryBuilder();
        $query = $queryBuilder
            ->select('id', 'type')
            ->from('objects')
            ->where('id = :id')
            ->setParameter('id', $id)
            ->setFirstResult($start)
            ->setMaxResults($limit);

        $sortingSettings = QueryParams::extractSortingSettings(['sort' => $sort]);
        if ($sortingSettings['orderKey']) {
            // we need a special mapping for classname as this is stored in subtype column
            $sortMapping = [
                'classname' => 'subtype',
            ];

            $sort = $sortingSettings['orderKey'];
            if (array_key_exists($sortingSettings['orderKey'], $sortMapping)) {
                $sort = $sortMapping[$sortingSettings['orderKey']];
            }

            $order = $sortingSettings['order'] ?? null;

            $query->orderBy($sort, $order);
        }

        $query = $query->executeQuery();

        $elements = [];
        if ($query->rowCount() > 0) {
            foreach ($query->fetchAllAssociative() as $hit) {
                $element = Element\Service::getElementById($hit['type'], (int) $hit['id']);
                if ($element instanceof Concrete) {
                    $data = GridData\DataObject::getData($element);
                    $data['__gdprIsDeletable'] = $this->config['classes'][$element->getClassName()]['allowDelete'] ?? false;
                    $elements[] = $data;
                }
            }
        }

        return ['data' => $elements, 'success' => true, 'total' => $query->rowCount()];
    }

    public function getSortPriority(): int
    {
        return 10;
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore;

use Doctrine\DBAL\ArrayParameterType;
use OpenDxp\Bundle\AdminBundle\Helper\QueryParams;
use OpenDxp\Bundle\AdminBundle\Service\Translation\AdminSearchTermResolver;
use OpenDxp\Db;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\Classificationstore;
use stdClass;

final class GetCollectionsHandler
{
    public function __construct(private readonly AdminSearchTermResolver $searchTermResolver) {}

    public function __invoke(GetCollectionsPayload $payload): GetCollectionsResult
    {
        $orderKey = 'name';
        $order = 'ASC';

        if ($payload->dir !== null) {
            $order = $payload->dir;
        }

        $sortingSettings = QueryParams::extractSortingSettings($payload->queryAll);
        if ($sortingSettings['orderKey'] && $sortingSettings['order']) {
            $orderKey = $sortingSettings['orderKey'];
            $order = $sortingSettings['order'];
        }

        if ($payload->overrideSort) {
            $orderKey = 'id';
            $order = 'DESC';
        }

        $storeIdFromDefinition = 0;
        $allowedCollectionIds = [];
        if ($payload->oid) {
            $object = DataObject\Concrete::getById($payload->oid);
            $class = $object->getClass();
            /** @var DataObject\ClassDefinition\Data\Classificationstore $fd */
            $fd = $class->getFieldDefinition($payload->fieldname);
            $allowedGroupIds = $fd->getAllowedGroupIds();

            if ($allowedGroupIds) {
                $db = Db::get();
                $relationList = $db->fetchAllAssociative(
                    'SELECT * FROM classificationstore_collectionrelations WHERE groupId IN (?)',
                    [$allowedGroupIds],
                    [ArrayParameterType::INTEGER]
                );

                foreach ($relationList as $item) {
                    $allowedCollectionIds[] = $item['colId'];
                }
            }

            $storeIdFromDefinition = $fd->getStoreId();
        }

        $list = new Classificationstore\CollectionConfig\Listing();

        $list->setLimit($payload->limit);
        $list->setOffset($payload->start);
        $list->setOrder($order);
        $list->setOrderKey($orderKey);

        $conditionParts = [];
        $db = Db::get();

        if ($payload->searchfilter) {
            $searchFilterConditions = [];

            $searchTerms = [$payload->searchfilter, ...$this->searchTermResolver->resolve($payload->searchfilter)];
            foreach ($searchTerms as $searchFilterTerm) {
                $searchFilterConditions[] = 'name LIKE ' . $db->quote('%' . $searchFilterTerm . '%') . ' OR description LIKE ' . $db->quote('%' . $searchFilterTerm . '%');
            }

            $conditionParts[] = '(' . implode(' OR ', $searchFilterConditions) . ')';
        }

        $storeId = $payload->storeId ?: $storeIdFromDefinition;

        $conditionParts[] = ' (storeId = ' . $db->quote($storeId) . ')';

        if ($payload->filter !== null) {
            $filters = json_decode($payload->filter);
            /** @var stdClass $f */
            foreach ($filters as $f) {
                if (!isset($f->value)) {
                    continue;
                }

                $conditionParts[] = $db->quoteIdentifier($f->property) . ' LIKE ' . $db->quote('%' . $f->value . '%');
            }
        }

        if ($allowedCollectionIds) {
            $conditionParts[] = ' id in (' . implode(',', $allowedCollectionIds) . ')';
        }

        $condition = implode(' AND ', $conditionParts);

        $list->setCondition($condition);

        $list->load();
        $configList = $list->getList();

        $data = [];
        foreach ($configList as $config) {
            $name = $config->getName();
            if (!$name) {
                $name = 'EMPTY';
            }
            $item = [
                'storeId' => $config->getStoreId(),
                'id' => $config->getId(),
                'name' => $name,
                'description' => $config->getDescription(),
            ];
            if ($config->getCreationDate()) {
                $item['creationDate'] = $config->getCreationDate();
            }

            if ($config->getModificationDate()) {
                $item['modificationDate'] = $config->getModificationDate();
            }

            $data[] = $item;
        }

        return new GetCollectionsResult(data: $data, total: $list->getTotalCount());
    }

}

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

use OpenDxp\Db;
use OpenDxp\Helper\ArrayHelper;

final class GetPageHandler
{
    public function __invoke(
        ?string $table,
        int $id,
        int $storeId,
        int $pageSize,
        ?string $sortKey,
        ?string $sortDir,
    ): GetPageResult {
        $tableSuffix = $table;
        if (!ArrayHelper::inArrayCaseInsensitive($tableSuffix, ['keys', 'groups'])) {
            $tableSuffix = 'keys';
        }

        $table = 'classificationstore_' . $tableSuffix;
        $db = Db::get();

        if ($sortKey) {
        } else {
            $sortKey = 'name';
            $sortDir = 'ASC';
        }

        if (!ArrayHelper::inArrayCaseInsensitive($sortDir, ['DESC', 'ASC'])) {
            $sortDir = 'DESC';
        }

        if (!ArrayHelper::inArrayCaseInsensitive($sortKey, ['name', 'title', 'description', 'id', 'type', 'creationDate', 'modificationDate', 'enabled', 'parentId', 'storeId'])) {
            $sortKey = 'name';
        }

        $sorter = ' order by `' . $sortKey . '` ' . $sortDir;

        if ($table === 'keys') {
            $query = '
                select *, (item.pos - 1)/ ' . $pageSize . ' + 1  as page from (
                    select * from (
                        select @rownum := @rownum + 1 as pos,  id, name, `type`
                        from `' . $table . '`
                        where enabled = 1 and storeId = ' . $storeId . $sorter . '
                      ) all_rows) item where id = ' . $id . ';';
        } else {
            $query = '
            select *, (item.pos - 1)/ ' . $pageSize . ' + 1  as page from (
                select * from (
                    select @rownum := @rownum + 1 as pos,  id, name
                    from `' . $table . '`
                    where storeId = ' . $storeId . $sorter . '
                  ) all_rows) item where id = ' . $id . ';';
        }

        $db->executeStatement('SET @rownum = 0');
        $result = $db->fetchAllAssociative($query);

        $page = (int) $result[0]['page'];

        return new GetPageResult(page: $page);
    }
}

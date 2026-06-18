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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetClassDefinitionForColumnConfig;

use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetClassDefinitionForColumnConfigHandler
{
    public function __invoke(GetClassDefinitionForColumnConfigPayload $payload): GetClassDefinitionForColumnConfigResult
    {
        $class = DataObject\ClassDefinition::getById($payload->id);
        if (!$class) {
            throw new NotFoundHttpException('Class not found');
        }

        $filteredDefinitions = DataObject\Service::getCustomLayoutDefinitionForGridColumnConfig($class, $payload->objectId);

        /** @var DataObject\ClassDefinition\Layout $layoutDefinitions */
        $layoutDefinitions = $filteredDefinitions['layoutDefinition'] ?? false;
        $filteredFieldDefinition = $filteredDefinitions['fieldDefinition'] ?? false;

        $class->setFieldDefinitions([]);

        $result = [];

        DataObject\Service::enrichLayoutDefinition($layoutDefinitions);

        $result['objectColumns']['children'] = $layoutDefinitions->getChildren();
        $result['objectColumns']['nodeLabel'] = 'object_columns';
        $result['objectColumns']['nodeType'] = 'object';

        $systemColumnNames = DataObject\Concrete::SYSTEM_COLUMN_NAMES;
        $systemColumns = [];
        foreach ($systemColumnNames as $systemColumn) {
            $systemColumns[] = ['title' => $systemColumn, 'name' => $systemColumn, 'datatype' => 'data', 'fieldtype' => 'system'];
        }
        $result['systemColumns']['nodeLabel'] = 'system_columns';
        $result['systemColumns']['nodeType'] = 'system';
        $result['systemColumns']['children'] = $systemColumns;

        $list = new DataObject\Objectbrick\Definition\Listing();
        $list = $list->load();

        foreach ($list as $brickDefinition) {
            $classDefs = $brickDefinition->getClassDefinitions();
            if (!empty($classDefs)) {
                foreach ($classDefs as $classDef) {
                    if ($classDef['classname'] == $class->getName()) {
                        $fieldName = $classDef['fieldname'];
                        if (isset($filteredFieldDefinition[$fieldName]) && !$filteredFieldDefinition[$fieldName]) {
                            continue;
                        }

                        $key = $brickDefinition->getKey();

                        $brickLayoutDefinitions = $brickDefinition->getLayoutDefinitions();
                        $context = [
                            'containerType' => 'objectbrick',
                            'containerKey' => $key,
                            'outerFieldname' => $fieldName,
                        ];
                        DataObject\Service::enrichLayoutDefinition($brickLayoutDefinitions, null, $context);

                        $result[$key]['nodeLabel'] = $key;
                        $result[$key]['brickField'] = $fieldName;
                        $result[$key]['nodeType'] = 'objectbricks';
                        $result[$key]['children'] = $brickLayoutDefinitions->getChildren();

                        break;
                    }
                }
            }
        }

        return new GetClassDefinitionForColumnConfigResult(config: $result);
    }
}

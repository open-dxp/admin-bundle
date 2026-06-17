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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper;

use OpenDxp\Bundle\AdminBundle\Service\Grid\DataObjectGridColumnConfigResolver;
use OpenDxp\Model\DataObject;

final class GetAvailableVisibleFieldsHandler
{
    public function __invoke(GetAvailableVisibleFieldsPayload $payload): GetAvailableVisibleFieldsResult
    {
        $classes = $payload->classes;
        if ($classes === null) {
            return new GetAvailableVisibleFieldsResult([]);
        }

        $classNameList = explode(',', $classes);
        $classList = [];
        foreach ($classNameList as $className) {
            $class = DataObject\ClassDefinition::getByName($className);
            if ($class) {
                $classList[] = $class;
            }
        }

        if (!$classList) {
            return new GetAvailableVisibleFieldsResult([]);
        }

        $availableFields = [];
        foreach (DataObjectGridColumnConfigResolver::SYSTEM_COLUMNS as $field) {
            $availableFields[] = [
                'key' => $field,
                'value' => $field,
            ];
        }

        $commonFields = [];
        $firstOne = true;

        foreach ($classNameList as $className) {
            $class = DataObject\ClassDefinition::getByName($className);
            if (!$class) {
                continue;
            }

            $fds = $class->getFieldDefinitions();
            $additionalFieldNames = array_keys($fds);

            $localizedFields = $class->getFieldDefinition('localizedfields');
            if ($localizedFields instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                $lfNames = array_keys($localizedFields->getFieldDefinitions());
                $additionalFieldNames = [...$additionalFieldNames, ...$lfNames];
            }

            foreach ($commonFields as $commonFieldKey => $commonFieldDefinition) {
                if (!in_array($commonFieldKey, $additionalFieldNames)) {
                    unset($commonFields[$commonFieldKey]);
                }
            }

            $this->processAvailableFieldDefinitions($fds, $firstOne, $commonFields);
            $firstOne = false;
        }

        foreach (array_keys($commonFields) as $field) {
            $availableFields[] = [
                'key' => $field,
                'value' => $field,
            ];
        }

        return new GetAvailableVisibleFieldsResult($availableFields);
    }

    /**
     * @param DataObject\ClassDefinition\Data[] $fds
     * @param DataObject\ClassDefinition\Data[] $commonFields
     */
    private function processAvailableFieldDefinitions(array $fds, bool &$firstOne, array &$commonFields): void
    {
        foreach ($fds as $fd) {
            if ($fd instanceof DataObject\ClassDefinition\Data\Fieldcollections) {
                continue;
            }
            if ($fd instanceof DataObject\ClassDefinition\Data\Objectbricks) {
                continue;
            }
            if ($fd instanceof DataObject\ClassDefinition\Data\Block) {
                continue;
            }
            if ($fd instanceof DataObject\ClassDefinition\Data\Localizedfields) {
                $lfDefs = $fd->getFieldDefinitions();
                $this->processAvailableFieldDefinitions($lfDefs, $firstOne, $commonFields);
            } elseif ($firstOne || (isset($commonFields[$fd->getName()]) && $commonFields[$fd->getName()]->getFieldtype() == $fd->getFieldtype())) {
                $commonFields[$fd->getName()] = $fd;
            }
        }
    }
}

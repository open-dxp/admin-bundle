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

namespace OpenDxp\Bundle\AdminBundle\Enricher\DataObject;

use OpenDxp\Model\DataObject\Concrete;

final class CustomLayoutEnricher
{
    public function enrich(Concrete $object, array &$data): void
    {
        if (!($data['layout'] ?? false)) {
            return;
        }

        $layoutArray = json_decode(json_encode($data['layout']), true);
        $classFieldDefinitions = json_decode(json_encode($object->getClass()->getFieldDefinitions()), true);

        if (is_array($layoutArray)) {
            $this->injectValuesForCustomLayout($layoutArray, $classFieldDefinitions);
        }

        $data['layout'] = $layoutArray;
    }

    private function injectValuesForCustomLayout(array &$layout, array $classFieldDefinitions): void
    {
        foreach ($layout['children'] as &$child) {
            if ($child['datatype'] === 'layout') {
                $this->injectValuesForCustomLayout($child, $classFieldDefinitions);
            } else {
                foreach ($classFieldDefinitions[$child['name']] as $key => $value) {
                    if (array_key_exists($key, $child) && ($child[$key] === null || $child[$key] === '' || (is_array($child[$key]) && empty($child[$key])))) {
                        $child[$key] = $value;
                    }
                }
            }
        }
    }
}

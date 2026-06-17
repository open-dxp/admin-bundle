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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\FieldCollection\GetFieldCollectionUsages\GetFieldCollectionUsagesPayload;
use OpenDxp\Model\DataObject\ClassDefinition\Data\Fieldcollections;
use OpenDxp\Model\DataObject\ClassDefinition\Listing;

final class GetFieldCollectionUsagesHandler
{
    public function __invoke(GetFieldCollectionUsagesPayload $payload): array
    {
        $result = [];

        foreach ((new Listing())->load() as $class) {
            foreach ($class->getFieldDefinitions() as $fieldDef) {
                if ($fieldDef instanceof Fieldcollections && in_array($payload->key, $fieldDef->getAllowedTypes())) {
                    $result[] = [
                        'class' => $class->getName(),
                        'field' => $fieldDef->getName(),
                    ];
                }
            }
        }

        return $result;
    }
}

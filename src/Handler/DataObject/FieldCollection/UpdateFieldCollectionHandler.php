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

use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UpdateFieldCollectionHandler
{
    public function __invoke(
        string $key,
        string $title,
        string $group,
        bool $isAdd,
        ?array $values,
        ?array $configuration,
    ): DataObject\Fieldcollection\Definition {
        if ($isAdd) {
            $list = new DataObject\Fieldcollection\Definition\Listing();
            foreach ($list->loadNames() as $fcName) {
                if (strtolower($key) === strtolower($fcName)) {
                    throw new BadRequestHttpException('FieldCollection with the same name already exists (lower/upper cases may be different)');
                }
            }
        }

        $fcDef = new DataObject\Fieldcollection\Definition();
        $fcDef->setKey($key);
        $fcDef->setTitle($title);
        $fcDef->setGroup($group);

        if ($values !== null) {
            $fcDef->setParentClass($values['parentClass']);
            $fcDef->setImplementsInterfaces($values['implementsInterfaces']);
        }

        if ($configuration !== null) {
            $configuration['datatype'] = 'layout';
            $configuration['fieldtype'] = 'panel';

            $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
            $fcDef->setLayoutDefinitions($layout);
        }

        $fcDef->save();

        return $fcDef;
    }
}

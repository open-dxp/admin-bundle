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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject;

use OpenDxp\Bundle\AdminBundle\Service\DataObject\DataObjectPayloadMapper;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\DataObject\ClassDefinition\Helper\OptionsProviderResolver;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetSelectOptionsHandler
{
    public function __construct(private readonly DataObjectPayloadMapper $mapper) {}

    public function __invoke(int $objectId, ?array $changedData, array $fieldDefinitionConfig, array $context): array
    {
        $object = DataObject\Concrete::getById($objectId);
        if (!$object instanceof DataObject\Concrete) {
            throw new NotFoundHttpException('Object not found.');
        }

        if ($changedData !== null) {
            $this->mapper->applyChanges($object, $changedData);
        }

        /** @var DataObject\ClassDefinition\Data\Select|DataObject\ClassDefinition\Data\Multiselect $fieldDefinition */
        $fieldDefinition = DataObject\Classificationstore\Service::getFieldDefinitionFromJson(
            $fieldDefinitionConfig,
            $fieldDefinitionConfig['fieldtype']
        );

        $optionsProvider = OptionsProviderResolver::resolveProvider(
            $fieldDefinition->getOptionsProviderClass(),
            $fieldDefinition instanceof DataObject\ClassDefinition\Data\Multiselect
                ? OptionsProviderResolver::MODE_MULTISELECT
                : OptionsProviderResolver::MODE_SELECT
        );

        return $optionsProvider->getOptions(
            [
                'object' => $object,
                'fieldname' => $fieldDefinition->getName(),
                'class' => $object->getClass(),
                'context' => $context,
            ],
            $fieldDefinition
        );
    }
}

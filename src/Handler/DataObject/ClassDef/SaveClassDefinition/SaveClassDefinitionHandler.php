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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveClassDefinition;

use OpenDxp\Model\DataObject;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class SaveClassDefinitionHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(SaveClassDefinitionPayload $payload): SaveClassDefinitionResult
    {
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $class = DataObject\ClassDefinition::getById($payload->id);
        if (!$class) {
            throw new NotFoundHttpException('Class not found');
        }

        $values = $payload->values;
        $configuration = $payload->configuration;

        if ($class->getModificationDate() != $values['modificationDate']) {
            throw new BadRequestHttpException('The class was modified during editing, please reload the class and make your changes again');
        }

        if ($values['name'] != $class->getName()) {
            $classByName = DataObject\ClassDefinition::getByName($values['name']);
            if ($classByName && $classByName->getId() !== $class->getId()) {
                throw new BadRequestHttpException('Class name already exists');
            }

            $values['name'] = $this->correctClassname($values['name']);
            $class->rename($values['name']);
        }

        if ($values['compositeIndices']) {
            foreach ($values['compositeIndices'] as $index => $compositeIndex) {
                if ($compositeIndex['index_key'] !== ($sanitizedKey = preg_replace('/[^a-za-z0-9_\-+]/', '', $compositeIndex['index_key']))) {
                    $values['compositeIndices'][$index]['index_key'] = $sanitizedKey;
                }
            }
        }

        unset($values['creationDate'], $values['userOwner'], $values['layoutDefinitions'], $values['fieldDefinitions']);

        $configuration['datatype'] = 'layout';
        $configuration['fieldtype'] = 'panel';
        $configuration['name'] = 'opendxp_root';

        $class->setValues($values);

        $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
        $class->setLayoutDefinitions($layout);
        $class->setUserModification($userId);
        $class->setModificationDate(time());

        $propertyVisibility = [];
        foreach ($values as $key => $value) {
            if (false !== stripos($key, 'propertyVisibility')) {
                if (preg_match("/\.grid\./i", $key)) {
                    $propertyVisibility['grid'][preg_replace("/propertyVisibility\.grid\./i", '', $key)] = (bool) $value;
                } elseif (preg_match("/\.search\./i", $key)) {
                    $propertyVisibility['search'][preg_replace("/propertyVisibility\.search\./i", '', $key)] = (bool) $value;
                }
            }
        }
        if (!empty($propertyVisibility)) {
            $class->setPropertyVisibility($propertyVisibility);
        }

        $class->save();

        $class->setFieldDefinitions([]);

        return new SaveClassDefinitionResult(class: $class);
    }

    private function correctClassname(string $name): string
    {
        $name = preg_replace('/[^a-zA-Z0-9_]+/', '', $name);

        return preg_replace('/^\d+/', '', $name);
    }
}

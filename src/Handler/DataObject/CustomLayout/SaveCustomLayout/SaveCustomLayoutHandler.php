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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SaveCustomLayout;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\SaveCustomLayout\SaveCustomLayoutPayload;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Exception\ConfigWriteException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class SaveCustomLayoutHandler
{
    public function __invoke(SaveCustomLayoutPayload $payload): SaveCustomLayoutResult
    {
        $id = $payload->id;
        $configuration = $payload->configuration;
        $values = $payload->values;

        $customLayout = DataObject\ClassDefinition\CustomLayout::getById($id)
            ?? throw new NotFoundHttpException();

        $modificationDate = (int) $values['modificationDate'];
        if ($modificationDate < $customLayout->getModificationDate()) {
            throw new AdminOperationFailedException('custom_layout_changed');
        }

        $configuration['datatype'] = 'layout';
        $configuration['fieldtype'] = 'panel';
        $configuration['name'] = 'opendxp_root';

        $layout = DataObject\ClassDefinition\Service::generateLayoutTreeFromArray($configuration, true);
        $customLayout->setLayoutDefinitions($layout);
        $customLayout->setName($values['name']);
        $customLayout->setDescription($values['description']);
        $customLayout->setDefault($values['default']);

        if (!$customLayout->isWriteable()) {
            throw new ConfigWriteException();
        }

        $customLayout->save();

        return new SaveCustomLayoutResult(id: $customLayout->getId(), data: $customLayout->getObjectVars());
    }
}

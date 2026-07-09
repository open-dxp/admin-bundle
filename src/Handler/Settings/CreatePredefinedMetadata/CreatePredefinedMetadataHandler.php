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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\CreatePredefinedMetadata;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Settings\PredefinedMetadataPayload;
use OpenDxp\Model\Exception\ConfigWriteException;
use OpenDxp\Model\Metadata;

final class CreatePredefinedMetadataHandler
{
    public function __invoke(PredefinedMetadataPayload $payload): CreatePredefinedMetadataResult
    {
        $data = $payload->data;
        unset($data['id']);

        if (!(new Metadata\Predefined())->isWriteable()) {
            throw new ConfigWriteException();
        }

        $metadata = Metadata\Predefined::create();
        $metadata->setValues($data);

        $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage(
            $metadata->getName(),
            $metadata->getLanguage(),
            $metadata->getTargetSubtype()
        );

        if ($existingItem) {
            throw new AdminOperationFailedException('rule_violation');
        }

        $metadata->save();

        $responseData = $metadata->getObjectVars();
        $responseData['writeable'] = $metadata->isWriteable();

        return new CreatePredefinedMetadataResult(data: $responseData);
    }
}

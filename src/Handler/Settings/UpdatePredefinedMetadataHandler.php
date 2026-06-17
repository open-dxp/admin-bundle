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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Model\Exception\ConfigWriteException;
use OpenDxp\Model\Metadata;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class UpdatePredefinedMetadataHandler
{
    public function __invoke(PredefinedMetadataPayload $payload): UpdatePredefinedMetadataResult
    {
        $data = $payload->data;
        $metadata = Metadata\Predefined::getById($data['id']);

        if (!$metadata->isWriteable()) {
            throw new ConfigWriteException();
        }

        $metadata->setValues($data);

        $existingItem = Metadata\Predefined\Listing::getByKeyAndLanguage(
            $metadata->getName(),
            $metadata->getLanguage(),
            $metadata->getTargetSubtype()
        );

        if ($existingItem && $existingItem->getId() !== $metadata->getId()) {
            throw new BadRequestHttpException('predefined_metadata_definitions_error_name_exists_msg');
        }

        $metadata->minimize();
        $metadata->save();
        $metadata->expand();

        $responseData = $metadata->getObjectVars();
        $responseData['writeable'] = $metadata->isWriteable();

        return new UpdatePredefinedMetadataResult(data: $responseData);
    }
}

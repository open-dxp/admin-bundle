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

namespace OpenDxp\Bundle\AdminBundle\Normalizer\DataObject;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectFolderHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Normalizer\Element\AbstractUserNamesNormalizer;
use OpenDxp\Model\DataObject\AbstractObject;
use OpenDxp\Model\Element\ElementInterface;

final class UserNamesNormalizer extends AbstractUserNamesNormalizer
{
    public function supports(ElementInterface $element, string $handlerClass): bool
    {
        return $element instanceof AbstractObject
            && in_array($handlerClass, [GetDataObjectHandler::class, GetDataObjectFolderHandler::class], true);
    }

    public function normalize(ElementInterface $element, array &$data, array $context = []): void
    {
        $ownerName = $this->resolveUserName($element->getUserOwner());
        $modificationName = $element->getUserOwner() === $element->getUserModification()
            ? $ownerName
            : $this->resolveUserName($element->getUserModification());

        $data['general']['userOwnerUsername'] = $ownerName['userName'];
        $data['general']['userOwnerFullname'] = $ownerName['fullName'];
        $data['general']['userModificationUsername'] = $modificationName['userName'];
        $data['general']['userModificationFullname'] = $modificationName['fullName'];
    }
}

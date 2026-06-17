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

namespace OpenDxp\Bundle\AdminBundle\Normalizer\Element;

use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Email\GetEmailData\GetEmailDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\GetFolderData\GetFolderDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\GetDocumentData\GetDocumentDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\GetHardlinkData\GetHardlinkDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Link\GetLinkData\GetLinkDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\GetPageData\GetPageDataHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Snippet\GetSnippetData\GetSnippetDataHandler;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;

final class UserNamesNormalizer extends AbstractUserNamesNormalizer
{
    public function supports(ElementInterface $element, string $handlerClass): bool
    {
        return ($element instanceof Document && in_array($handlerClass, [
                GetDocumentDataHandler::class,
                GetEmailDataHandler::class,
                GetFolderDataHandler::class,
                GetHardlinkDataHandler::class,
                GetLinkDataHandler::class,
                GetPageDataHandler::class,
                GetSnippetDataHandler::class,
            ], true))
            || ($element instanceof Asset && $handlerClass === GetAssetDataHandler::class);
    }

    public function normalize(ElementInterface $element, array &$data, array $context = []): void
    {
        $ownerName = $this->resolveUserName($element->getUserOwner());
        $modificationName = $element->getUserOwner() === $element->getUserModification()
            ? $ownerName
            : $this->resolveUserName($element->getUserModification());

        $data['userOwnerUsername'] = $ownerName['userName'];
        $data['userOwnerFullname'] = $ownerName['fullName'];
        $data['userModificationUsername'] = $modificationName['userName'];
        $data['userModificationFullname'] = $modificationName['fullName'];
    }
}

<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\SaveToSession;

use OpenDxp\Bundle\AdminBundle\Handler\Document\Email\SaveEmail\SaveEmailPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\SaveFolder\SaveFolderPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\SaveHardlink\SaveHardlinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Link\SaveLink\SaveLinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\PagePayload;
use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveToSessionPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $id,
        public readonly ?PagePayload $page,
        public readonly ?SaveEmailPayload $email,
        public readonly ?SaveLinkPayload $link,
        public readonly ?SaveHardlinkPayload $hardlink,
        public readonly ?SaveFolderPayload $folder,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $id = $request->request->getInt('id');
        if (!$id) {
            return new static(id: 0, page: null, email: null, link: null, hardlink: null, folder: null);
        }

        return new static(
            id: $id,
            page: PagePayload::fromRequest($request),
            email: SaveEmailPayload::fromRequest($request),
            link: SaveLinkPayload::fromRequest($request),
            hardlink: SaveHardlinkPayload::fromRequest($request),
            folder: SaveFolderPayload::fromRequest($request),
        );
    }
}

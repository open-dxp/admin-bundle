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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Media;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Media\GetAssetText\GetAssetTextPayload;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

final class GetAssetTextHandler
{
    public function __invoke(GetAssetTextPayload $payload): AssetTextResult
    {
        $id = $payload->id;
        $page = $payload->page;
        $asset = Asset::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$asset->isAllowed('view')) {
            throw new AccessDeniedHttpException('not allowed to view');
        }

        $text = null;
        if ($asset instanceof Asset\Document) {
            $text = $asset->getText($page);
        }

        return new AssetTextResult($text);
    }
}

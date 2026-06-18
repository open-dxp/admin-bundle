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

namespace OpenDxp\Bundle\AdminBundle\Handler\GDPR\Asset\ExportAsset;

use OpenDxp\Bundle\AdminBundle\GDPR\DataProvider\Assets;
use OpenDxp\Bundle\AdminBundle\Payload\Common\IdQueryPayload;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ExportAssetHandler
{
    public function __construct(
        private readonly Assets $assets,
    ) {}

    public function __invoke(IdQueryPayload $payload): ExportAssetResult
    {
        $asset = Asset::getById($payload->id);
        if (!$asset) {
            throw new NotFoundHttpException('Asset not found');
        }
        if (!$asset->isAllowed('view')) {
            throw new AccessDeniedHttpException('Export denied');
        }

        return new ExportAssetResult($this->assets->doExportData($asset));
    }
}

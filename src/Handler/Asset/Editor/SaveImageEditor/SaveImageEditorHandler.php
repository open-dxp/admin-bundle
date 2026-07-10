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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\SaveImageEditor;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\AssetResult;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Editor\SaveImageEditor\SaveImageEditorPayload;
use OpenDxp\Model\Asset;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;

final class SaveImageEditorHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function __invoke(SaveImageEditorPayload $payload): AssetResult
    {
        $id = $payload->id;
        $dataUri = $payload->dataUri;
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $asset = Asset::getById($id) ?? throw new AssetNotFoundException($id);

        if (!$asset->isAllowed('publish')) {
            throw new AccessDeniedHttpException('Not allowed to publish asset');
        }

        $commaPosition = strpos($dataUri, ',');
        $data = $commaPosition !== false ? substr($dataUri, $commaPosition) : $dataUri;
        $data = base64_decode($data);
        $asset->setData($data);
        $asset->setUserModification($userId);
        $asset->save();

        return new AssetResult($asset);
    }
}

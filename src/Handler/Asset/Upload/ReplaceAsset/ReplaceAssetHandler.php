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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAsset;

use OpenDxp\Bundle\AdminBundle\Exception\Asset\AssetNotFoundException;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\ReplaceAsset\ReplaceAssetPayload;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Mime\MimeTypes;
use Symfony\Contracts\Translation\TranslatorInterface;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;

final class ReplaceAssetHandler
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,private readonly TranslatorInterface $translator) {}

    public function __invoke(ReplaceAssetPayload $payload): ReplaceAssetResult
    {
        $id = $payload->id;
        $filePath = $payload->filePath;
        $originalFilename = $payload->originalFilename;
        $userId = $this->userContext->getAdminUser()?->getId() ?? 0;
        $asset = Asset::getById($id) ?? throw new AssetNotFoundException($id);

        $newFilename = Element\Service::getValidKey($originalFilename, 'asset');
        $mimetype = MimeTypes::getDefault()->guessMimeType($filePath);
        $newType = Asset::getTypeFromMimeMapping($mimetype, $newFilename);

        if ($newType !== $asset->getType()) {
            throw new AdminOperationFailedException(sprintf(
                $this->translator->trans('asset_type_change_not_allowed', [], 'admin'),
                $newType,
                $asset->getType(),
            ));
        }

        $stream = fopen($filePath, 'rb+') ?: null;
        $asset->setStream($stream);
        $asset->setCustomSetting('thumbnails', null);

        if (method_exists($asset, 'getEmbeddedMetaData')) {
            $asset->getEmbeddedMetaData(true);
        }

        $asset->setUserModification($userId);

        $newFileExt = pathinfo($newFilename, PATHINFO_EXTENSION);
        $currentFileExt = pathinfo($asset->getFilename(), PATHINFO_EXTENSION);
        if ($newFileExt !== $currentFileExt) {
            $newFilename = preg_replace('/\.' . $currentFileExt . '$/i', '.' . $newFileExt, $asset->getFilename());
            $newFilename = Element\Service::getSafeCopyName($newFilename, $asset->getParent());
            $asset->setFilename($newFilename);
        }

        if (!$asset->isAllowed('publish')) {
            throw new AccessDeniedHttpException('missing permission');
        }

        $asset->save();

        return new ReplaceAssetResult(id: $asset->getId(), path: $asset->getRealFullPath());
    }
}

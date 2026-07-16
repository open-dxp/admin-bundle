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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetData;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\AdminStyleEnricher;
use OpenDxp\Bundle\AdminBundle\Enricher\Element\UserNamesEnricher;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Asset\GetAssetData\GetAssetDataPayload;
use OpenDxp\Bundle\AdminBundle\Service\Element\EditLockService;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Element;
use OpenDxp\Model\Metadata;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;

final class GetAssetDataHandler
{
    final const string PDF_MIMETYPE = 'application/pdf';

    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly AdminStyleEnricher $adminStyleEnricher,
        private readonly UserNamesEnricher $userNamesEnricher,
        private readonly EditLockService $editLockService,
    ) {}

    public function __invoke(GetAssetDataPayload $payload): GetAssetDataResult
    {
        $id = $payload->id;
        $requestSchemeAndHost = $payload->requestSchemeAndHost;
        $asset = Asset::getById($id);
        if (!$asset instanceof Asset) {
            throw new AdminOperationFailedException(sprintf('Asset with id %d not found', $id));
        }

        $adminUser = $this->userContext->getAdminUser();
        if (!$asset->isAllowed('view')) {
            throw new AccessDeniedHttpException();
        }

        if (!$asset instanceof Asset\Folder && ($asset->isAllowed('publish') || $asset->isAllowed('delete'))) {
            $this->editLockService->checkAndAcquire($asset->getId(), 'asset', AdminEvents::ASSET_GET_IS_LOCKED, $asset);
        }

        $asset = clone $asset;
        $asset->setParent(null);
        $asset->setStream(null);

        $data = $asset->getObjectVars();
        $data['locked'] = $asset->isLocked();

        if ($asset instanceof Asset\Text) {
            if ($asset->getFileSize() < 2000000) {
                $data['data'] = \ForceUTF8\Encoding::toUTF8($asset->getData());
            } else {
                $data['data'] = false;
            }
        } elseif ($asset instanceof Asset\Document) {
            $data['pdfPreviewAvailable'] = (bool) $this->getDocumentPreviewPdf($asset);
        } elseif ($asset instanceof Asset\Video) {
            $videoInfo = [];

            if (\OpenDxp\Video::isAvailable()) {
                $config = Asset\Video\Thumbnail\Config::getPreviewConfig();
                $thumbnail = $asset->getThumbnail($config, ['mp4']);
                if ($thumbnail && $thumbnail['status'] === 'finished') {
                    $videoInfo['previewUrl'] = $thumbnail['formats']['mp4'];
                    $videoInfo['width'] = $asset->getWidth();
                    $videoInfo['height'] = $asset->getHeight();
                    $metaData = $asset->getSphericalMetaData();
                    if (isset($metaData['ProjectionType']) && strtolower($metaData['ProjectionType']) === 'equirectangular') {
                        $videoInfo['isVrVideo'] = true;
                    }
                }
            }

            $data['videoInfo'] = $videoInfo;
        } elseif ($asset instanceof Asset\Image) {
            $imageInfo = [];

            $previewUrl = $this->urlGenerator->generate('opendxp_admin_asset_getimagethumbnail', [
                'id' => $asset->getId(),
                'treepreview' => true,
                '_dc' => time(),
            ]);

            if ($asset->isAnimated()) {
                $previewUrl = $this->urlGenerator->generate('opendxp_admin_asset_getasset', [
                    'id' => $asset->getId(),
                    '_dc' => time(),
                ]);
            }

            $imageInfo['previewUrl'] = $previewUrl;

            if ($asset->getWidth() && $asset->getHeight()) {
                $imageInfo['dimensions'] = [
                    'width' => $asset->getWidth(),
                    'height' => $asset->getHeight(),
                ];
            }

            $imageInfo['exiftoolAvailable'] = (bool) \OpenDxp\Tool\Console::getExecutable('exiftool');

            if (!$asset->getEmbeddedMetaData(false)) {
                $asset->getEmbeddedMetaData(true, false);
            }

            $data['imageInfo'] = $imageInfo;
        }

        $predefinedMetaData = Metadata\Predefined\Listing::getByTargetType('asset', [$asset->getType()]);
        $predefinedMetaDataGroups = [];
        foreach ($predefinedMetaData as $item) {
            if ($item->getGroup()) {
                $predefinedMetaDataGroups[$item->getGroup()] = true;
            }
        }
        $data['predefinedMetaDataGroups'] = array_keys($predefinedMetaDataGroups);
        $data['properties'] = Element\Service::minimizePropertiesForEditmode($asset->getProperties());
        $data['metadata'] = Asset\Service::expandMetadataForEditmode($asset->getMetadata());
        $data['versionDate'] = $asset->getModificationDate();
        $data['filesizeFormatted'] = $asset->getFileSize(true);
        $data['filesize'] = $asset->getFileSize();
        $data['fileExtension'] = pathinfo($asset->getFilename(), PATHINFO_EXTENSION);
        $data['idPath'] = Element\Service::getIdPath($asset);
        $data['userPermissions'] = $asset->getUserPermissions($adminUser);

        $frontendPath = $asset->getFrontendFullPath();
        $data['url'] = preg_match('/^http(s)?:\\/\\/.+/', $frontendPath)
            ? $frontendPath
            : $requestSchemeAndHost . $frontendPath;

        $data['scheduledTasks'] = array_map(
            static fn (Task $task) => $task->getObjectVars(),
            $asset->getScheduledTasks()
        );

        $this->userNamesEnricher->enrich($asset, $data);
        $this->adminStyleEnricher->forEditor($asset, $data);

        $data['php'] = [
            'classes' => [$asset::class, ...array_values(class_parents($asset))],
            'interfaces' => array_values(class_implements($asset)),
        ];

        $event = new GenericEvent(null, [
            'data' => $data,
            'asset' => $asset,
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::ASSET_GET_PRE_SEND_DATA);
        $eventData = $event->getArgument('data');
        $data = is_array($eventData) ? $eventData : $data;

        return new GetAssetDataResult($data);
    }

    private function getDocumentPreviewPdf(Asset\Document $asset): mixed
    {
        $stream = null;

        if ($asset->getMimeType() == self::PDF_MIMETYPE) {
            $stream = $asset->getStream();
        }

        if (
            !$stream &&
            $asset->getPageCount() &&
            \OpenDxp\Document::isAvailable() &&
            \OpenDxp\Document::isFileTypeSupported($asset->getFilename())
        ) {
            try {
                $stream = \OpenDxp\Document::getInstance()->getPdf($asset);
            } catch (Exception) {
                // nothing to do
            }
        }

        return $stream;
    }

}


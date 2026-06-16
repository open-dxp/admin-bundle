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

namespace OpenDxp\Bundle\AdminBundle\Service\Asset;

use Exception;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Payload\Asset\AssetPayload;
use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Logger;
use OpenDxp\Model;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Schedule\Task;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class AssetPayloadMapper
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function applyPayload(AssetPayload $payload, Asset $asset): void
    {
        if ($payload->metadata !== null) {
            $metadataEvent = new GenericEvent(null, [
                'id' => $asset->getId(),
                'metadata' => $payload->metadata,
            ]);
            $this->eventDispatcher->dispatch($metadataEvent, AdminEvents::ASSET_METADATA_PRE_SET);
            $this->applyMetadata($metadataEvent->getArgument('metadata'), $asset);
        }

        $this->applyProperties($payload->propertiesData, $asset);
        $this->applyScheduler($payload->schedulerData, $asset);
        $this->applyRawData($payload->rawData, $asset);
        $this->applyImageSettings($payload->hasImage, $payload->imageData, $asset);
    }

    private function applyMetadata(array $metadata, Asset $asset): void
    {
        $metadataValues = Asset\Service::minimizeMetadata($metadata['values'], 'editor');
        $asset->setMetadataRaw($metadataValues);
    }

    private function applyProperties(?array $propertiesData, Asset $asset): void
    {
        if ($propertiesData === null) {
            return;
        }

        $properties = [];
        foreach ($propertiesData as $propertyName => $propertyData) {
            try {
                $property = new Model\Property();
                $property->setType($propertyData['type']);
                $property->setName($propertyName);
                $property->setCtype('asset');
                $property->setDataFromEditmode($propertyData['data']);
                $property->setInheritable($propertyData['inheritable']);

                $properties[$propertyName] = $property;
            } catch (Exception) {
                Logger::err("Can't add " . $propertyName . ' to asset ' . $asset->getRealFullPath());
            }
        }

        $asset->setProperties($properties);
    }

    private function applyScheduler(?array $schedulerData, Asset $asset): void
    {
        if ($schedulerData === null || !$asset->isAllowed('settings') || !method_exists($asset, 'setScheduledTasks')) {
            return;
        }

        $userId = $this->userContext->getAdminUser()?->getId();
        $tasks = [];

        foreach ($schedulerData as $taskData) {
            $taskData['userId'] = $userId;
            $tasks[] = new Task($taskData);
        }

        $asset->setScheduledTasks($tasks);
    }

    private function applyRawData(?string $rawData, Asset $asset): void
    {
        if ($rawData !== null) {
            $asset->setData($rawData);
        }
    }

    private function applyImageSettings(bool $hasImage, ?array $imageData, Asset $asset): void
    {
        if (!$asset instanceof Asset\Image) {
            return;
        }

        if ($hasImage && $imageData !== null) {
            if (isset($imageData['focalPoint'])) {
                $asset->setCustomSetting('focalPointX', $imageData['focalPoint']['x']);
                $asset->setCustomSetting('focalPointY', $imageData['focalPoint']['y']);
            }
        } else {
            $asset->removeCustomSetting('focalPointX');
            $asset->removeCustomSetting('focalPointY');
        }
    }
}

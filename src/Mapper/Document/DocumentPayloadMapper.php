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

namespace OpenDxp\Bundle\AdminBundle\Mapper\Document;

use Exception;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Folder\SaveFolder\SaveFolderPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Hardlink\SaveHardlink\SaveHardlinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Link\SaveLink\SaveLinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\PagePayload;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\PersonalizationBundle\Model\Document\Targeting\TargetingDocumentInterface;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\Concrete;
use OpenDxp\Model\Document;
use OpenDxp\Model\Document\Folder;
use OpenDxp\Model\Document\Hardlink;
use OpenDxp\Model\Document\Link;
use OpenDxp\Model\Element;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Property;
use OpenDxp\Model\Schedule\Task;

final class DocumentPayloadMapper
{
    public function __construct(private readonly AdminUserContextInterface $userContext)
    {
    }

    public function applyPagePayload(PagePayload $payload, Document\PageSnippet $document, ?string $task = null): void
    {
        // matches the original saveDocument() task switch: the scheduler task only ever
        // applied scheduler data, never settings/editables/properties (setValuesToDocument()
        // was called for publish/unpublish/save/version/autosave only)
        if ($task === 'scheduler') {
            $this->applyScheduler($payload->scheduler, $document);

            return;
        }

        if ($payload->missingRequiredEditable !== null) {
            $document->setMissingRequiredEditable($payload->missingRequiredEditable);
        }

        if ($payload->settings !== null && ($payload->settings['published'] ?? false)) {
            $document->setMissingRequiredEditable(null);
        }

        $this->applySettings($payload->settings, $document);
        $this->applyEditables($payload->editables, $payload->appendEditables, $document);
        $this->applyProperties($payload->properties, $document);
        $this->applyScheduler($payload->scheduler, $document);
    }

    public function applyLinkPayload(SaveLinkPayload $payload, Link $document): void
    {
        $this->applyLinkData($payload->data, $document);
        $this->applyProperties($payload->properties, $document);
        $this->applyScheduler($payload->scheduler, $document);
    }

    public function applyHardlinkPayload(SaveHardlinkPayload $payload, Hardlink $document): void
    {
        $this->applyHardlinkData($payload->data, $document);
        $this->applyProperties($payload->properties, $document);
        $this->applyScheduler($payload->scheduler, $document);
    }

    public function applyFolderPayload(SaveFolderPayload $payload, Folder $document): void
    {
        $this->applyProperties($payload->properties, $document);
    }

    private function applySettings(?array $settings, Document $document): void
    {
        if ($settings === null || !$document->isAllowed('settings')) {
            return;
        }

        if (array_key_exists('prettyUrl', $settings)) {
            $settings['prettyUrl'] = htmlspecialchars($settings['prettyUrl']);
        }

        $document->setValues($settings);
    }

    private function applyEditables(?array $editables, bool $appendEditables, Document\PageSnippet $document): void
    {
        $isTargetSpecific = interface_exists(TargetingDocumentInterface::class)
            && $document instanceof TargetingDocumentInterface
            && $document->hasTargetGroupSpecificEditables();

        if ($appendEditables || $isTargetSpecific) {
            $document->getEditables();
        } else {
            // ensure no editables (e.g. from session, version, ...) are still referenced
            $document->setEditables(null);
        }

        if ($editables === null) {
            return;
        }

        foreach ($editables as $name => $editableData) {
            $document->setRawEditable($name, $editableData['type'], $editableData['data'] ?? null);
        }
    }

    private function applyProperties(?array $propertiesData, Document $document): void
    {
        if ($propertiesData === null) {
            $document->getProperties();

            return;
        }

        $properties = [];
        foreach ($document->getProperties() as $p) {
            if ($p->isInherited()) {
                $properties[$p->getName()] = $p;
            }
        }

        foreach ($propertiesData as $propertyName => $propertyData) {
            $value = $propertyData['data'];

            try {
                $property = new Property();
                $property->setType($propertyData['type']);
                $property->setName($propertyName);
                $property->setCtype('document');
                $property->setDataFromEditmode($value);
                $property->setInheritable($propertyData['inheritable']);

                if ($propertyName === 'language') {
                    $property->setInherited($this->resolvePropertyInheritance($document, $propertyName, $value));
                }

                $properties[$propertyName] = $property;
            } catch (Exception) {
                Logger::warning("Can't add " . $propertyName . ' to document ' . $document->getRealFullPath());
            }
        }

        if ($document->isAllowed('properties')) {
            $document->setProperties($properties);
        }

        $document->getProperties();
    }

    private function applyScheduler(?array $schedulerData, ElementInterface $element): void
    {
        if ($schedulerData === null || !$element->isAllowed('settings') || !method_exists($element, 'setScheduledTasks')) {
            return;
        }

        $userId = $this->userContext->getAdminUser()?->getId();
        $tasks = [];

        foreach ($schedulerData as $taskData) {
            $taskData['userId'] = $userId;
            $tasks[] = new Task($taskData);
        }

        $element->setScheduledTasks($tasks);
    }

    private function applyLinkData(?array $data, Link $document): void
    {
        if ($data === null) {
            return;
        }

        $path = $data['path'];
        $target = null;

        if (!empty($path)) {
            if ($data['linktype'] === 'internal' && $data['internalType']) {
                $target = Element\Service::getElementByPath($data['internalType'], $path);
                if ($target) {
                    $data['internal'] = $target->getId();
                }
            }

            if (!$target) {
                if ($target = Document::getByPath($path)) {
                    $data['internalType'] = 'document';
                    $data['internal'] = $target->getId();
                } elseif ($target = Asset::getByPath($path)) {
                    $data['internalType'] = 'asset';
                    $data['internal'] = $target->getId();
                } elseif ($target = Concrete::getByPath($path)) {
                    $data['internalType'] = 'object';
                    $data['internal'] = $target->getId();
                } else {
                    $data['linktype'] = 'direct';
                    $data['internalType'] = null;
                    $data['internal'] = null;
                    $data['direct'] = $path;
                }

                if ($target) {
                    $data['linktype'] = 'internal';
                    $data['direct'] = '';
                }
            }
        } else {
            $data['linktype'] = 'internal';
            $data['direct'] = '';
            $data['internalType'] = null;
            $data['internal'] = null;
        }

        unset($data['path']);
        $document->setValues($data);
    }

    private function applyHardlinkData(?array $data, Hardlink $document): void
    {
        if ($data === null) {
            return;
        }

        $sourceId = null;
        if ($sourceDocument = Document::getByPath($data['sourcePath'])) {
            $sourceId = $sourceDocument->getId();
        }

        $document->setSourceId($sourceId);
        $document->setValues($data);
    }

    private function resolvePropertyInheritance(Document $document, string $propertyName, mixed $value): bool
    {
        if ($document->getParent()) {
            return $value == $document->getParent()->getProperty($propertyName);
        }

        return false;
    }
}

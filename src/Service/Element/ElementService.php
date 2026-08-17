<?php

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

namespace OpenDxp\Bundle\AdminBundle\Service\Element;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Controller\Traits\AdminStyleTrait;
use OpenDxp\Bundle\AdminBundle\CustomView;
use OpenDxp\Bundle\AdminBundle\Event\ElementAdminStyleEvent;
use OpenDxp\Config;
use OpenDxp\Logger;
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use OpenDxp\Model\Site;
use OpenDxp\Security\User\UserLoader;

use OpenDxp\Tool\Frontend;
use OpenDxp\Workflow\Manager;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * @internal
 */
class ElementService implements ElementServiceInterface
{
    use AdminStyleTrait;

    public function __construct(
        protected UrlGeneratorInterface $urlGenerator,
        protected Config $config,
        protected UserLoader $userLoader
    ) {
    }

    /**
     * @internal
     */
    public function getCustomViewById(string $id): ?array
    {
        $customViews = CustomView\Config::get();
        foreach ($customViews as $customView) {
            if ($customView['id'] == $id) {
                return $customView;
            }
        }

        return null;
    }

    /**
     * @throws Exception
     */
    public function getElementTreeNodeConfig(ElementInterface $element): array
    {
        $tmpNode = [
            'id' => $element->getId(),
            'key' => $element->getKey(),
            'text' => htmlspecialchars($element->getKey() ?? ''),
            'type' => $element->getType(),
            'path' => $element->getRealFullPath(),
            'basePath' => $element->getRealPath(),
            'locked' => $element->isLocked(),
            'lockOwner' => (bool)$element->getLocked(),
            'elementType' => Service::getElementType($element),
        ];

        if ($element instanceof Asset) {
            $tmpNode['cls'] = '';
            $this->assignAssetTreeConfig($element, $tmpNode);
        } elseif ($element instanceof Document) {
            $tmpNode['idx'] = $element->getIndex();
            $tmpNode['published'] = $element->isPublished();
            $tmpNode['leaf'] = true;

            $this->assignDocumentTreeConfig($element, $tmpNode);
        } elseif ($element instanceof DataObject) {
            $tmpNode['idx'] = $element->getIndex();
            $tmpNode['sortBy'] = $element->getChildrenSortBy();
            $tmpNode['sortOrder'] = $element->getChildrenSortOrder();

            $this->assignDataObjectTreeConfig($element, $tmpNode);
        }

        $tmpNode['permissions'] = $this->mergeWorkflowPermissions($element, $tmpNode['permissions']);

        $this->addAdminStyle($element, ElementAdminStyleEvent::CONTEXT_TREE, $tmpNode);

        if ($element->isLocked()) {
            $tmpNode['cls'] .= 'opendxp_treenode_locked ';
        }
        if ($element->getLocked()) {
            $tmpNode['cls'] .= 'opendxp_treenode_lockOwner ';
        }

        return $tmpNode;
    }

    public function getThumbnailUrl(Asset $asset, array $params = []): ?string
    {
        $defaults = [
            'id' => $asset->getId(),
            'treepreview' => true,
            '_dc' => $asset->getModificationDate(),
        ];

        if ($asset instanceof Asset\Image && ($params['origin'] ?? null) === 'folderPreview') {
            if (!$asset->getThumbnail(Asset\Image\Thumbnail\Config::getPreviewConfig())->exists()) {
                $defaults['_dc'] = time();
            }
        }

        $params = [...$defaults, ...$params];

        switch ($asset) {
            case $asset instanceof Asset\Image:
                $thumbnailUrl = $this->urlGenerator->generate('opendxp_admin_asset_getimagethumbnail', $params);

                break;
            case $asset instanceof Asset\Folder:
                $thumbnailUrl = $this->urlGenerator->generate('opendxp_admin_asset_getfolderthumbnail', $params);

                break;
            case $asset instanceof Asset\Video && \OpenDxp\Video::isAvailable():
                $thumbnailUrl = $this->urlGenerator->generate('opendxp_admin_asset_getvideothumbnail', $params);

                break;
            case $asset instanceof Asset\Document && \OpenDxp\Document::isAvailable() && $asset->getPageCount():
                $thumbnailUrl = $this->urlGenerator->generate('opendxp_admin_asset_getdocumentthumbnail', $params);

                break;
            case $asset instanceof Asset\Audio:
                $thumbnailUrl = '/bundles/opendxpadmin/img/flat-color-icons/speaker.svg';

                break;
            default:
                $thumbnailUrl = '/bundles/opendxpadmin/img/filetype-not-supported.svg';
        }

        return $thumbnailUrl;
    }

    /**
     * @throws Exception
     */
    private function assignAssetTreeConfig(
        Asset $element,
        array &$tmpNode
    ): void {
        $user = $this->userLoader->getUser();
        $hasChildren = $element->getDao()->hasChildren($user);
        $permissions = $element->getUserPermissions($user);

        $tmpNode['permissions'] = [
            'remove' => $permissions['delete'],
            'settings' => $permissions['settings'],
            'rename' => $permissions['rename'],
            'publish' => $permissions['publish'],
            'view' => $permissions['view'],
            'list' => $permissions['list'],
        ];

        if ($element instanceof Asset\Folder) {
            $tmpNode['leaf'] = false;
            $tmpNode['expanded'] = !$hasChildren;
            $tmpNode['loaded'] = !$hasChildren;
            $tmpNode['permissions']['create'] = $permissions['create'];
            $tmpNode['thumbnail'] = $this->getThumbnailUrl($element, ['origin' => 'treeNode']);
        } else {
            $tmpNode['leaf'] = true;
            $tmpNode['expandable'] = false;
            $tmpNode['expanded'] = false;
        }

        $this->assignAssetThumbnailConfig($element, $tmpNode);
    }

    /**
     * @throws Exception
     */
    private function assignDataObjectTreeConfig(DataObject\AbstractObject $element, array &$tmpNode): void
    {
        $allowedTypes = [DataObject::OBJECT_TYPE_OBJECT, DataObject::OBJECT_TYPE_FOLDER];
        if ($element instanceof DataObject\Concrete && $element->getClass()->getShowVariants()) {
            $allowedTypes[] = DataObject::OBJECT_TYPE_VARIANT;
        }
        $user = $this->userLoader->getUser();
        $hasChildren = $element->getDao()->hasChildren($allowedTypes, null, $user);

        $tmpNode['allowDrop'] = ($tmpNode['type'] ?? false) != DataObject::OBJECT_TYPE_VARIANT;
        $tmpNode['isTarget'] = true;
        $tmpNode['allowChildren'] = true;
        $tmpNode['leaf'] = !$hasChildren;
        $tmpNode['cls'] = 'opendxp_class_icon ';

        if ($element instanceof DataObject\Concrete) {
            $tmpNode['published'] = $element->isPublished();
            $tmpNode['className'] = $element->getClass()->getName();

            if (!$element->isPublished()) {
                $tmpNode['cls'] .= 'opendxp_unpublished ';
            }

            $tmpNode['allowVariants'] = $element->getClass()->getAllowVariants();
        }

        $tmpNode['expanded'] = !$hasChildren;
        $tmpNode['permissions'] = $element->getUserPermissions($user);

        if ($tmpNode['leaf']) {
            $tmpNode['expandable'] = false;
            $tmpNode['leaf'] = false; //this is required to allow drag&drop
            $tmpNode['expanded'] = true;
            $tmpNode['loaded'] = true;
        }
    }

    /**
     * @throws Exception
     */
    private function assignDocumentTreeConfig(
        Document $element,
        array &$tmpNode
    ): void {
        $user = $this->userLoader->getUser();
        $hasChildren = $element->getDao()->hasChildren(null, $user);

        $treeNodePermissionTypes = [
            'view',
            'remove' => 'delete',
            'settings',
            'rename',
            'publish',
            'unpublish',
            'create',
            'list',
        ];

        $permissions = $element->getUserPermissions($user);

        foreach ($treeNodePermissionTypes as $key => $permissionType) {
            $permissionKey = is_string($key) ? $key : $permissionType;
            $tmpNode['permissions'][$permissionKey] = $permissions[$permissionType];
        }

        $tmpNode['expandable'] = $hasChildren;
        $tmpNode['loaded'] = !$hasChildren;

        // set type specific settings
        if ($element->getType() === 'page') {
            $tmpNode['leaf'] = false;
            $tmpNode['expanded'] = !$hasChildren;

            // test for a site
            if ($site = Site::getByRootId($element->getId())) {
                $site->setRootDocument(null);
                $tmpNode['site'] = $site->getObjectVars();
            }
        } elseif (in_array($element->getType(), ['folder', 'link', 'hardlink'])) {
            $tmpNode['leaf'] = false;
            $tmpNode['expanded'] = !$hasChildren;
        } elseif (method_exists($element, 'getTreeNodeConfig')) { //for BC reasons
            $tmp = $element->getTreeNodeConfig();
            $tmpNode = [...$tmpNode, ...$tmp];
        }

        $this->assignDocumentSpecificSettings($element, $tmpNode);
    }

    private function assignAssetThumbnailConfig(Asset $asset, array &$tmpAsset): void
    {
        try {
            switch ($asset) {
                case $asset instanceof Asset\Image:
                    $tmpAsset['thumbnail'] = $this->getThumbnailUrl($asset, ['origin' => 'treeNode']);

                    // we need the dimensions for the wysiwyg editors, so that they can resize the image immediately
                    if ($asset->getCustomSetting('imageDimensionsCalculated')) {
                        $tmpAsset['imageWidth'] = $asset->getCustomSetting('imageWidth');
                        $tmpAsset['imageHeight'] = $asset->getCustomSetting('imageHeight');
                    }

                    break;

                case $asset instanceof Asset\Video:
                    if (\OpenDxp\Video::isAvailable()) {
                        $tmpAsset['thumbnail'] = $this->getThumbnailUrl($asset, ['origin' => 'treeNode']);
                    }

                    break;

                case $asset instanceof Asset\Document:
                    // add the PDF check here, otherwise the preview layer in admin is shown without content
                    if (
                        \OpenDxp\Document::isAvailable() &&
                        \OpenDxp\Document::isFileTypeSupported($asset->getFilename())
                    ) {
                        $tmpAsset['thumbnail'] = $this->getThumbnailUrl($asset, ['origin' => 'treeNode']);
                    }

                    break;
            }
        } catch (Exception $e) {
            Logger::error('Cannot get dimensions of asset, seems to be broken. Reason: ' . $e->getMessage());
        }
    }

    private function assignDocumentSpecificSettings(Document $document, array &$tmpDocument): void
    {
        // PREVIEWS temporary disabled, need's to be optimized some time
        if ($document instanceof Document\Page && isset($this->config['documents']['generate_preview'])) {
            $thumbnailFile = $document->getPreviewImageFilesystemPath();
            // only if the thumbnail exists and isn't out of time
            if (file_exists($thumbnailFile) && filemtime($thumbnailFile) > ($document->getModificationDate() - 20)) {
                $tmpDocument['thumbnail'] =
                    $this->urlGenerator->generate('opendxp_admin_document_page_display_preview_image',
                        ['id' => $document->getId()]
                    );
            }
        }

        $tmpDocument['cls'] = '';

        if ($document instanceof Document\Page) {
            $tmpDocument['url'] = $document->getFullPath();
            $site = Frontend::getSiteForDocument($document);
            if ($site instanceof Site) {
                $tmpDocument['url'] =
                    'http://' .
                    $site->getMainDomain() .
                    preg_replace(
                        '@^' .
                        $site->getRootPath() .
                        '/?@',
                        '/',
                        $document->getRealFullPath()
                    );
            }
        }

        if ($document->getProperty('navigation_exclude')) {
            $tmpDocument['cls'] .= 'opendxp_navigation_exclude ';
        }

        if (!$document->isPublished()) {
            $tmpDocument['cls'] .= 'opendxp_unpublished ';
        }
    }

    private function mergeWorkflowPermissions(ElementInterface $element, array $permissions): array
    {
        $workflowManager = OpenDxp::getContainer()->get(Manager::class);

        $workflowPermission = [
            'settings' => !$workflowManager->isDeniedInWorkflow($element, 'settings'),
            'rename' => !$workflowManager->isDeniedInWorkflow($element, 'rename'),
            'publish' => !$workflowManager->isDeniedInWorkflow($element, 'publish'),
        ];

        if ($element instanceof Asset) {
            $workflowPermission['remove'] = !$workflowManager->isDeniedInWorkflow($element, 'delete');
        } elseif ($element instanceof DataObject) {
            $workflowPermission['delete'] = !$workflowManager->isDeniedInWorkflow($element, 'delete');
        }

        return [...$permissions, ...$workflowPermission];
    }
}

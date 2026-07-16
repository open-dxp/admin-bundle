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

namespace OpenDxp\Bundle\AdminBundle\Service\Asset;

use Exception;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\Mapper\GridData;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use OpenDxp\Logger;
use OpenDxp\Loader\ImplementationLoader\Exception\UnsupportedException;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Metadata;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class AssetGridService
{
    public function __construct(
        private readonly AdminUserContextInterface $userContext,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly GridHelperService $gridHelperService,
    ) {}

    public function gridProxy(array $allParams, ?string $effectiveLanguage): array
    {
        if (isset($allParams['data']) && $allParams['data']) {
            if ($allParams['xaction'] === 'update') {
                try {
                    $data = json_decode($allParams['data'], true);

                    $updateEvent = new GenericEvent(null, [
                        'data' => $data,
                        'processed' => false,
                    ]);
                    $this->eventDispatcher->dispatch($updateEvent, AdminEvents::ASSET_LIST_BEFORE_UPDATE);

                    if ($updateEvent->getArgument('processed')) {
                        return ['success' => true];
                    }

                    $data = $updateEvent->getArgument('data');

                    $asset = Asset::getById((int) $data['id']);
                    if (!$asset) {
                        throw new NotFoundHttpException('Asset not found');
                    }
                    if (!$asset->isAllowed('publish')) {
                        throw new AccessDeniedHttpException("Permission denied. You don't have the rights to save this asset.");
                    }

                    $loader = OpenDxp::getContainer()->get('opendxp.implementation_loader.asset.metadata.data');
                    $metadata = $asset->getMetadata(null, null, false, true);
                    $dirty = false;

                    unset($data['id']);
                    $fieldLanguage = $effectiveLanguage;
                    foreach ($data as $key => $value) {
                        $fieldDef = explode('~', (string) $key);
                        $key = $fieldDef[0];
                        if (isset($fieldDef[1])) {
                            $fieldLanguage = ($fieldDef[1] === 'none' ? '' : $fieldDef[1]);
                        }

                        foreach ($metadata as &$em) {
                            if ($em['name'] == $key && $em['language'] == $fieldLanguage) {
                                try {
                                    $dataImpl = $loader->build($em['type']);
                                    $value = $dataImpl->getDataFromListfolderGrid($value, $em);
                                } catch (UnsupportedException) {
                                    Logger::error('could not resolve metadata implementation for ' . $em['type']);
                                }
                                $em['data'] = $value;
                                $dirty = true;
                                break;
                            }
                        }
                        unset($em);

                        if (!$dirty) {
                            $defaultMetadataFields = ['title', 'alt', 'copyright'];
                            if (in_array($key, $defaultMetadataFields)) {
                                $newEm = [
                                    'name' => $key,
                                    'language' => $fieldLanguage,
                                    'type' => 'input',
                                    'data' => $value,
                                ];
                                try {
                                    $dataImpl = $loader->build($newEm['type']);
                                    $newEm['data'] = $dataImpl->getDataFromListfolderGrid($value, $newEm);
                                } catch (UnsupportedException) {
                                    Logger::error('could not resolve metadata implementation for ' . $newEm['type']);
                                }
                                $metadata[] = $newEm;
                                $dirty = true;
                            } else {
                                $predefined = Metadata\Predefined::getByName($key);
                                if ($predefined && (empty($predefined->getTargetSubtype())
                                        || $predefined->getTargetSubtype() === $asset->getType())) {
                                    $newEm = [
                                        'name' => $key,
                                        'language' => $fieldLanguage,
                                        'type' => $predefined->getType(),
                                        'data' => $value,
                                    ];
                                    try {
                                        $dataImpl = $loader->build($newEm['type']);
                                        $newEm['data'] = $dataImpl->getDataFromListfolderGrid($value, $newEm);
                                    } catch (UnsupportedException) {
                                        Logger::error('could not resolve metadata implementation for ' . $newEm['type']);
                                    }
                                    $metadata[] = $newEm;
                                    $dirty = true;
                                }
                            }
                        }
                    }

                    if ($dirty) {
                        $metadataEvent = new GenericEvent(null, [
                            'id' => $asset->getId(),
                            'metadata' => $metadata,
                        ]);
                        $this->eventDispatcher->dispatch($metadataEvent, AdminEvents::ASSET_METADATA_PRE_SET);

                        $asset->setMetadataRaw($metadataEvent->getArgument('metadata'));
                        $asset->save();

                        return ['success' => true];
                    }

                    return ['success' => false, 'message' => 'something went wrong.'];
                } catch (NotFoundHttpException|AccessDeniedHttpException $e) {
                    throw $e;
                } catch (Exception $e) {
                    return ['success' => false, 'message' => $e->getMessage()];
                }
            }
        } else {
            $list = $this->gridHelperService->prepareAssetListingForGrid($allParams, $this->userContext->getAdminUser());

            $beforeListLoadEvent = new GenericEvent($this->gridHelperService, [
                'list' => $list,
                'context' => $allParams,
            ]);
            $this->eventDispatcher->dispatch($beforeListLoadEvent, AdminEvents::ASSET_LIST_BEFORE_LIST_LOAD);
            /** @var Asset\Listing $list */
            $list = $beforeListLoadEvent->getArgument('list');

            $list->load();

            $assets = [];
            foreach ($list->getAssets() as $asset) {
                if ($asset->isAllowed('list')) {
                    $assets[] = GridData\Asset::getData($asset, $allParams['fields'], $allParams['language'] ?? '');
                }
            }

            $result = ['success' => true, 'data' => $assets, 'total' => $list->getTotalCount()];

            $afterListLoadEvent = new GenericEvent($this->gridHelperService, [
                'list' => $result,
                'context' => $allParams,
            ]);
            $this->eventDispatcher->dispatch($afterListLoadEvent, AdminEvents::ASSET_LIST_AFTER_LIST_LOAD);

            return (array) $afterListLoadEvent->getArgument('list');
        }

        return ['success' => false];
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document;

use OpenDxp\Bundle\AdminBundle\Enum\SiteCustomConfigNodeType;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\SiteCustomSettingsEvent;
use OpenDxp\Model\Site;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class UpdateSiteHandler
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(
        int $rootId,
        array $domains,
        string $mainDomain,
        string $errorDocument,
        array $localizedErrorDocuments,
        bool $redirectToMainDomain,
        array $requestCustomSettings,
    ): UpdateSiteResult {
        if (!$site = Site::getByRootId($rootId)) {
            $site = Site::create(['rootId' => $rootId]);
        }

        $event = new SiteCustomSettingsEvent($site);
        $this->eventDispatcher->dispatch($event, AdminEvents::SITE_CUSTOM_SETTINGS);

        $customSettings = [];
        foreach ($event->getConfigNodes() as $scope => $nodes) {
            foreach ($nodes as $node) {
                $requestValueName = sprintf('customSettings_%s_%s', $scope, $node['name']);
                if (isset($requestCustomSettings[$requestValueName])) {
                    $value = $requestCustomSettings[$requestValueName];
                    if ($node['type'] === SiteCustomConfigNodeType::CHECKBOX->value) {
                        $value = $value === 'true';
                    }
                    $customSettings[$scope][$node['name']] = $value;
                }
            }
        }

        $site->setDomains($domains);
        $site->setMainDomain($mainDomain);
        $site->setErrorDocument($errorDocument);
        $site->setLocalizedErrorDocuments($localizedErrorDocuments);
        $site->setRedirectToMainDomain($redirectToMainDomain);
        $site->setCustomSettings(count($customSettings) === 0 ? null : $customSettings);
        $site->save();

        $site->setRootDocument(null);

        return new UpdateSiteResult(siteVars: $site->getObjectVars());
    }
}

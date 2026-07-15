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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\GetAvailableSites;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Logger;
use OpenDxp\Model;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetAvailableSitesHandler
{
    public function __construct(
        private readonly TranslatorInterface $translator,
        private readonly AdminUserContextInterface $userContext,
    ) {
    }

    public function __invoke(GetAvailableSitesPayload $payload): GetAvailableSitesResult
    {
        $adminUser = $this->userContext->getAdminUser();

        if ($adminUser === null || !$adminUser->isAllowed('documents')) {
            Logger::log('[Startup] Sites are not loaded as "documents" permission is missing');

            return new GetAvailableSitesResult(sites: []);
        }

        $sitesList = new Model\Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = [];

        if (!$payload->excludeMainSite) {
            $sites[] = [
                'id' => 0,
                'rootId' => 1,
                'domains' => '',
                'rootPath' => '/',
                'domain' => $this->translator->trans('main_site', [], 'admin'),
            ];
        }

        foreach ($sitesObjects as $site) {
            if ($site->getRootDocument()) {
                if ($site->getMainDomain()) {
                    $sites[] = [
                        'id' => $site->getId(),
                        'rootId' => $site->getRootId(),
                        'domains' => implode(',', $site->getDomains()),
                        'rootPath' => $site->getRootPath(),
                        'domain' => $site->getMainDomain(),
                    ];
                }
            } else {
                $site->delete();
            }
        }

        return new GetAvailableSitesResult(sites: $sites);
    }
}

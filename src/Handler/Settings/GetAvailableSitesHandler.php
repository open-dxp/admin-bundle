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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings;

use OpenDxp\Model;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetAvailableSitesHandler
{
    public function __construct(private readonly TranslatorInterface $translator)
    {
    }

    public function __invoke(bool $excludeMainSite): GetAvailableSitesResult
    {
        $sitesList = new Model\Site\Listing();
        $sitesObjects = $sitesList->load();
        $sites = [];

        if (!$excludeMainSite) {
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

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Site;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\SiteCustomSettingsEvent;
use OpenDxp\Model\Site;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class GetSiteCustomSettingsHandler
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {}

    public function __invoke(int $siteId): GetSiteCustomSettingsResult
    {
        $site = Site::getById($siteId);

        $event = new SiteCustomSettingsEvent($site);
        $this->eventDispatcher->dispatch($event, AdminEvents::SITE_CUSTOM_SETTINGS);

        return new GetSiteCustomSettingsResult($event->getConfigNodes());
    }
}

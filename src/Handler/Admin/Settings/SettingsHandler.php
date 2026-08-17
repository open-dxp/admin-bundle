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

namespace OpenDxp\Bundle\AdminBundle\Handler\Admin\Settings;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\IndexActionSettingsEvent;
use OpenDxp\Bundle\AdminBundle\Perspective\Config as PerspectiveConfig;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminSettingsService;
use OpenDxp\Bundle\AdminBundle\Service\Admin\AdminUserContextInterface;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Config;
use OpenDxp\Extension\Bundle\OpenDxpBundleManager;
use OpenDxp\SystemSettingsConfig;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class SettingsHandler
{
    public function __construct(
        private readonly AdminSettingsService $adminSettingsService,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly OpenDxpBundleManager $bundleManager,
        private readonly Config $config,
        private readonly AdminUserContextInterface $userContext,
    ) {
    }

    public function __invoke(SettingsPayload $payload): SettingsResult
    {
        $user = $this->userContext->getAdminUser();
        $dto = $this->adminSettingsService->createSettings($payload->locale, $user);
        $settings = $dto->asSettingsArray();

        $event = new IndexActionSettingsEvent($settings);
        $this->eventDispatcher->dispatch($event, AdminEvents::INDEX_ACTION_SETTINGS);

        return new SettingsResult(
            templateParams: [
                'config'             => $this->config,
                'systemSettings'     => SystemSettingsConfig::get(),
                'adminSettings'      => AdminConfig::get(),
                'perspectiveConfig'  => new PerspectiveConfig(),
                'runtimePerspective' => $dto->perspective,
                'pluginJsPaths'      => $this->bundleManager->getJsPaths(),
                'pluginCssPaths'     => $this->bundleManager->getCssPaths(),
                'settings'           => $event->getSettings(),
            ],
            template: $event->getTemplate(),
        );
    }
}

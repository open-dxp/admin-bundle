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

namespace OpenDxp\Bundle\AdminBundle\Factory;

use OpenDxp\Bundle\AdminBundle\Service\Login\LoginPageService;
use OpenDxp\Config;
use OpenDxp\Extension\Bundle\OpenDxpBundleManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LoginPageFactory
{
    public function __construct(
        private readonly Config $config,
        private readonly OpenDxpBundleManager $bundleManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthenticationUtils $authenticationUtils,
    ) {}

    public function create(Request $request): LoginPageService
    {
        return new LoginPageService(
            config: $this->config,
            bundleManager: $this->bundleManager,
            eventDispatcher: $this->eventDispatcher,
            authenticationUtils: $this->authenticationUtils,
            request: $request,
        );
    }
}

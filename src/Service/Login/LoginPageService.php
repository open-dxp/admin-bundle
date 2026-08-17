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

namespace OpenDxp\Bundle\AdminBundle\Service\Login;

use Browser;
use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Service\Admin\CurrentControllerContextInterface;
use OpenDxp\Bundle\AdminBundle\System\AdminConfig;
use OpenDxp\Config;
use OpenDxp\Extension\Bundle\OpenDxpBundleManager;
use OpenDxp\Security\SecurityHelper;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class LoginPageService
{
    public function __construct(
        private readonly Config $config,
        private readonly OpenDxpBundleManager $bundleManager,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly AuthenticationUtils $authenticationUtils,
        private readonly RequestStack $requestStack,
        private readonly CurrentControllerContextInterface $currentControllerContext,
    ) {
    }

    /**
     * Full params for the main login page — includes browser detection,
     * error resolution and the LOGIN_BEFORE_RENDER event.
     */
    public function forLoginPage(?string $tooManyAttempts): array
    {
        $request = $this->requestStack->getCurrentRequest();
        $gcMaxlifetime = (int) ini_get('session.gc_maxlifetime') ?: 120;

        $params = $this->base() + [
            'csrfTokenRefreshInterval' => ($gcMaxlifetime - 60) * 1000,
            'browserSupported' => $this->detectBrowser(),
            'debug' => OpenDxp::inDebugMode(),
            'includeTemplates' => [],
            'deeplink' => $request?->query->has('deeplink') ?? false,
            'error' => $this->resolveError($tooManyAttempts),
            'login_error' => $this->authenticationUtils->getLastAuthenticationError(),
        ];

        $event = new GenericEvent($this->currentControllerContext->getController(), [
            'parameters' => $params,
            'config' => $this->config,
            'request' => $request,
        ]);
        $this->eventDispatcher->dispatch($event, AdminEvents::LOGIN_BEFORE_RENDER);

        return $event->getArgument('parameters');
    }

    /**
     * Base params shared across all login-area pages
     * (lost password, 2FA, 2FA setup).
     */
    public function base(): array
    {
        return [
            'config' => $this->config,
            'adminSettings' => AdminConfig::get(),
            'pluginCssPaths' => $this->bundleManager->getCssPaths(),
        ];
    }

    private function resolveError(?string $tooManyAttempts): ?string
    {
        if ($tooManyAttempts !== null) {
            return SecurityHelper::convertHtmlSpecialChars($tooManyAttempts);
        }

        $request = $this->requestStack->getCurrentRequest();

        if ($request?->query->has('auth_failed')) {
            return 'error_auth_failed';
        }

        if ($request?->query->has('session_expired')) {
            return 'error_session_expired';
        }

        return null;
    }

    private function detectBrowser(): bool
    {
        $browser = new Browser();
        $version = (float) $browser->getVersion();

        return match ($browser->getBrowser()) {
            Browser::BROWSER_FIREFOX => $version >= 72,
            Browser::BROWSER_CHROME => $version >= 84,
            Browser::BROWSER_SAFARI => $version >= 13.1,
            Browser::BROWSER_EDGE => $version >= 90,
            default => false,
        };
    }
}

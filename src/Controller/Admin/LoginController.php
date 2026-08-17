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

namespace OpenDxp\Bundle\AdminBundle\Controller\Admin;

use OpenDxp\Bundle\AdminBundle\Attribute\SessionGatewayAware;
use OpenDxp\Bundle\AdminBundle\Controller\AdminAbstractController;
use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LoginRedirectEvent;
use OpenDxp\Bundle\AdminBundle\Exception\Login\TwoFactorCodeInvalidException;
use OpenDxp\Bundle\AdminBundle\Handler\Login\Deeplink\DeeplinkHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Login\Deeplink\DeeplinkPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Login\GenerateTwoFactorSetup\GenerateTwoFactorSetupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Login\GenerateTwoFactorSetup\GenerateTwoFactorSetupPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LoginCheck\LoginCheckPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Login\LostPassword\LostPasswordPayload;
use OpenDxp\Bundle\AdminBundle\Handler\Login\SaveTwoFactorSetup\SaveTwoFactorSetupHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Login\SaveTwoFactorSetup\SaveTwoFactorSetupPayload;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\AdminBundle\Service\Login\LoginPageService;
use OpenDxp\Bundle\AdminBundle\Session\Gateway\TwoFactorSetupSessionGateway;
use OpenDxp\Controller\KernelControllerEventInterface;
use OpenDxp\Controller\KernelResponseEventInterface;
use OpenDxp\Http\ResponseHelper;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapQueryParameter;
use Symfony\Component\HttpKernel\Event\ControllerEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
class LoginController extends AdminAbstractController implements KernelControllerEventInterface, KernelResponseEventInterface
{
    public function __construct(
        protected ResponseHelper $responseHelper,
        protected TranslatorInterface $translator,
        protected EventDispatcherInterface $eventDispatcher,
        private readonly LoginPageService $loginPageService,
    ) {
    }

    public function onKernelControllerEvent(ControllerEvent $event): void
    {
        $locale = 'en';
        $availableLocales = \OpenDxp\Tool\Admin::getLanguages();

        foreach ($event->getRequest()->getLanguages() as $userLocale) {
            if (in_array($userLocale, $availableLocales)) {
                $locale = $userLocale;
                break;
            }
        }

        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($locale);
        }
    }

    public function onKernelResponseEvent(ResponseEvent $event): void
    {
        $response = $event->getResponse();
        $response->headers->set('X-Frame-Options', 'deny', true);
        $this->responseHelper->disableCache($response, true);
    }

    #[Route('/login', name: 'opendxp_admin_login')]
    #[Route('/login/', name: 'opendxp_admin_login_fallback')]
    public function loginAction(
        Request $request,
        CsrfProtectionHandler $csrfProtection,
        #[MapQueryParameter(name: 'too_many_attempts')] ?string $tooManyAttempts = null,
    ): Response {

        $queryParams = $request->query->all();

        if ($request->attributes->get('_route') === 'opendxp_admin_login_fallback') {
            return $this->redirectToRoute('opendxp_admin_login', $queryParams, Response::HTTP_MOVED_PERMANENTLY);
        }

        $redirectUrl = $this->dispatchLoginRedirect($queryParams);
        if ($this->generateUrl('opendxp_admin_login', $queryParams) !== $redirectUrl) {
            return new RedirectResponse($redirectUrl);
        }

        if (!$csrfProtection->getCsrfToken($request->getSession())) {
            $csrfProtection->regenerateCsrfToken($request->getSession());
        }

        if ($this->getUser() instanceof UserInterface) {
            return $this->redirectToRoute('opendxp_admin_index');
        }

        return $this->render(
            '@OpenDxpAdmin/admin/login/login.html.twig',
            $this->loginPageService->forLoginPage($tooManyAttempts),
        );
    }

    #[Route('/login/csrf-token', name: 'opendxp_admin_login_csrf_token')]
    public function csrfTokenAction(Request $request, CsrfProtectionHandler $csrfProtection): JsonResponse
    {
        if (!$this->getAdminUser()) {
            $csrfProtection->regenerateCsrfToken($request->getSession());
        }

        return $this->json([
            'csrfToken' => $csrfProtection->getCsrfToken($request->getSession()),
        ]);
    }

    #[Route('/logout', name: 'opendxp_admin_logout', methods: ['POST'])]
    public function logoutAction(): void
    {
        // handled by the logout handler
    }

    /**
     * Dummy route used to check authentication
     */
    #[Route('/login/login', name: 'opendxp_admin_login_check')]
    public function loginCheckAction(LoginCheckPayload $payload): RedirectResponse
    {
        $params = [];
        if ($payload->perspective !== null) {
            $params['perspective'] = $payload->perspective;
        }

        return new RedirectResponse($this->generateUrl('opendxp_admin_login', $params));
    }

    #[Route('/login/lostpassword', name: 'opendxp_admin_login_lostpassword')]
    public function lostpasswordAction(
        Request $request,
        CsrfProtectionHandler $csrfProtection,
        LostPasswordPayload $payload,
        LostPasswordHandler $handler,
    ): Response {
        $params = $this->loginPageService->base();

        $result = $handler($payload);

        if ($result->eventResponse !== null) {
            return $result->eventResponse;
        }

        $csrfProtection->regenerateCsrfToken($request->getSession());

        return $this->render('@OpenDxpAdmin/admin/login/lost_password.html.twig', $params);
    }

    #[Route('/login/deeplink', name: 'opendxp_admin_login_deeplink')]
    public function deeplinkAction(
        DeeplinkHandler $handler,
        DeeplinkPayload $payload,
    ): Response {
        $result = $handler($payload);

        if ($result->redirectUrl) {
            return $this->redirect($result->redirectUrl);
        }

        return $this->render($result->template, $result->params);
    }

    #[Route('/login/2fa', name: 'opendxp_admin_2fa')]
    public function twoFactorAuthenticationAction(Request $request): Response
    {
        $params = $this->loginPageService->base();

        if ($request->hasSession()) {
            $session = $request->getSession();
            $authException = $session->get(SecurityRequestAttributes::AUTHENTICATION_ERROR);

            if ($authException instanceof AuthenticationException) {
                $session->remove(SecurityRequestAttributes::AUTHENTICATION_ERROR);
                $params['error'] = $authException->getMessage();
            }
        } else {
            $params['error'] = 'No session available, it either timed out or cookies are not enabled.';
        }

        return $this->render('@OpenDxpAdmin/admin/login/two_factor_authentication.html.twig', $params);
    }

    #[Route('/login/2fa-setup', name: 'opendxp_admin_2fa_setup')]
    public function twoFactorSetupAuthenticationAction(Request $request): Response|RedirectResponse
    {
        if ($request->isMethod('post')) {
            return $this->forward(self::class . '::twoFactorSetupSaveAction');
        }

        return $this->forward(self::class . '::twoFactorSetupGenerateAction', [], $request->query->all());
    }

    #[SessionGatewayAware(TwoFactorSetupSessionGateway::class)]
    #[Route('/login/2fa-setup-save', name: 'opendxp_admin_2fa_setup_save')]
    public function twoFactorSetupSaveAction(
        SaveTwoFactorSetupPayload $payload,
        SaveTwoFactorSetupHandler $handler,
    ): RedirectResponse {
        try {
            $handler($payload);
        } catch (TwoFactorCodeInvalidException) {
            return new RedirectResponse($this->generateUrl('opendxp_admin_2fa_setup', ['error' => '2fa_wrong']));
        }

        return new RedirectResponse($this->generateUrl('opendxp_admin_login'));
    }

    #[SessionGatewayAware(TwoFactorSetupSessionGateway::class)]
    #[Route('/login/2fa-setup-generate', name: 'opendxp_admin_2fa_setup_generate')]
    public function twoFactorSetupGenerateAction(
        GenerateTwoFactorSetupPayload $payload,
        GenerateTwoFactorSetupHandler $handler,
    ): Response {

        $params = $this->loginPageService->base();
        $params['setup'] = true;

        if ($payload->error) {
            $params['error'] = $payload->error;
        }

        $result = $handler($payload);
        $params['image'] = $result->qrDataUri;

        return $this->render('@OpenDxpAdmin/admin/login/two_factor_setup.html.twig', $params);
    }

    #[Route('/login/2fa-verify', name: 'opendxp_admin_2fa-verify')]
    public function twoFactorAuthenticationVerifyAction(): void
    {
        // handled by firewall
    }

    private function dispatchLoginRedirect(array $routeParams = []): string
    {
        $event = new LoginRedirectEvent('opendxp_admin_login', $routeParams);
        $this->eventDispatcher->dispatch($event, AdminEvents::LOGIN_REDIRECT);

        return $this->generateUrl($event->getRouteName(), $event->getRouteParams());
    }
}

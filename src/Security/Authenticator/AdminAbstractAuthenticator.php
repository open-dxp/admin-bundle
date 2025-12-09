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
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.ch)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Security\Authenticator;

use OpenDxp\Bundle\AdminBundle\Security\Authentication\Token\TwoFactorRequiredToken;
use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\User as UserModel;
use OpenDxp\Security\User\User;
use OpenDxp\Tool\Authentication;
use OpenDxp\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Core\Exception\TooManyLoginAttemptsAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Contracts\Translation\LocaleAwareInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * @internal
 */
abstract class AdminAbstractAuthenticator extends AbstractAuthenticator implements LoggerAwareInterface
{
    public const OPENDXP_ADMIN_LOGIN = 'opendxp_admin_login';

    public const OPENDXP_ADMIN_LOGIN_CHECK = 'opendxp_admin_login_check';

    use LoggerAwareTrait;

    protected bool $twoFactorRequired = false;

    public function __construct(
        protected EventDispatcherInterface $dispatcher,
        protected RouterInterface $router,
        protected TranslatorInterface $translator
    ) {
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $requestParameters = [
            'auth_failed' => 'true',
        ];

        if ($exception instanceof TooManyLoginAttemptsAuthenticationException) {
            $requestParameters = [
                'too_many_attempts' => $this->translator->trans($exception->getMessageKey(), $exception->getMessageData(), 'admin'),
            ];
        }
        $url = $this->router->generate(self::OPENDXP_ADMIN_LOGIN, $requestParameters);

        return new RedirectResponse($url);
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $securityUser = $token->getUser();
        if (!$securityUser instanceof User) {
            throw new \Exception('Invalid user object. User has to be instance of ' . User::class);
        }

        /** @var UserModel $user */
        $user = $securityUser->getUser();

        // set user language
        $request->setLocale($user->getLanguage());
        if ($this->translator instanceof LocaleAwareInterface) {
            $this->translator->setLocale($user->getLanguage());
        }

        // set user on runtime cache for legacy compatibility
        RuntimeCache::set('opendxp_admin_user', $user);

        // as we authenticate statelessly (short lived sessions) the authentication is called for
        // every request. therefore we only redirect if we're on the login page
        if (!in_array($request->attributes->get('_route'), [
            self::OPENDXP_ADMIN_LOGIN,
            self::OPENDXP_ADMIN_LOGIN_CHECK,
        ])) {
            return null;
        }

        if ($request->get('deeplink') && $request->get('deeplink') !== 'true') {
            $url = $this->router->generate('opendxp_admin_login_deeplink');
            $url .= '?' . $request->get('deeplink');
        } else {
            $url = $this->router->generate('opendxp_admin_index', [
                '_dc' => time(),
                'perspective' => strip_tags($request->get('perspective', '')),
            ]);
        }

        if ($url) {
            $response = new RedirectResponse($url);
            $response->headers->setCookie(new Cookie('opendxp_admin_sid', 'true'));

            return $response;
        }

        return null;
    }

    protected function saveUserToSession(User $user, SessionInterface $session): void
    {
        if (Authentication::isValidUser($user->getUser())) {
            $openDxpUser = $user->getUser();

            Session::useBag($session, function (AttributeBagInterface $adminSession, SessionInterface $session) use ($openDxpUser): void {
                $session->migrate();
                $adminSession->set('user', $openDxpUser);
            });
        }
    }

    #[\Override]
    public function createToken(Passport $passport, string $firewallName): TokenInterface
    {
        if ($this->twoFactorRequired) {
            return new TwoFactorRequiredToken(
                $passport->getUser(),
                $firewallName,
                $passport->getUser()->getRoles()
            );
        }
        return parent::createToken($passport, $firewallName);
    }
}

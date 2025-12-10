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

namespace OpenDxp\Bundle\AdminBundle\Security\Event;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LogoutEvent as OpenDxpLogoutEvent;
use OpenDxp\Model\Element\Editlock;
use OpenDxp\Model\User;
use OpenDxp\Tool\Authentication;
use OpenDxp\Tool\Session;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Http\Event\LogoutEvent;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

/**
 * Handle logout. This was originally implemented as LogoutHandler, but wasn't triggered as the token was empty at call
 * time in LogoutListener::handle was called. As the logout success handler is always triggered it is now implemented as
 * success handler.
 *
 *
 * @internal
 */
class LogoutListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public static function getSubscribedEvents(): array
    {
        return [
            LogoutEvent::class => 'onLogout',
        ];
    }

    public function __construct(
        protected TokenStorageInterface $tokenStorage,
        protected RouterInterface $router,
        protected EventDispatcherInterface $eventDispatcher
    ) {
    }

    public function onLogout(LogoutEvent $event): void
    {
        $request = $event->getRequest();

        $event->setResponse($this->onLogoutSuccess($request));
    }

    public function onLogoutSuccess(Request $request): RedirectResponse|Response
    {
        $this->logger->debug('Logging out');

        $this->tokenStorage->setToken(null);

        // clear open edit locks for this session
        Editlock::clearSession($request->getSession()->getId());

        /** @var OpenDxpLogoutEvent|null $event */
        $event = Session::useBag($request->getSession(), function (AttributeBagInterface $adminSession) use ($request) {
            $event = null;

            $user = Authentication::authenticateSession($request);
            if ($user instanceof User) {
                $event = new OpenDxpLogoutEvent($request, $user);
                $this->eventDispatcher->dispatch($event, AdminEvents::LOGIN_LOGOUT);

                $adminSession->remove('user');
            }

            $adminSession->clear();

            return $event;
        });

        if ($event && $event->hasResponse()) {
            $response = $event->getResponse();
        } else {
            $response = new RedirectResponse($this->router->generate('opendxp_admin_index'));
        }

        // cleanup opendxp-cookies => 315554400 => strtotime('1980-01-01')
        $response->headers->setCookie(new Cookie('opendxp_opentabs', null, 315554400));
        // clear cookie -> we can't use $response->headers->clearCookie() because it doesn't allow $secure = null
        $response->headers->setCookie(new Cookie('opendxp_admin_sid', null, 1));

        if ($response instanceof RedirectResponse) {
            $this->logger->debug('Logout succeeded, redirecting to ' . $response->getTargetUrl());
        }

        return $response;
    }
}

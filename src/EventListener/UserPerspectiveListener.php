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

namespace OpenDxp\Bundle\AdminBundle\EventListener;

use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Model\User;
use OpenDxp\Security\User\TokenStorageUserResolver;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class UserPerspectiveListener implements EventSubscriberInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;
    use OpenDxpContextAwareTrait;

    public function __construct(protected TokenStorageUserResolver $userResolver)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        if ($user = $this->userResolver->getUser()) {
            $this->setRequestedPerspective($user, $request);
        }
    }

    protected function setRequestedPerspective(User $user, Request $request): void
    {
        // update perspective settings
        $requestedPerspective = $request->query->get('perspective');

        if ($requestedPerspective && $requestedPerspective !== $user->getActivePerspective()) {
            $existingPerspectives = array_keys(\OpenDxp\Bundle\AdminBundle\Perspective\Config::get());
            if (!in_array($requestedPerspective, $existingPerspectives)) {
                $this->logger->warning('Requested perspective {perspective} for {user} does not exist.', [
                    'user' => $user->getName(),
                    'perspective' => $requestedPerspective,
                ]);

                $requestedPerspective = null;
            }
        }

        if (!$requestedPerspective || !$user->isAllowed($requestedPerspective, 'perspective')) {
            $previouslyRequested = $requestedPerspective;

            // choose active perspective or a first allowed
            $requestedPerspective = $user->isAllowed($user->getActivePerspective(), 'perspective')
                ? $user->getActivePerspective()
                : $user->getFirstAllowedPerspective();

            if ($previouslyRequested) {
                $this->logger->warning('User {user} is not allowed requested perspective {requestedPerspective}. Falling back to {perspective}.', [
                    'user' => $user->getName(),
                    'requestedPerspective' => $previouslyRequested,
                    'perspective' => $requestedPerspective,
                ]);
            } else {
                $this->logger->debug('Perspective for user {user} was not requested. Falling back to {perspective}.', [
                    'user' => $user->getName(),
                    'perspective' => $requestedPerspective,
                ]);
            }
        }

        if ($requestedPerspective !== $user->getActivePerspective()) {
            $this->logger->info('Setting active perspective for user {user} to {perspective}.', [
                'user' => $user->getName(),
                'perspective' => $requestedPerspective,
            ]);

            $user->setActivePerspective($requestedPerspective);
            $user->save();
        }
    }
}

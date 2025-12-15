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
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class CustomAdminEntryPointCheckListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public function __construct(protected ?string $customAdminPathIdentifier)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 560],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if ($event->isMainRequest() && $this->customAdminPathIdentifier && $this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN) && $this->customAdminPathIdentifier !== $request->cookies->get('opendxp_custom_admin')) {
            // display standard 404 error page, we don't expose that /admin exists but access is prohibited
            throw new NotFoundHttpException();
        }
    }
}

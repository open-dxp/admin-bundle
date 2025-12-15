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

use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Twig\Environment;

/**
 * @internal
 */
class CsrfProtectionListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    protected Environment $twig;

    public function __construct(protected CsrfProtectionHandler $csrfProtectionHandler)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['handleRequest', 11],
        ];
    }

    public function handleRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        if ($event->getRequest()->hasSession()) {
            $this->csrfProtectionHandler->generateCsrfToken($event->getRequest()->getSession());
        }

        if ($request->isMethodCacheable()) {
            return;
        }

        $exludedRoutes = [
            // WebDAV
            'opendxp_admin_webdav',

            // external applications
            'opendxp_bundle_systeminfo_opcache_index',
        ];

        $route = $request->attributes->get('_route');
        if (in_array($route, $exludedRoutes) || in_array($route, $this->csrfProtectionHandler->getExcludedRoutes())) {
            return;
        }

        $this->csrfProtectionHandler->checkCsrfToken($request);
    }
}

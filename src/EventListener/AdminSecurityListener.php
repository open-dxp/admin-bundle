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

use OpenDxp\Bundle\AdminBundle\Security\ContentSecurityPolicyHandler;
use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Config;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\RequestHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class AdminSecurityListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public function __construct(
        protected RequestHelper $requestHelper,
        protected ContentSecurityPolicyHandler $contentSecurityPolicyHandler,
        protected Config $config
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$this->config['admin_csp_header']['enabled']) {
            return;
        }

        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        if (!$this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)) {
            return;
        }

        if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
            return;
        }

        if (!empty($this->config['admin_csp_header']['exclude_paths'])) {
            $requestUri = $request->getRequestUri();
            foreach ($this->config['admin_csp_header']['exclude_paths'] as $path) {
                if (@preg_match($path, $requestUri)) {
                    return;
                }
            }
        }

        $response = $event->getResponse();

        // set CSP header with random nonce string to the response
        $response->headers->set('Content-Security-Policy', $this->contentSecurityPolicyHandler->getCspHeader());
    }
}

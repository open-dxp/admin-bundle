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

namespace OpenDxp\Bundle\AdminBundle\EventListener;

use OpenDxp;
use OpenDxp\Bundle\CoreBundle\EventListener\Traits\OpenDxpContextAwareTrait;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Http\RequestHelper;
use OpenDxp\Http\ResponseHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class HttpCacheListener implements EventSubscriberInterface
{
    use OpenDxpContextAwareTrait;

    public function __construct(protected RequestHelper $requestHelper, protected ResponseHelper $responseHelper)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => 'onKernelResponse',
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $request = $event->getRequest();

        if (!$event->isMainRequest()) {
            return;
        }

        $disable = false;
        if ($this->matchesOpenDxpContext($request, OpenDxpContextResolver::CONTEXT_ADMIN)) {
            $disable = true;
        } else {
            if ($this->requestHelper->isFrontendRequestByAdmin($request)) {
                $disable = true;
            }

            if (OpenDxp::inDebugMode()) {
                $disable = true;
            }
        }

        $response = $event->getResponse();

        if ($disable) {
            // set headers to avoid problems with proxies, ...
            $this->responseHelper->disableCache($response, false);
        }
    }
}

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
use OpenDxp\Config;
use OpenDxp\Http\Request\Resolver\OpenDxpContextResolver;
use OpenDxp\Security\User\TokenStorageUserResolver;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class UsageStatisticsListener implements EventSubscriberInterface
{
    use LoggerAwareTrait;
    use OpenDxpContextAwareTrait;

    public function __construct(protected TokenStorageUserResolver $userResolver, protected Config $config)
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

        $this->logUsageStatistics($request);
    }

    protected function logUsageStatistics(Request $request): void
    {
        if (!empty($this->config['general']['disable_usage_statistics'])) {
            return;
        }

        $params = $this->getParams($request);
        $user = $this->userResolver->getUser();
        $this->logger->info($request->attributes->get('_controller', ''), [
            $user ? $user->getId() : '0',
            $request->attributes->get('_route'),
            $request->attributes->get('_route_params'),
            $params,
        ]);
    }

    protected function getParams(Request $request): array
    {
        $params = [];
        $disallowedKeys = ['_dc', 'module', 'controller', 'action', 'password'];

        // TODO is this enough?
        $requestParams = [...$request->query->all(), ...$request->request->all()];

        foreach ($requestParams as $key => $value) {
            if (is_json($value)) {
                $value = json_decode($value);
                if (is_array($value)) {
                    array_walk_recursive($value, function (&$item, $key): void {
                        if (str_contains((string)$key, 'pass')) {
                            $item = '*************';
                        }
                    });
                }

                $value = json_encode($value);
            }

            if (!in_array($key, $disallowedKeys) && is_string($value)) {
                $params[$key] = (strlen($value) > 40) ? substr($value, 0, 40) . '...' : $value;
            }
        }

        return $params;
    }
}

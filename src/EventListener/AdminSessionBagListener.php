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

use OpenDxp\Config;
use OpenDxp\Session\Attribute\LockableAttributeBag;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;

/**
 * @internal
 */
class AdminSessionBagListener implements EventSubscriberInterface
{
    public function __construct(protected Config $config)
    {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            //run after Symfony\Component\HttpKernel\EventListener\SessionListener
            KernelEvents::REQUEST => ['onKernelRequest', 127],
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        if ($event->getRequest()->attributes->get('_stateless', false)) {
            return;
        }

        $session = $event->getRequest()->getSession();

        //do not register bags, if session is already started
        if ($session->isStarted()) {
            return;
        }

        $this->configure($session);
    }

    public function configure(SessionInterface $session): void
    {
        foreach ($this->config['session']['attribute_bags'] as $name => $config) {
            $bag = new LockableAttributeBag($config['storage_key']);
            $bag->setName($name);

            $session->registerBag($bag);
        }
    }
}

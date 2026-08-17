<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Login\Deeplink;

use OpenDxp\Bundle\AdminBundle\Event\AdminEvents;
use OpenDxp\Bundle\AdminBundle\Event\Login\LoginRedirectEvent;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

final class DeeplinkHandler
{
    public function __construct(
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly UrlGeneratorInterface $urlGenerator,
    ) {}

    public function __invoke(DeeplinkPayload $payload): DeeplinkResult
    {
        // No deeplink pattern found → not found
        if (!$payload->deeplink) {
            throw new NotFoundHttpException();
        }

        // Has token → redirect with deeplink
        if (str_contains($payload->queryString, 'token')) {
            $event = new LoginRedirectEvent('opendxp_admin_login', [
                'deeplink' => $payload->deeplink,
                'perspective' => $payload->perspective,
            ]);
            $this->eventDispatcher->dispatch($event, AdminEvents::LOGIN_REDIRECT);

            $url = $this->urlGenerator->generate($event->getRouteName(), $event->getRouteParams());

            return new DeeplinkResult(redirectUrl: $url . '&' . $payload->queryString);
        }

        // Has query string → render deeplink page
        if ($payload->queryString) {
            $event = new LoginRedirectEvent('opendxp_admin_login', [
                'deeplink' => 'true',
                'perspective' => $payload->perspective,
            ]);
            $this->eventDispatcher->dispatch($event, AdminEvents::LOGIN_REDIRECT);

            $redirect = $this->urlGenerator->generate($event->getRouteName(), $event->getRouteParams());

            return new DeeplinkResult(
                template: '@OpenDxpAdmin/admin/login/deeplink.html.twig',
                params: ['tab' => $payload->deeplink, 'redirect' => $redirect],
            );
        }

        // No query string → not found
        throw new NotFoundHttpException();
    }
}

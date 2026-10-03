<?php

declare(strict_types=1);

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy\DataObjectGridProxyHandler;
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Security\TrustedLoginLinkHostResolver;
use OpenDxp\TestFoundation\Container;

it('boots with the services of the bundle', function (string $service) {
    expect(Container::get($service))->toBeInstanceOf($service);
})->with([
    GridHelperService::class,
    TrustedLoginLinkHostResolver::class,
    DataObjectGridProxyHandler::class,
]);

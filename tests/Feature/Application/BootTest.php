<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Application;

use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Security\TrustedLoginLinkHostResolver;
use OpenDxp\TestFoundation\Container;

it('boots with the services of the bundle', function (string $service) {
    expect(Container::get($service))->toBeInstanceOf($service);
})->with([
    'the grid helper' => [GridHelperService::class],
    'the login link host resolver' => [TrustedLoginLinkHostResolver::class],
]);

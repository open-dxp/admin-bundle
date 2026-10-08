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
 * @copyright  Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\LoginLink;

use OpenDxp\Bundle\AdminBundle\Tests\Application\Host\MockGeneralHostProvider;
use OpenDxp\Test\Factory\SiteFactory;

it('trusts the host of a registered site without asking the providers', function () {
    SiteFactory::createOne(['mainDomain' => $this->domain('main')]);

    expect($this->hostResolver()->resolve($this->requestFor($this->domain('main'))))
        ->toBe($this->domain('main'))
        ->and($this->generalHosts->contexts)
        ->toBe([]);
});

it('falls back to the general host for a host no site knows', function () {
    SiteFactory::createOne(['mainDomain' => $this->domain('main')]);

    expect($this->hostResolver()->resolve($this->requestFor('attacker.example')))->toBe(self::GENERAL_HOST);
});

it('trusts a backend host that a provider vouches for', function () {
    $backend = sprintf('backend.%s', $this->domain('site'));
    $this->generalHosts = new MockGeneralHostProvider($backend);

    expect($this->hostResolver()->resolve($this->requestFor($backend)))->toBe($backend);
});

it('falls back to the general host without a request', function () {
    expect($this->hostResolver()->resolve(null))->toBe(self::GENERAL_HOST);
});

it('hands the request to the providers it asks for the general host', function () {
    $request = $this->requestFor('attacker.example');

    $this->hostResolver()->resolve($request);

    expect($this->generalHosts->contexts)->toBe([['source' => $request]]);
});

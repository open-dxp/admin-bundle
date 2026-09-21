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

namespace OpenDxp\Bundle\AdminBundle\Tests\Model\Security;

use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\LoginLinkHostTestCase;
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\RecordingGeneralHostProvider;

class TrustedLoginLinkHostResolverTest extends LoginLinkHostTestCase
{
    public function testReturnsRequestHostForRegisteredSiteDomainWithoutConsultingProviders(): void
    {
        $this->createSite($this->domain('main'));

        $host = $this->createHostResolver()->resolve($this->createRequest($this->domain('main')));

        self::assertSame($this->domain('main'), $host);
        self::assertSame([], $this->generalHostProvider->receivedContexts);
    }

    public function testFallsBackToGeneralHostForUnknownRequestHost(): void
    {
        $this->createSite($this->domain('main'));

        $host = $this->createHostResolver()->resolve($this->createRequest('attacker.example'));

        self::assertSame(self::GENERAL_HOST, $host);
    }

    public function testReturnsUnregisteredBackendHostThatAProviderVouchesFor(): void
    {
        $backendHost = 'backend.' . $this->domain('site');
        $this->generalHostProvider = new RecordingGeneralHostProvider($backendHost);

        $host = $this->createHostResolver()->resolve($this->createRequest($backendHost));

        self::assertSame($backendHost, $host);
    }

    public function testFallsBackToGeneralHostWithoutRequest(): void
    {
        $host = $this->createHostResolver()->resolve(null);

        self::assertSame(self::GENERAL_HOST, $host);
    }

    public function testAsksFallbackProvidersToTrustTheRequestHost(): void
    {
        $request = $this->createRequest('attacker.example');

        $this->createHostResolver()->resolve($request);

        self::assertSame([['source' => $request, 'trust_request_host' => true]], $this->generalHostProvider->receivedContexts);
    }
}

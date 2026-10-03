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

namespace OpenDxp\Bundle\AdminBundle\Tests\TestCase;

use OpenDxp\Bundle\AdminBundle\Security\TrustedLoginLinkHostResolver;
use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Test\Factory\SiteFactory;
use OpenDxp\TestFoundation\Container;
use OpenDxp\TestFoundation\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

class LoginLinkTestCase extends TestCase
{
    protected const string GENERAL_HOST = 'general.login-link-test.example';

    protected GeneralHostProviderInterface $generalHosts;

    private string $run;

    protected function setUp(): void
    {
        parent::setUp();

        $this->run = uniqid();
        $this->generalHosts = self::generalHostProvider(self::GENERAL_HOST);
    }

    /**
     * The provider remembers the context it was asked with, so a test can see whether it was asked at all.
     */
    protected static function generalHostProvider(string $host): GeneralHostProviderInterface
    {
        return new class($host) implements GeneralHostProviderInterface {
            /**
             * @var list<array<string, mixed>>
             */
            public array $contexts = [];

            public function __construct(private readonly string $host)
            {
            }

            public function provide(array $context = []): ?string
            {
                $this->contexts[] = $context;

                return $this->host;
            }
        };
    }

    /**
     * OpenDXP caches the site of a domain beyond the transaction of a test, so every test uses domains of its own.
     */
    protected function domain(string $label): string
    {
        return sprintf('%s.%s.login-link-test.example', $label, $this->run);
    }

    protected function site(string $mainDomain): void
    {
        SiteFactory::createOne(['mainDomain' => $mainDomain]);
    }

    protected function hostResolver(): TrustedLoginLinkHostResolver
    {
        return new TrustedLoginLinkHostResolver(new GeneralHostResolver([$this->generalHosts]));
    }

    protected function requestFor(string $host): Request
    {
        return Request::create('https://' . $host . '/admin/login/lostpassword');
    }

    /**
     * Symfony's RouterListener takes the host of the request into the router context as it is, and so does this.
     */
    protected function requestStackFor(string $host): RequestStack
    {
        $request = $this->requestFor($host);
        $this->router()->getContext()->fromRequest($request);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    protected function router(): RouterInterface
    {
        return Container::get('router');
    }

    protected static function hostOf(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }
}

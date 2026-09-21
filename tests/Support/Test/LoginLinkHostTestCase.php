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

namespace OpenDxp\Bundle\AdminBundle\Tests\Support\Test;

use OpenDxp;
use OpenDxp\Bundle\AdminBundle\Security\TrustedLoginLinkHostResolver;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Model\Site;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\RouterInterface;

abstract class LoginLinkHostTestCase extends ModelTestCase
{
    protected const string GENERAL_HOST = 'general.login-link-test.example';

    protected RecordingGeneralHostProvider $generalHostProvider;

    /**
     * @var list<Site>
     */
    private array $sites = [];

    private string $runId;

    private string $originalRouterHost;

    protected function setUp(): void
    {
        parent::setUp();

        $this->runId = uniqid();
        $this->generalHostProvider = new RecordingGeneralHostProvider(self::GENERAL_HOST);
        $this->originalRouterHost = $this->getRouter()->getContext()->getHost();
    }

    protected function tearDown(): void
    {
        foreach ($this->sites as $site) {
            $site->delete();
        }
        $this->sites = [];

        $this->getRouter()->getContext()->setHost($this->originalRouterHost);

        parent::tearDown();
    }

    /**
     * Unique per test, so cached site lookups of one test cannot leak into another.
     */
    protected function domain(string $label): string
    {
        return sprintf('%s.%s.login-link-test.example', $label, $this->runId);
    }

    protected function createSite(string $mainDomain): void
    {
        $rootPage = TestHelper::createEmptyDocumentPage('login-link-site-root-');

        $site = new Site();
        $site->setRootId($rootPage->getId());
        $site->setMainDomain($mainDomain);
        $site->save();

        $this->sites[] = $site;
    }

    protected function createHostResolver(): TrustedLoginLinkHostResolver
    {
        return new TrustedLoginLinkHostResolver(new GeneralHostResolver([$this->generalHostProvider]));
    }

    protected function createRequest(string $host): Request
    {
        return Request::create('https://' . $host . '/admin/login/lostpassword');
    }

    /**
     * Mirrors what Symfony's RouterListener does: the router context host is the raw request host.
     */
    protected function startRequestTo(string $host): RequestStack
    {
        $request = $this->createRequest($host);
        $this->getRouter()->getContext()->fromRequest($request);

        $requestStack = new RequestStack();
        $requestStack->push($request);

        return $requestStack;
    }

    protected function assertRouterHostIs(string $expectedHost): void
    {
        self::assertSame($expectedHost, $this->getRouter()->getContext()->getHost());
    }

    protected function getRouter(): RouterInterface
    {
        return OpenDxp::getContainer()->get('router');
    }

    protected function hostOf(string $url): ?string
    {
        $host = parse_url($url, PHP_URL_HOST);

        return is_string($host) ? $host : null;
    }
}

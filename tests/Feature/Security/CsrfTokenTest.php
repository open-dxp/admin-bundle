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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Security;

use OpenDxp\Bundle\AdminBundle\EventListener\AdminSessionBagListener;
use OpenDxp\Bundle\AdminBundle\Security\CsrfProtectionHandler;
use OpenDxp\TestFoundation\Container;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Twig\Environment;

function printedCsrfToken(): string
{
    return Container::get(Environment::class)->createTemplate('{{ csrfToken }}')->render();
}

function currentSession(): SessionInterface
{
    return Container::requestStack()->getSession();
}

function adminSession(): Session
{
    $session = new Session(new MockArraySessionStorage());
    Container::get(AdminSessionBagListener::class)->configure($session);

    return $session;
}

function startAdminRequest(): void
{
    $request = Request::create('/admin');
    $request->setSessionFactory(static fn () => Container::sessionFactory()->createSession());
    Container::get(AdminSessionBagListener::class)->configure($request->getSession());

    Container::requestStack()->push($request);
}

beforeEach(fn () => startAdminRequest());

it('prints the token of the current session', function () {
    $token = Container::get(CsrfProtectionHandler::class)->getCsrfToken(currentSession());

    $printed = printedCsrfToken();

    expect($printed)
        ->toBe($token)
        ->not->toBeEmpty();
});

it('prints the new token once it is regenerated, also after templates have been rendered', function () {
    $before = printedCsrfToken();

    Container::get(CsrfProtectionHandler::class)->regenerateCsrfToken(currentSession());

    expect(printedCsrfToken())->not->toBe($before);
});

it('prints an empty token in the frontend without starting a session', function () {
    $request = Request::create('/');
    $request->setSessionFactory(static fn () => Container::sessionFactory()->createSession());
    Container::requestStack()->push($request);
    $this->expectUserDeprecationMessageMatches('/"csrfToken" outside the admin is deprecated/');

    $printed = printedCsrfToken();

    expect($printed)
        ->toBe('')
        ->and($request->getSession()->isStarted())
        ->toBeFalse();
});

it('keeps the token of one session away from another', function () {
    $handler = Container::get(CsrfProtectionHandler::class);

    $first = $handler->getCsrfToken(adminSession());
    $second = $handler->getCsrfToken(adminSession());

    expect($first)->not->toBe($second);
});

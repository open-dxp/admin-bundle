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

namespace OpenDxp\Bundle\AdminBundle\Tests\Model\Handler\DataObject\DataObjectGridProxy;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy\DataObjectGridProxyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy\DataObjectGridProxyPayload;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\Unittest;
use OpenDxp\Model\User;
use OpenDxp\Security\User\User as UserProxy;
use OpenDxp\Tests\Support\Helper\OpenDxp;
use OpenDxp\Tests\Support\Test\ModelTestCase;
use OpenDxp\Tests\Support\Util\TestHelper;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class DataObjectGridProxyHandlerTest extends ModelTestCase
{
    private ?Unittest $object = null;

    private ?User $adminUser = null;

    protected function tearDown(): void
    {
        parent::tearDown();

        $this->getModule('\\' . OpenDxp::class)
            ->grabService(TokenStorageInterface::class)
            ->setToken(null);

        $this->object?->delete();
        $this->adminUser?->delete();
    }

    public function testUpdateSavesLocalizedFieldInRequestedGridLanguage(): void
    {
        $this->object = TestHelper::createEmptyObject();
        $this->object->setLinput('initial-en', 'en');
        $this->object->setLinput('initial-de', 'de');
        $this->object->save();

        $openDxpModule = $this->getModule('\\' . OpenDxp::class);

        $this->adminUser = new User();
        $this->adminUser->setName('grid-proxy-lang-test-' . uniqid());
        $this->adminUser->setAdmin(true);
        $this->adminUser->save();

        $tokenStorage = $openDxpModule->grabService(TokenStorageInterface::class);
        $tokenStorage->setToken(new PreAuthenticatedToken(new UserProxy($this->adminUser), 'opendxp_admin'));

        /** @var LocaleServiceInterface $localeService */
        $localeService = $openDxpModule->grabService(LocaleServiceInterface::class);

        // Simulate the ambient locale AdminAbstractAuthenticator primes on every
        // authenticated request: the admin user's interface language, "en" here -
        // deliberately different from the grid language used below.
        $localeService->setLocale('en');

        /** @var DataObjectGridProxyHandler $handler */
        $handler = $openDxpModule->grabService(DataObjectGridProxyHandler::class);

        $payload = new DataObjectGridProxyPayload(
            allParams: [
                'xaction' => 'update',
                'language' => 'de',
                'fields' => null,
                'data' => json_encode([
                    'id' => $this->object->getId(),
                    'linput' => 'from-grid-de',
                ], JSON_THROW_ON_ERROR),
            ],
            locale: 'en',
        );

        $handler($payload);

        $reloaded = Unittest::getById($this->object->getId(), ['force' => true]);

        self::assertSame('from-grid-de', $reloaded->getLinput('de'), 'Grid-language value must be saved under the requested grid language');
        self::assertSame('initial-en', $reloaded->getLinput('en'), 'Admin UI language value must stay untouched by a grid edit made in a different language');
    }
}

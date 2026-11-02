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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Grid;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy\DataObjectGridProxyHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy\DataObjectGridProxyPayload;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ExecuteBatch\ExecuteBatchHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\ExecuteBatch\ExecuteBatchPayload;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\TestProductFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Admin;
use OpenDxp\TestFoundation\Container;

beforeEach(function () {
    $this->object = TestProductFactory::new()
        ->withLocalizedValues('name', [
            'en' => 'initial-en',
            'de' => 'initial-de',
        ])
        ->create();

    $admin = UserFactory::new()
        ->admin()
        ->create();
    Admin::actingAs($admin);

    // The authenticator primes the locale with the language of the admin interface on every request. It differs
    // from the language of the grid on purpose.
    Container::get(LocaleServiceInterface::class)->setLocale('en');
});

it('saves a grid edit in the language of the grid, not of the admin interface', function () {
    Container::get(DataObjectGridProxyHandler::class)(new DataObjectGridProxyPayload(
        allParams: [
            'xaction' => 'update',
            'language' => 'de',
            'fields' => null,
            'data' => json_encode(
                [
                    'id' => $this->object->getId(),
                    'name' => 'from-grid-de',
                ],
                JSON_THROW_ON_ERROR,
            ),
        ],
        locale: 'en',
    ));

    expect(Elements::refresh($this->object))
        ->getName('de')->toBe('from-grid-de')
        ->getName('en')->toBe('initial-en');
});

it('saves a batch edit in the language of the grid, not of the admin interface', function () {
    Container::get(ExecuteBatchHandler::class)(new ExecuteBatchPayload(
        params: [
            'job' => $this->object->getId(),
            'name' => 'name',
            'value' => 'from-batch-de',
            'valueType' => 'primitive',
            'language' => 'de',
        ],
        locale: 'en',
        hasData: true,
    ));

    expect(Elements::refresh($this->object))
        ->getName('de')->toBe('from-batch-de')
        ->getName('en')->toBe('initial-en');
});

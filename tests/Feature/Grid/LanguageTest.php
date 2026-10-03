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
use OpenDxp\Bundle\AdminBundle\Tests\Factory\InheritanceFactory;
use OpenDxp\Localization\LocaleServiceInterface;
use OpenDxp\Model\DataObject\Inheritance;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Admin;
use OpenDxp\TestFoundation\Container;

beforeEach(function () {
    $this->object = InheritanceFactory::new()->unsaved()->create();
    $this->object->setInput('initial-en', 'en');
    $this->object->setInput('initial-de', 'de');
    $this->object->save();

    Admin::actingAs(UserFactory::new()->admin()->create());

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
            'data' => json_encode(['id' => $this->object->getId(), 'input' => 'from-grid-de'], JSON_THROW_ON_ERROR),
        ],
        locale: 'en',
    ));

    expect(Inheritance::getById($this->object->getId(), ['force' => true]))
        ->getInput('de')->toBe('from-grid-de')
        ->getInput('en')->toBe('initial-en');
});

it('saves a batch edit in the language of the grid, not of the admin interface', function () {
    Container::get(ExecuteBatchHandler::class)(new ExecuteBatchPayload(
        params: [
            'job' => $this->object->getId(),
            'name' => 'input',
            'value' => 'from-batch-de',
            'valueType' => 'primitive',
            'language' => 'de',
        ],
        locale: 'en',
        hasData: true,
    ));

    expect(Inheritance::getById($this->object->getId(), ['force' => true]))
        ->getInput('de')->toBe('from-batch-de')
        ->getInput('en')->toBe('initial-en');
});

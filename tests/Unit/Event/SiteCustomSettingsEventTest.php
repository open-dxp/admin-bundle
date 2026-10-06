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

namespace OpenDxp\Bundle\AdminBundle\Tests\Unit\Event;

use OpenDxp\Bundle\AdminBundle\Dto\SiteCustomSettings\CheckboxNodeConfig;
use OpenDxp\Bundle\AdminBundle\Dto\SiteCustomSettings\DropdownNodeConfig;
use OpenDxp\Bundle\AdminBundle\Dto\SiteCustomSettings\InputNodeConfig;
use OpenDxp\Bundle\AdminBundle\Dto\SiteCustomSettings\TextNodeConfig;
use OpenDxp\Bundle\AdminBundle\Enum\SiteCustomConfigNodeType;
use OpenDxp\Bundle\AdminBundle\Event\SiteCustomSettingsEvent;
use OpenDxp\Model\Site;

beforeEach(function () {
    $this->event = new SiteCustomSettingsEvent(new Site());
});

it('groups the config nodes by their scope', function () {
    $this->event->addConfigNode(new InputNodeConfig(), 'seo', 'title', 'SEO Title');
    $this->event->addConfigNode(new CheckboxNodeConfig(), 'seo', 'noindex', 'No Index');
    $this->event->addConfigNode(new DropdownNodeConfig(), 'i18n', 'zone', 'Zone');
    $this->event->addConfigNode(new TextNodeConfig(), 'app', 'description', 'Description');

    expect($this->event->getConfigNodes())
        ->seo->toHaveCount(2)
        ->i18n->toHaveCount(1)
        ->app->toHaveCount(1);
});

it('describes a config node by its type, name, label and config', function () {
    $store = [
        [
            'label' => 'A',
            'value' => 'a',
        ],
    ];

    $this->event->addConfigNode(
        new DropdownNodeConfig(store: $store, required: true),
        'app',
        'my_field',
        'My Field',
    );

    expect($this->event->getConfigNodes()['app'][0])
        ->type->toBe(SiteCustomConfigNodeType::DROPDOWN->value)
        ->name->toBe('my_field')
        ->label->toBe('My Field')
        ->config->required->toBeTrue()
        ->config->store->toBe($store);
});

it('keeps the config nodes of one scope in the order they were added', function () {
    $this->event->addConfigNode(new InputNodeConfig(), 'app', 'first', 'First');
    $this->event->addConfigNode(new InputNodeConfig(), 'app', 'second', 'Second');
    $this->event->addConfigNode(new InputNodeConfig(), 'app', 'third', 'Third');

    expect(array_column($this->event->getConfigNodes()['app'], 'name'))->toBe(['first', 'second', 'third']);
});

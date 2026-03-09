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
use OpenDxp\Bundle\AdminBundle\Tests\Support\Test\UnitTestCase;

class SiteCustomSettingsEventTest extends UnitTestCase
{
    public function testInitialConfigNodesAreEmpty(): void
    {
        $event = new SiteCustomSettingsEvent($this->createSite());

        self::assertSame([], $event->getConfigNodes());
    }

    public function testGetSiteReturnsSite(): void
    {
        $site  = $this->createSite();
        $event = new SiteCustomSettingsEvent($site);

        self::assertSame($site, $event->getSite());
    }

    public function testAddConfigNodeGroupsByScope(): void
    {
        $event = new SiteCustomSettingsEvent($this->createSite());

        $event->addConfigNode(new InputNodeConfig(),    'seo',  'title',       'SEO Title');
        $event->addConfigNode(new CheckboxNodeConfig(), 'seo',  'noindex',     'No Index');
        $event->addConfigNode(new DropdownNodeConfig(), 'i18n', 'zone',        'Zone');
        $event->addConfigNode(new TextNodeConfig(),     'app',  'description', 'Description');

        $nodes = $event->getConfigNodes();

        self::assertCount(2, $nodes['seo']);
        self::assertCount(1, $nodes['i18n']);
        self::assertCount(1, $nodes['app']);
    }

    public function testAddConfigNodeBuildsCorrectStructure(): void
    {
        $event = new SiteCustomSettingsEvent($this->createSite());

        $event->addConfigNode(
            new DropdownNodeConfig(
                store: [['label' => 'A', 'value' => 'a']],
                required: true,
            ),
            'app',
            'my_field',
            'My Field',
        );

        $node = $event->getConfigNodes()['app'][0];

        self::assertSame(SiteCustomConfigNodeType::DROPDOWN->value, $node['type']);
        self::assertSame('my_field', $node['name']);
        self::assertSame('My Field', $node['label']);
        self::assertTrue($node['config']['required']);
        self::assertSame([['label' => 'A', 'value' => 'a']], $node['config']['store']);
    }

    public function testMultipleNodesInSameScopeAreAppended(): void
    {
        $event = new SiteCustomSettingsEvent($this->createSite());

        $event->addConfigNode(new InputNodeConfig(), 'app', 'first',  'First');
        $event->addConfigNode(new InputNodeConfig(), 'app', 'second', 'Second');
        $event->addConfigNode(new InputNodeConfig(), 'app', 'third',  'Third');

        $nodes = $event->getConfigNodes()['app'];

        self::assertCount(3, $nodes);
        self::assertSame('first',  $nodes[0]['name']);
        self::assertSame('second', $nodes[1]['name']);
        self::assertSame('third',  $nodes[2]['name']);
    }

    public function testEachDtoReturnsCorrectType(): void
    {
        self::assertSame(SiteCustomConfigNodeType::INPUT,    (new InputNodeConfig())->getType());
        self::assertSame(SiteCustomConfigNodeType::TEXT,     (new TextNodeConfig())->getType());
        self::assertSame(SiteCustomConfigNodeType::CHECKBOX, (new CheckboxNodeConfig())->getType());
        self::assertSame(SiteCustomConfigNodeType::DROPDOWN, (new DropdownNodeConfig())->getType());
    }
}
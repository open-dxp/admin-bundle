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

        $event->addConfigNode(SiteCustomConfigNodeType::INPUT,    'seo',  'title',       'SEO Title',   []);
        $event->addConfigNode(SiteCustomConfigNodeType::CHECKBOX, 'seo',  'noindex',     'No Index',    []);
        $event->addConfigNode(SiteCustomConfigNodeType::DROPDOWN, 'i18n', 'zone',        'Zone',        ['store' => []]);
        $event->addConfigNode(SiteCustomConfigNodeType::TEXT,     'app',  'description', 'Description', []);

        $nodes = $event->getConfigNodes();

        self::assertCount(2, $nodes['seo']);
        self::assertCount(1, $nodes['i18n']);
        self::assertCount(1, $nodes['app']);
    }

    public function testAddConfigNodeBuildsCorrectStructure(): void
    {
        $event = new SiteCustomSettingsEvent($this->createSite());

        $event->addConfigNode(
            SiteCustomConfigNodeType::DROPDOWN,
            'app',
            'my_field',
            'My Field',
            ['required' => true, 'store' => [['label' => 'A', 'value' => 'a']]]
        );

        $node = $event->getConfigNodes()['app'][0];

        self::assertSame(SiteCustomConfigNodeType::DROPDOWN->value, $node['type']);
        self::assertSame('my_field', $node['name']);
        self::assertSame('My Field', $node['label']);
        self::assertSame(['required' => true, 'store' => [['label' => 'A', 'value' => 'a']]], $node['config']);
    }

    public function testMultipleNodesInSameScopeAreAppended(): void
    {
        $event = new SiteCustomSettingsEvent($this->createSite());

        $event->addConfigNode(SiteCustomConfigNodeType::INPUT, 'app', 'first',  'First',  []);
        $event->addConfigNode(SiteCustomConfigNodeType::INPUT, 'app', 'second', 'Second', []);
        $event->addConfigNode(SiteCustomConfigNodeType::INPUT, 'app', 'third',  'Third',  []);

        self::assertCount(3, $event->getConfigNodes()['app']);
        self::assertSame('first',  $event->getConfigNodes()['app'][0]['name']);
        self::assertSame('second', $event->getConfigNodes()['app'][1]['name']);
        self::assertSame('third',  $event->getConfigNodes()['app'][2]['name']);
    }

    public function testNodeTypeValuesMatchExpectedExtJsTypes(): void
    {
        self::assertSame('input',    SiteCustomConfigNodeType::INPUT->value);
        self::assertSame('text',     SiteCustomConfigNodeType::TEXT->value);
        self::assertSame('checkbox', SiteCustomConfigNodeType::CHECKBOX->value);
        self::assertSame('combobox', SiteCustomConfigNodeType::DROPDOWN->value);
    }
}
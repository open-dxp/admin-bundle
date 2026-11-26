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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Classificationstore;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetGroups\GetGroupsHandler;
use OpenDxp\Bundle\AdminBundle\Handler\DataObject\Classificationstore\GetGroups\GetGroupsPayload;
use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;
use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;
use OpenDxp\Test\Factory\TestProductFactory;
use OpenDxp\TestFoundation\Container;

it('lists only allowed groups of classification store field', function () {
    $product = TestProductFactory::new()->create();
    $group = GroupConfig::getByName('values', StoreConfig::getByName('TestSpecifications')->getId());
    $product->getClass()->getFieldDefinition('specifications')->setAllowedGroupIds(
        sprintf('%d)OR(1=1', $group->getId()),
    );

    $result = Container::get(GetGroupsHandler::class)(new GetGroupsPayload(
        oid: $product->getId(),
        fieldname: 'specifications',
    ));

    expect(array_column($result->data, 'id'))->toBe([$group->getId()]);
});

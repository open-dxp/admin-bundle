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

use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Tests\Factory\SluggableFactory;
use OpenDxp\Model\DataObject\Data\UrlSlug;
use OpenDxp\Model\DataObject\Sluggable;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;

it('filters the grid by a URL slug', function () {
    $shoes = SluggableFactory::new()->create(['slug' => [new UrlSlug('/shoes')]]);
    SluggableFactory::new()->create(['slug' => [new UrlSlug('/shirts')]]);

    $listing = Container::get(GridHelperService::class)->prepareListingForGrid(
        [
            'folderId' => $shoes->getParentId(),
            'classId' => $shoes->getClassId(),
            'filter' => json_encode([[
                'property' => 'slug',
                'type' => 'string',
                'operator' => 'like',
                'value' => 'sho',
            ]]),
        ],
        'en',
        UserFactory::new()->admin()->create(),
    );

    expect(array_map(static fn (Sluggable $found) => $found->getId(), $listing->load()))->toBe([$shoes->getId()]);
});

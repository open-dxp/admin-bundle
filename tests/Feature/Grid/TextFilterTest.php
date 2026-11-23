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
use OpenDxp\Model\Asset;
use OpenDxp\Model\DataObject\TestArticle;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\TestArticleFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Container;

it('matches an underscore literally in object grid key filters', function () {
    $match = TestArticleFactory::new()->create(['key' => 'a_b']);
    TestArticleFactory::new()->create(['key' => 'axb', 'parentId' => $match->getParentId()]);

    $listing = Container::get(GridHelperService::class)->prepareListingForGrid(
        [
            'folderId' => $match->getParentId(),
            'classId' => $match->getClassId(),
            'filter' => json_encode([['property' => 'key', 'type' => 'string', 'operator' => 'like', 'value' => 'a_b']]),
        ],
        'en',
        UserFactory::new()->admin()->create(),
    );

    expect(array_map(static fn (TestArticle $found) => $found->getId(), $listing->load()))->toBe([$match->getId()]);
});

it('matches an underscore literally in asset grid filename filters', function () {
    $folder = AssetFolderFactory::createOne();
    $match = AssetImageFactory::createOne(['parentId' => $folder->getId(), 'filename' => 'a_b.jpg']);
    AssetImageFactory::createOne(['parentId' => $folder->getId(), 'filename' => 'axb.jpg']);

    $listing = Container::get(GridHelperService::class)->prepareAssetListingForGrid(
        [
            'folderId' => $folder->getId(),
            'filter' => json_encode([['property' => 'filename~system', 'type' => 'string', 'operator' => 'like', 'value' => 'a_b']]),
        ],
        UserFactory::new()->admin()->create(),
    );

    expect(array_map(static fn (Asset $found) => $found->getId(), $listing->load()))->toBe([$match->getId()]);
});

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
use OpenDxp\Bundle\AdminBundle\Helper\GridHelperService;
use OpenDxp\Bundle\AdminBundle\Mapper\GridData\DataObject as GridData;
use OpenDxp\Model\DataObject\ClassDefinition;
use OpenDxp\Model\DataObject\Classificationstore\GroupConfig;
use OpenDxp\Model\DataObject\Classificationstore\KeyConfig;
use OpenDxp\Model\DataObject\Classificationstore\StoreConfig;
use OpenDxp\Model\DataObject\Data\QuantityValue;
use OpenDxp\Model\DataObject\QuantityValue\Unit;
use OpenDxp\Model\DataObject\TestProduct;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\TestProductFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Admin;
use OpenDxp\TestFoundation\Container;

function valuesGroup(): GroupConfig
{
    return GroupConfig::getByName('values', StoreConfig::getByName('TestSpecifications')->getId());
}

function specificationsColumn(string $key): string
{
    return sprintf(
        '~classificationstore~specifications~%d-%d',
        valuesGroup()->getId(),
        KeyConfig::getByName($key, valuesGroup()->getStoreId())->getId(),
    );
}

function createUnit(string $abbreviation): Unit
{
    $unit = new Unit();
    $unit->setAbbreviation($abbreviation);
    $unit->save();

    return $unit;
}

function getSpecificationsValue(TestProduct $object, string $key): mixed
{
    return Elements::refresh($object)->getSpecifications()->getLocalizedKeyValue(
        valuesGroup()->getId(),
        KeyConfig::getByName($key, valuesGroup()->getStoreId())->getId(),
    );
}

function getGridObjectIds(TestProduct $object, array $request): array
{
    $listing = Container::get(GridHelperService::class)->prepareListingForGrid(
        [
            'folderId' => $object->getParentId(),
            'classId' => $object->getClassId(),
            ...$request,
        ],
        'en',
        UserFactory::new()->admin()->create(),
    );

    return array_map(static fn (TestProduct $found) => $found->getId(), $listing->load());
}

beforeEach(function () {
    Admin::actingAs(UserFactory::new()->admin()->create());
});

it('shows the value of a classification store key its object inherits', function () {
    $parent = TestProductFactory::new()
        ->withClassificationValues('specifications', valuesGroup(), ['input' => 'red'])
        ->create();
    $child = TestProductFactory::new()
        ->withParent($parent)
        ->create();

    $row = GridData::getData($child, [specificationsColumn('input')], 'en');

    expect($row[specificationsColumn('input')])
        ->toBe('red')
        ->and($row['inheritedFields'][specificationsColumn('input')])
        ->toBe([
            'inherited' => true,
            'objectid' => $parent->getId(),
        ]);
});

it('filters the grid by a classification store key', function (string $key, callable $values) {
    [$matchingValue, $otherValues, $filters] = $values();
    $matching = TestProductFactory::new()
        ->withClassificationValues('specifications', valuesGroup(), [$key => $matchingValue])
        ->create();

    foreach ($otherValues as $otherValue) {
        TestProductFactory::new()
            ->withClassificationValues('specifications', valuesGroup(), [$key => $otherValue])
            ->create();
    }

    $ids = getGridObjectIds($matching, [
        'filter' => json_encode(array_map(
            static fn (array $filter) => ['property' => specificationsColumn($key), ...$filter],
            $filters,
        )),
    ]);

    expect($ids)->toBe([$matching->getId()]);
})->with([
    'a text' => [
        'input',
        fn () => [
            'red',
            ['blue'],
            [['type' => 'string', 'operator' => 'like', 'value' => 're']],
        ],
    ],
    'a number between two bounds' => [
        'numeric',
        fn () => [
            10,
            [3, 20],
            [
                ['type' => 'numeric', 'operator' => 'gt', 'value' => 5],
                ['type' => 'numeric', 'operator' => 'lt', 'value' => 15],
            ],
        ],
    ],
    'one of several selected values' => [
        'multiselect',
        fn () => [
            ['red', 'green'],
            [['blue']],
            [['type' => 'list', 'operator' => 'eq', 'value' => ['green', 'yellow']]],
        ],
    ],
    'a quantity value in its unit' => [
        'quantityValue',
        function () {
            $centimetre = createUnit('cm');

            return [
                new QuantityValue(10, $centimetre),
                [new QuantityValue(10, createUnit('kg'))],
                [['type' => 'quantityValue', 'operator' => 'eq', 'value' => [[10, $centimetre->getId()]]]],
            ];
        },
    ],
]);

it('sorts the grid by a classification store key', function () {
    $second = TestProductFactory::new()
        ->withClassificationValues('specifications', valuesGroup(), ['input' => 'b'])
        ->create();
    $first = TestProductFactory::new()
        ->withClassificationValues('specifications', valuesGroup(), ['input' => 'a'])
        ->create();

    $ids = getGridObjectIds($first, [
        'sort' => json_encode([['property' => specificationsColumn('input'), 'direction' => 'ASC']]),
    ]);

    expect($ids)->toBe([$first->getId(), $second->getId()]);
});

it('saves a grid edit of a classification store key', function () {
    $object = TestProductFactory::new()->create();

    Container::get(DataObjectGridProxyHandler::class)(new DataObjectGridProxyPayload(
        allParams: [
            'xaction' => 'update',
            'language' => 'en',
            'fields' => null,
            'data' => json_encode(
                [
                    'id' => $object->getId(),
                    specificationsColumn('input') => 'from-grid',
                ],
                JSON_THROW_ON_ERROR,
            ),
        ],
        locale: 'en',
    ));

    expect(getSpecificationsValue($object, 'input'))->toBe('from-grid');
});

it('saves a batch edit of a classification store key', function () {
    $object = TestProductFactory::new()->create();

    Container::get(ExecuteBatchHandler::class)(new ExecuteBatchPayload(
        params: [
            'job' => $object->getId(),
            'name' => specificationsColumn('input'),
            'value' => 'from-batch',
            'valueType' => 'primitive',
            'language' => 'en',
        ],
        locale: 'en',
        hasData: true,
    ));

    expect(getSpecificationsValue($object, 'input'))->toBe('from-batch');
});

it('keeps the join of a grid column when a slug column is filtered too', function () {
    $listing = new TestProduct\Listing();

    Container::get(GridHelperService::class)->addGridJoins(
        $listing,
        ClassDefinition::getByName('TestProduct'),
        [['fieldname' => 'slug']],
        ['gridcolumn_input' => specificationsColumn('input')],
        ['gridcolumn_input' => "`gridcolumn_input` LIKE '%t%'"],
        'en',
    );

    expect($listing->getDao()->getQueryBuilder()->getSQL())->toContain(
        'LEFT JOIN object_url_slugs slug',
        'LEFT JOIN object_classificationstore_data_TestProduct cskey_gridcolumn_input',
        "HAVING (`gridcolumn_input` LIKE '%t%')",
    );
});

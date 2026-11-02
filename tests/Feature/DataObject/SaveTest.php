<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\DataObject;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Test\Elements;
use OpenDxp\Test\Factory\TestGuideFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

it('saves the changes on the published object when they do not fit the draft', function () {
    $admin = UserFactory::new()
        ->admin()
        ->create();
    $object = TestGuideFactory::new()
        ->withSections('first', 'second')
        ->create();
    $changes = [
        'sections' => [
            [
                'oIndex' => 1,
                'data' => ['localizedfields' => ['de' => ['heading' => 'changed']]],
            ],
        ],
    ];

    $object->setSections([$object->getSections()[1]]);
    $object->saveVersion();
    RuntimeCache::clear();

    Browser::actingAs($admin)
        ->put('/admin/object/save?task=publish', [
            'body' => [
                'id' => $object->getId(),
                'data' => json_encode($changes),
            ],
        ]);
    $items = Elements::refresh($object)->getSections();

    expect($items)
        ->toHaveCount(1)
        ->and($items[0]['localizedfields']->getData()->getLocalizedValue('heading', 'de'))
        ->toBe('changed');
});

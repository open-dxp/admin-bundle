<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Element;

use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes\GetNoteTypesHandler;
use OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes\GetNoteTypesPayload;
use OpenDxp\TestFoundation\Container;

it('offers the note types of the element type it is asked about', function (string $ctype, array $types) {
    $result = Container::get(GetNoteTypesHandler::class)(new GetNoteTypesPayload($ctype));

    expect(array_column($result->noteTypes, 'name'))->toBe($types);
})->with([
    'a document' => [
        'document',
        ['', 'seo'],
    ],
    'an object' => [
        'object',
        ['', 'stock'],
    ],
]);

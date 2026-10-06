<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Document;

use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\SavePage\SavePagePayload;
use OpenDxp\Bundle\AdminBundle\Mapper\Document\DocumentPayloadMapper;
use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;

/**
 * The editmode sends only the editables a page does not inherit. Without editables in the request, it sends null.
 *
 * @param array<string, array{type: string, data: mixed}>|null $editables
 */
function publishPage(Page $page, ?array $editables): Page
{
    $page = Page::getById($page->getId(), ['force' => true]);
    $payload = new SavePagePayload(
        id: $page->getId(),
        task: 'publish',
        settings: null,
        editables: $editables,
        appendEditables: false,
        properties: null,
        scheduler: null,
        missingRequiredEditable: null,
    );

    Container::get(DocumentPayloadMapper::class)->applyPagePayload($payload, $page, 'publish');
    $page->save();

    return Page::getById($page->getId(), ['force' => true]);
}

beforeEach(function () {
    $this->main = DocumentPageFactory::new()
        ->withEditables(['headline' => (new Input())->setDataFromResource('from the main document')])
        ->create();
    $this->page = DocumentPageFactory::new()
        ->withEditables(['headline' => (new Input())->setDataFromResource('own')])
        ->withContentMainDocument($this->main)
        ->create();
});

it('drops the own editables when the editmode sends none because all are inherited', function () {
    $page = publishPage($this->page, []);

    expect($page->getEditables())
        ->toBeEmpty()
        ->and($page->getEditable('headline'))
        ->getValue()->toBe('from the main document')
        ->getInherited()->toBeTrue();
});

it('keeps only the editables the editmode sends', function () {
    $page = publishPage($this->page, [
        'subline' => [
            'type' => 'input',
            'data' => 'own subline',
        ],
    ]);

    expect(array_keys($page->getEditables()))
        ->toBe(['subline'])
        ->and($page->getEditable('headline'))
        ->getValue()->toBe('from the main document');
});

it('keeps the own editables when the save sends no editables', function () {
    $page = publishPage($this->page, null);

    expect(array_keys($page->getEditables()))
        ->toBe(['headline'])
        ->and($page->getEditable('headline'))
        ->getValue()->toBe('own');
});

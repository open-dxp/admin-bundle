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

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Document;

use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\SavePage\SavePagePayload;
use OpenDxp\Bundle\AdminBundle\Mapper\Document\DocumentPayloadMapper;
use OpenDxp\Model\Document\Editable\Input;
use OpenDxp\Model\Document\Page;
use OpenDxp\Test\Factory\DocumentPageFactory;
use OpenDxp\TestFoundation\Container;

/**
 * Saves the page like the editmode: with its own (non-inherited) editables, or null when none were sent.
 *
 * @param array<string, array{type: string, data: mixed}>|null $editables
 */
function savePage(Page $page, ?array $editables): Page
{
    $page = Page::getById($page->getId(), ['force' => true]);

    Container::get(DocumentPayloadMapper::class)->applyPagePayload(new SavePagePayload(
        id: $page->getId(),
        task: 'publish',
        settings: null,
        editables: $editables,
        appendEditables: false,
        properties: null,
        scheduler: null,
        missingRequiredEditable: null,
    ), $page, 'publish');
    $page->save();

    return Page::getById($page->getId(), ['force' => true]);
}

beforeEach(function () {
    $this->main = DocumentPageFactory::new()
        ->withEditables(['headline' => (new Input())->setDataFromResource('from the main document')])
        ->create();

    // the page already had own editables before it got a content main document
    $this->page = DocumentPageFactory::new()
        ->withEditables(['headline' => (new Input())->setDataFromResource('own')])
        ->withContentMainDocument($this->main)
        ->create();
});

it('drops the own editables when the editmode sends none because all are inherited', function () {
    $page = savePage($this->page, []);

    expect($page->getEditables())->toBeEmpty()
        ->and($page->getEditable('headline')->getValue())->toBe('from the main document')
        ->and($page->getEditable('headline')->getInherited())->toBeTrue();
});

it('keeps only the editables the editmode sends', function () {
    $page = savePage($this->page, [
        'subline' => ['type' => 'input', 'data' => 'own subline'],
    ]);

    expect(array_keys($page->getEditables()))->toBe(['subline'])
        ->and($page->getEditable('headline')->getValue())->toBe('from the main document');
});

it('keeps the own editables when the save sends no editables', function () {
    $page = savePage($this->page, null);

    expect(array_keys($page->getEditables()))->toBe(['headline'])
        ->and($page->getEditable('headline')->getValue())->toBe('own');
});

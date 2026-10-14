<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Tree;

use OpenDxp\Bundle\AdminBundle\Tests\Value\ElementKind;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;

/**
 * Creates a folder with five children that match the search "book" and one that does not.
 *
 * @return array{ElementInterface, list<ElementInterface>} the folder and the matching children in tree order
 */
function folderWithBooks(ElementKind $kind): array
{
    $folder = $kind->folder('library')
        ->create();

    $books = [];
    foreach (range(1, 5) as $number) {
        $books[] = $kind->leaf(sprintf('book-%d', $number))
            ->withParent($folder)
            ->create();
    }

    $kind->leaf('shelf')
        ->withParent($folder)
        ->create();

    return [$folder, $books];
}

/**
 * Searches the children of the folder as the search field of the folder bar does.
 *
 * @return array{nodes: list<array{path: string}>, total: int} the page of the response
 */
function searchFolder(ElementKind $kind, ElementInterface $folder, string $filter, int $start, int $limit): array
{
    $content = Browser::actingAs(UserFactory::new()->admin()->create())
        ->visit(sprintf(
            '/admin/%s/tree-get-children-by-id-paginated?node=%d&filter=%s&inSearch=1&start=%d&limit=%d&view=0',
            $kind->name,
            $folder->getId(),
            $filter,
            $start,
            $limit,
        ))
        ->content();

    return json_decode($content, true, flags: JSON_THROW_ON_ERROR);
}

// The folder bar pages through search results like through children. It sends start and limit with the filter.
it('returns the requested page of the search results', function (ElementKind $kind) {
    [$folder, $books] = folderWithBooks($kind);

    $page = searchFolder($kind, $folder, 'book', start: 2, limit: 2);

    expect(array_column($page['nodes'], 'path'))
        ->toBe([$books[2]->getRealFullPath(), $books[3]->getRealFullPath()]);
})->with([
    'assets' => fn () => ElementKind::asset(),
    'documents' => fn () => ElementKind::document(),
    'objects' => fn () => ElementKind::object(),
]);

// The pager of the folder bar counts its pages from the total of the response.
it('counts the search results as the total', function (ElementKind $kind) {
    [$folder] = folderWithBooks($kind);

    $page = searchFolder($kind, $folder, 'book', start: 0, limit: 2);

    expect($page['total'])->toBe(5);
})->with([
    'assets' => fn () => ElementKind::asset(),
    'documents' => fn () => ElementKind::document(),
    'objects' => fn () => ElementKind::object(),
]);

// An editor searches a large folder by any part of a key, not only by its start.
it('finds the children that contain the search anywhere in their key', function (ElementKind $kind) {
    [$folder, $books] = folderWithBooks($kind);

    $page = searchFolder($kind, $folder, 'ok-3', start: 0, limit: 10);

    expect(array_column($page['nodes'], 'path'))->toBe([$books[2]->getRealFullPath()]);
})->with([
    'assets' => fn () => ElementKind::asset(),
    'documents' => fn () => ElementKind::document(),
    'objects' => fn () => ElementKind::object(),
]);

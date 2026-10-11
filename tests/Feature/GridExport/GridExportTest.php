<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use DateTimeImmutable;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportFormat;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportHeader;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSettings;
use OpenDxp\Bundle\AdminBundle\Test\GridExport\GridExports;
use OpenDxp\Test\Factory\UserFactory;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
});

it('writes the rows of every batch in their order', function () {
    $file = GridExports::export($this->admin, 'mock');

    expect($file)
        ->getColumn('ID')
        ->toBe(['1', '2', '3']);
});

it('writes the titles of the columns the way the header asks for', function (GridExportHeader $header, array $titles) {
    $file = GridExports::export($this->admin, 'mock', settings: new GridExportSettings(header: $header));

    expect($file)
        ->getHeader()
        ->toBe($titles);
})->with([
    'the labels' => [GridExportHeader::LABEL, ['ID', 'Name', 'Price', 'Active', 'Released', 'Updated']],
    'the keys' => [GridExportHeader::KEY, ['id', 'name', 'price', 'active', 'released', 'updated']],
    'no header' => [GridExportHeader::NONE, ['1', 'Ada', '9.5', 'Yes', '2024-01-15', '2024-01-15 10:30:00']],
]);

it('starts a CSV export with the byte order mark that Excel needs for UTF-8', function () {
    $file = GridExports::export($this->admin, 'mock');

    expect($file)
        ->content
        ->toStartWith("\xEF\xBB\xBF");
});

it('separates the values of a CSV export with the delimiter of the settings', function () {
    $file = GridExports::export($this->admin, 'mock', settings: new GridExportSettings(delimiter: ','));

    expect($file->getRows()[1])->toBe(['1', 'Ada', '9.5', 'Yes', '2024-01-15', '2024-01-15 10:30:00']);
});

it('escapes a value that looks like a formula in a CSV export', function () {
    $file = GridExports::export($this->admin, 'mock');

    expect($file)
        ->getColumn('Name')
        ->toBe(['Ada', '\'=HYPERLINK("https://example.com")', 'Jürgen']);
});

it('writes every value of an XLSX export with its type', function () {
    $file = GridExports::export(
        $this->admin,
        'mock',
        settings: new GridExportSettings(format: GridExportFormat::XLSX),
    );

    expect($file->getRows()[1])->toEqual([
        1,
        'Ada',
        9.5,
        true,
        new DateTimeImmutable('2024-01-15'),
        new DateTimeImmutable('2024-01-15 10:30:00'),
    ]);
});

it('writes a value that looks like a formula as text into an XLSX export', function () {
    $file = GridExports::export(
        $this->admin,
        'mock',
        settings: new GridExportSettings(format: GridExportFormat::XLSX),
    );

    expect($file)
        ->getWorksheet()
        ->not->toContain('<f>')
        ->and($file)
        ->getColumn('Name')
        ->toBe(['Ada', '=HYPERLINK("https://example.com")', 'Jürgen']);
});

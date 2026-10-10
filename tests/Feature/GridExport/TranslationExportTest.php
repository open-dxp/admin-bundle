<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\GridExport;

use OpenDxp\Model\Translation;
use OpenDxp\Test\Factory\TranslationFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\Tool;

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->language = Tool::getValidLanguages()[0];
});

it('titles the columns with the key and the languages, as the import expects', function () {
    $file = exportThroughAdmin($this->admin, 'translations');

    expect($file)
        ->getHeader()
        ->toBe(['key', ...Tool::getValidLanguages()]);
});

it('exports the translations that the search of the grid finds', function () {
    TranslationFactory::new()
        ->withTranslations([$this->language => 'Found'])
        ->create(['key' => 'export.found']);
    TranslationFactory::new()
        ->withTranslations([$this->language => 'Ignored'])
        ->create(['key' => 'other.ignored']);

    $file = exportThroughAdmin($this->admin, 'translations', parameters: ['searchString' => 'export.']);

    expect($file)
        ->getColumn('key')
        ->toBe(['export.found']);
});

it('exports a translation that the import reads back unchanged', function (string $text) {
    TranslationFactory::new()
        ->withTranslations([$this->language => $text])
        ->create(['key' => 'export.round-trip']);

    $file = exportThroughAdmin($this->admin, 'translations', parameters: ['searchString' => 'export.round-trip'])
        ->createTemporaryFile();

    $translation = Translation::getByKey('export.round-trip');
    $translation->addTranslation($this->language, 'changed');
    $translation->save();

    Translation::importTranslationsFromFile($file);

    expect(Translation::getByKey('export.round-trip'))
        ->getTranslation($this->language)
        ->toBe($text);
})->with([
    'a quote and a line break' => "He said \"hello\"\nand left.",
    'a formula' => '=1+1',
]);

it('exports the admin translations as a source of their own', function () {
    TranslationFactory::new()
        ->inAdminDomain()
        ->withTranslations(['en' => 'Admin'])
        ->create(['key' => 'export.admin']);

    $file = exportThroughAdmin($this->admin, 'admin-translations', parameters: ['searchString' => 'export.admin']);

    expect($file)
        ->getColumn('key')
        ->toBe(['export.admin']);
});

it('refuses the admin domain to the source of the website translations', function () {
    requestGridExport($this->admin, 'translations', parameters: ['domain' => Translation::DOMAIN_ADMIN])
        ->assertStatus(403);
});

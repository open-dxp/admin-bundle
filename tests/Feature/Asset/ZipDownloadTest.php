<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Tests\Feature\Asset;

use OpenDxp\Bundle\AdminBundle\Tests\Value\ZipDownload;
use OpenDxp\Model\Asset;
use OpenDxp\Model\Asset\Image\Thumbnail\Config;
use OpenDxp\Model\User;
use OpenDxp\Test\Factory\AssetDocumentFactory;
use OpenDxp\Test\Factory\AssetFolderFactory;
use OpenDxp\Test\Factory\AssetImageFactory;
use OpenDxp\Test\Factory\ThumbnailConfigFactory;
use OpenDxp\Test\Factory\UserFactory;
use OpenDxp\TestFoundation\Browser;
use ZipArchive;

/**
 * Runs the ZIP download the way the admin does. It asks for the jobs, runs each of them and fetches the archive.
 *
 * @param array<string, int|string> $query
 */
function zipDownload(User $user, array $query): ZipDownload
{
    $browser = Browser::actingAs($user);

    $jobs = json_decode(
        $browser->visit(sprintf('/admin/asset/download-as-zip-jobs?%s', http_build_query($query)))->content(),
        true,
        flags: JSON_THROW_ON_ERROR,
    );
    foreach ($jobs['jobs'] as [$job]) {
        $browser->visit(sprintf('%s?%s', $job['url'], http_build_query($job['params'])));
    }

    $archive = $browser->visit(sprintf(
        '/admin/asset/download-as-zip?%s',
        http_build_query([
            'id' => $query['id'],
            'jobId' => $jobs['jobId'],
            'thumbnail' => $query['thumbnail'] ?? null,
        ]),
    ));

    // content() trims the body, and with it the zero bytes an archive ends with.
    $file = tempnam(sys_get_temp_dir(), 'zip-download-');
    file_put_contents($file, $archive->client()->getInternalResponse()->getContent());

    $zip = new ZipArchive();
    $zip->open($file);
    $entries = [];
    for ($index = 0; $index < $zip->numFiles; $index++) {
        $entries[$zip->getNameIndex($index)] = $zip->getFromIndex($index);
    }
    $zip->close();
    unlink($file);

    $disposition = (string) $archive->client()->getResponse()->headers->get('Content-Disposition');
    preg_match('/filename="?([^";]+)"?/', $disposition, $filename);

    return new ZipDownload($filename[1] ?? '', $entries);
}

function zipOfOriginals(User $user, Asset\Folder $folder): ZipDownload
{
    return zipDownload($user, ['id' => $folder->getId()]);
}

/**
 * @param list<int> $selectedIds
 */
function zipOfThumbnails(User $user, Asset\Folder $folder, Config $thumbnail, array $selectedIds): ZipDownload
{
    return zipDownload($user, array_filter([
        'id' => $folder->getId(),
        'selectedIds' => implode(',', $selectedIds),
        'thumbnail' => $thumbnail->getName(),
    ]));
}

/**
 * @param list<int> $selectedIds
 */
function imageCount(User $user, Asset\Folder $folder, array $selectedIds): int
{
    $query = http_build_query(array_filter([
        'id' => $folder->getId(),
        'selectedIds' => implode(',', $selectedIds),
    ]));
    $browser = Browser::actingAs($user)
        ->visit(sprintf('/admin/asset/download-as-zip-image-count?%s', $query));

    return json_decode($browser->content(), true, flags: JSON_THROW_ON_ERROR)['count'];
}

/**
 * @return list<string>
 */
function pathsIn(Asset\Folder $folder, string ...$paths): array
{
    return array_map(static fn (string $path): string => sprintf('%s/%s', $folder->getFilename(), $path), $paths);
}

beforeEach(function () {
    $this->admin = UserFactory::new()
        ->admin()
        ->create();
    $this->thumbnail = ThumbnailConfigFactory::new()
        ->scalingByWidth(40)
        ->with([
            'format' => 'PNG',
            'downloadable' => true,
        ])
        ->create();

    $this->folder = AssetFolderFactory::createOne();
    $this->shoes = AssetFolderFactory::new()
        ->withParent($this->folder)
        ->with(['filename' => 'shoes'])
        ->create();
    $this->red = AssetImageFactory::new()
        ->withParent($this->folder)
        ->with(['filename' => 'red.jpg'])
        ->create();
    $this->blue = AssetImageFactory::new()
        ->withParent($this->folder)
        ->with(['filename' => 'blue.jpg'])
        ->create();
    $this->boot = AssetImageFactory::new()
        ->withParent($this->shoes)
        ->with(['filename' => 'boot.jpg'])
        ->create();
    $this->manual = AssetDocumentFactory::new()
        ->withParent($this->folder)
        ->with(['filename' => 'manual.pdf'])
        ->create();
});

it('packs the originals of every file below the folder', function () {
    $zip = zipOfOriginals($this->admin, $this->folder);

    expect($zip->paths())->toEqualCanonicalizing(
        pathsIn($this->folder, 'red.jpg', 'blue.jpg', 'shoes/boot.jpg', 'manual.pdf'),
    );
});

it('packs a thumbnail of every image below the folder and leaves out the other files', function () {
    $zip = zipOfThumbnails($this->admin, $this->folder, $this->thumbnail, []);

    expect($zip->paths())->toEqualCanonicalizing(pathsIn($this->folder, 'red.png', 'blue.png', 'shoes/boot.png'));
});

it('packs the image in the size of the thumbnail', function () {
    $zip = zipOfThumbnails($this->admin, $this->folder, $this->thumbnail, [$this->red->getId()]);

    $image = imagecreatefromstring($zip->entries[sprintf('%s/red.png', $this->folder->getFilename())]);
    expect(imagesx($image))->toBe(40);
});

it('names the archive after the folder and the thumbnail', function () {
    $zip = zipOfThumbnails($this->admin, $this->folder, $this->thumbnail, []);

    expect($zip->filename)->toBe(sprintf('%s-%s.zip', $this->folder->getFilename(), $this->thumbnail->getName()));
});

it('packs only the selected images', function () {
    $selectedIds = [
        $this->blue->getId(),
        $this->manual->getId(),
    ];

    $zip = zipOfThumbnails($this->admin, $this->folder, $this->thumbnail, $selectedIds);

    expect($zip->paths())->toBe(pathsIn($this->folder, 'blue.png'));
});

it('appends the ID to a thumbnail whose name another thumbnail already took', function () {
    $jpg = AssetImageFactory::new()
        ->withParent($this->shoes)
        ->with(['filename' => 'logo.jpg'])
        ->create();
    $png = AssetImageFactory::new()
        ->withParent($this->shoes)
        ->with(['filename' => 'logo.png'])
        ->create();

    $zip = zipOfThumbnails($this->admin, $this->shoes, $this->thumbnail, [$jpg->getId(), $png->getId()]);

    expect($zip->paths())->toEqualCanonicalizing([
        'shoes/logo.png',
        sprintf('shoes/logo-%d.png', $png->getId()),
    ]);
});

it('leaves out the images in a folder the user may not see', function () {
    $user = UserFactory::new()
        ->withPermissions('assets')
        ->withAssetWorkspace($this->folder, 'list', 'view')
        ->withAssetWorkspace($this->shoes)
        ->create();

    $zip = zipOfThumbnails($user, $this->folder, $this->thumbnail, []);

    expect($zip->paths())->toEqualCanonicalizing(pathsIn($this->folder, 'red.png', 'blue.png'));
});

it('counts the images a thumbnail download would pack', function (array $selectedIds, int $count) {
    expect(imageCount($this->admin, $this->folder, $selectedIds))->toBe($count);
})->with([
    'the whole folder' => [fn (): array => [], 3],
    'an image and a document' => [fn (): array => [$this->red->getId(), $this->manual->getId()], 1],
    'only a document' => [fn (): array => [$this->manual->getId()], 0],
]);

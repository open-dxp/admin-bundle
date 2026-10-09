<?php

declare(strict_types=1);

use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Bundle\AdminBundle\Test\GridExport\GridExportFile;
use OpenDxp\Model\User;
use OpenDxp\TestFoundation\Browser;
use OpenDxp\TestFoundation\Container;
use Zenstruck\Browser\KernelBrowser;

/**
 * @param array<string, mixed> $parameters
 * @param array<string, string> $settings
 * @param list<int|string> $selectedIds
 */
function requestGridExport(
    User $user,
    string $source,
    array $parameters = [],
    array $settings = [],
    array $selectedIds = [],
): KernelBrowser {
    return Browser::actingAs($user)->post('/admin/grid-export/start', [
        'body' => [
            'source' => $source,
            'parameters' => json_encode($parameters, JSON_THROW_ON_ERROR),
            'selectedIds' => $selectedIds,
            'language' => 'en',
            'timezone' => 'UTC',
            ...$settings,
        ],
    ]);
}

/**
 * Runs a grid export through the requests of the admin and returns its file. A source that reads the admin session,
 * like the one of the objects, needs these requests.
 *
 * @param array<string, mixed> $parameters
 * @param array<string, string> $settings
 * @param list<int|string> $selectedIds
 */
function exportThroughAdmin(
    User $user,
    string $source,
    array $parameters = [],
    array $settings = [],
    array $selectedIds = [],
): GridExportFile {
    $response = requestGridExport($user, $source, $parameters, $settings, $selectedIds)->content();
    $id = json_decode($response, true, flags: JSON_THROW_ON_ERROR)['id'];

    for ($batch = 0; $batch < findGridExport($id)->getBatchCount(); $batch++) {
        writeGridExportBatch($user, $id, $batch);
    }

    $download = downloadGridExport($user, $id)->client();
    preg_match(
        '/filename="?([^";]+)"?/',
        (string) $download->getResponse()->headers->get('Content-Disposition'),
        $filename,
    );

    return new GridExportFile($filename[1] ?? '', $download->getInternalResponse()->getContent());
}

/**
 * Starts a grid export and returns its ID.
 */
function startGridExport(User $user, string $source): string
{
    $response = requestGridExport($user, $source)->content();

    return json_decode($response, true, flags: JSON_THROW_ON_ERROR)['id'];
}

function writeGridExportBatch(User $user, string $id, int $batch): KernelBrowser
{
    return Browser::actingAs($user)->post('/admin/grid-export/batch', [
        'body' => [
            'id' => $id,
            'batch' => $batch,
        ],
    ]);
}

function downloadGridExport(User $user, string $id): KernelBrowser
{
    return Browser::actingAs($user)->visit(sprintf('/admin/grid-export/download?id=%s', $id));
}

function deleteGridExport(User $user, string $id): KernelBrowser
{
    return Browser::actingAs($user)->post('/admin/grid-export/delete', ['body' => ['id' => $id]]);
}

function findGridExport(string $id): ?GridExport
{
    return Container::get(GridExportService::class)->findExport($id);
}

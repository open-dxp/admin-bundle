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

use OpenDxp\Bundle\AdminBundle\GridExport\GridExport;
use OpenDxp\Bundle\AdminBundle\Service\Grid\GridExportService;
use OpenDxp\Bundle\AdminBundle\Test\GridExport\GridExportFile;
use OpenDxp\Model\DataObject\ClassDefinition;
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

    return writeGridExportFile($user, json_decode($response, true, flags: JSON_THROW_ON_ERROR)['id']);
}

function writeGridExportFile(User $user, string $id): GridExportFile
{
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

function createGridExportJobRun(User $user, string $id): KernelBrowser
{
    return Browser::actingAs($user)->post('/admin/grid-export/create-job-run', ['body' => ['id' => $id]]);
}

function deleteGridExport(User $user, string $id): KernelBrowser
{
    return Browser::actingAs($user)->post('/admin/grid-export/delete', ['body' => ['id' => $id]]);
}

function findGridExport(string $id): ?GridExport
{
    return Container::get(GridExportService::class)->findExport($id);
}

/**
 * Returns the parameters of an object grid of the class Inheritance that shows the given columns.
 *
 * @param array<string, string> $columns
 * @param array<string, string> $settings
 *
 * @return array<string, mixed>
 */
function objectGridParameters(int $folderId, array $columns, array $settings = []): array
{
    return [
        'folderId' => (string) $folderId,
        'classId' => ClassDefinition::getByName('Inheritance')->getId(),
        'language' => 'en',
        'fields' => array_keys($columns),
        'columns' => array_map(
            static fn (string $key, string $label): array => ['key' => $key, 'label' => $label],
            array_keys($columns),
            $columns,
        ),
        ...$settings,
    ];
}

/**
 * The admin keeps the definition of an operator column in the session. Both requests therefore share one browser.
 */
function startObjectGridExportWithAnyGetter(User $user, int $folderId, string $label, string $attribute): string
{
    $browser = Browser::actingAs($user);
    $prepared = $browser->post('/admin/object-helper/prepare-helper-column-configs', [
        'body' => [
            'columns' => json_encode([
                [
                    'isOperator' => true,
                    'attributes' => [
                        'type' => 'operator',
                        'class' => 'AnyGetter',
                        'label' => $label,
                        'attribute' => $attribute,
                        'children' => [],
                    ],
                ],
            ], JSON_THROW_ON_ERROR),
        ],
    ])->content();
    $key = json_decode($prepared, true, flags: JSON_THROW_ON_ERROR)['columns'][0]['key'];

    $started = $browser->post('/admin/grid-export/start', [
        'body' => [
            'source' => 'objects',
            'parameters' => json_encode(objectGridParameters($folderId, [$key => $label]), JSON_THROW_ON_ERROR),
            'language' => 'en',
            'timezone' => 'UTC',
        ],
    ])->content();

    return json_decode($started, true, flags: JSON_THROW_ON_ERROR)['id'];
}

<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Attribute;

use Attribute;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
 * Registers a class as the source of a grid export.
 *
 * - The name identifies the source in the requests of the admin.
 * - A user needs the permission to export the grid.
 * - The batch size limits the rows of one request.
 *
 * @see GridExportSourceInterface
 */
#[Attribute(Attribute::TARGET_CLASS)]
final class AsGridExportSource extends AutoconfigureTag
{
    public function __construct(string $name, string $permission, int $batchSize = 500)
    {
        parent::__construct(GridExportSourceRegistry::TAG, [
            'name' => $name,
            'permission' => $permission,
            'batch_size' => $batchSize,
        ]);
    }
}

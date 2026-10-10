<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Attribute;

use Attribute;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceInterface;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportSourceRegistry;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

/**
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

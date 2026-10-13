<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport;

/**
 * Takes the values of a grid export source from the session of the admin. A job run of the export has no session.
 */
interface SessionGridExportSourceInterface
{
    /**
     * Returns no object. The TmpStore keeps the query and with it each value.
     *
     * @return array<string, mixed>
     */
    public function getSessionParameters(GridExportQuery $query): array;
}

<?php

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\GridExport\Source;

use OpenDxp\Bundle\AdminBundle\Attribute\AsGridExportSource;
use OpenDxp\Bundle\AdminBundle\GridExport\GridExportQuery;
use OpenDxp\Bundle\AdminBundle\Security\AdminPermission;
use OpenDxp\Model\Translation;
use OpenDxp\Tool\Admin;

#[AsGridExportSource(name: 'admin-translations', permission: AdminPermission::AdminTranslations->value)]
final class AdminTranslationGridExportSource extends AbstractTranslationGridExportSource
{
    protected function getDomain(GridExportQuery $query): string
    {
        return Translation::DOMAIN_ADMIN;
    }

    protected function getLanguages(GridExportQuery $query): array
    {
        return Admin::getLanguages();
    }
}

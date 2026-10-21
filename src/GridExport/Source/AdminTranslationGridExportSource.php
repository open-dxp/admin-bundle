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

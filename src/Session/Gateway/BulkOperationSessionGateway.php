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

namespace OpenDxp\Bundle\AdminBundle\Session\Gateway;

use OpenDxp\Bundle\AdminBundle\Session\SessionGatewayInterface;
use OpenDxp\Tool;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

/**
 * Backs the two-step class-definition bulk export wizards.
 */
final class BulkOperationSessionGateway implements SessionGatewayInterface
{
    public function __construct(private readonly RequestStack $requestStack)
    {
    }

    public function storeExportSettings(string $json): void
    {
        $this->bag()->set('class_bulk_export_settings', $json);
    }

    public function getExportSettings(): ?string
    {
        return $this->bag()->get('class_bulk_export_settings');
    }

    public function storeImportFile(string $tmpFile): void
    {
        $this->bag()->set('class_bulk_import_file', $tmpFile);
    }

    public function getImportFile(): ?string
    {
        return $this->bag()->get('class_bulk_import_file');
    }

    private function bag(): AttributeBagInterface
    {
        return Tool\Session::getSessionBag($this->requestStack->getSession(), self::BAG_BULK_OPERATION);
    }
}

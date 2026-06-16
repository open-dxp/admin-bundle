<?php

/**
 * OpenDXP
 *
 * This source file is licensed under the GNU General Public License version 3 (GPLv3).
 *
 * Full copyright and license information is available in
 * LICENSE.md which is distributed with this source code.
 *
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef;

use OpenDxp\Tool\Session;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Attribute\AttributeBagInterface;

final class BulkExportPrepareHandler
{
    public function __construct(private readonly RequestStack $requestStack) {}

    public function __invoke(string $data): void
    {
        Session::useBag(
            $this->requestStack->getCurrentRequest()->getSession(),
            static function (AttributeBagInterface $session) use ($data): void {
                $session->set('class_bulk_export_settings', $data);
            },
            'opendxp_objects'
        );
    }
}

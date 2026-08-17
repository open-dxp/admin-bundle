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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\ReorderWidget;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ReorderWidgetPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $dashboardId = '',
        public readonly ?int $widgetId = null,
        public readonly int $column = 0,
        public readonly int $row = 0,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            dashboardId: (string) $request->request->get('key'),
            widgetId: $request->request->has('id') ? (int) $request->request->get('id') : null,
            column: (int) $request->request->getString('column'),
            row: $request->request->getInt('row'),
        );
    }
}

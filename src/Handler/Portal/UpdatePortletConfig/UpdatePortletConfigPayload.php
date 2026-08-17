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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\UpdatePortletConfig;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdatePortletConfigPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $dashboardKey = '',
        public readonly ?int $portletId = null,
        public readonly mixed $configuration = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            dashboardKey: (string) $request->request->get('key'),
            portletId: $request->request->has('id') ? (int) $request->request->get('id') : null,
            configuration: $request->request->get('config'),
        );
    }
}

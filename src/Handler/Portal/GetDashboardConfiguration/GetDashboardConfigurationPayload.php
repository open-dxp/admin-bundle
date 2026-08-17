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

namespace OpenDxp\Bundle\AdminBundle\Handler\Portal\GetDashboardConfiguration;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetDashboardConfigurationPayload implements ExtJsPayloadInterface
{
    public function __construct(public readonly ?string $key = null)
    {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            key: $request->query->has('key') ? (string) $request->query->get('key') : null,
        );
    }
}

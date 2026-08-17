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

namespace OpenDxp\Bundle\AdminBundle\Handler\Settings\ClearCache;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class ClearCachePayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly bool $onlySymfonyCache,
        public readonly bool $onlyOpendxpCache,
        public readonly string $env,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            onlySymfonyCache: (bool) $request->request->getString('only_symfony_cache'),
            onlyOpendxpCache: (bool) $request->request->getString('only_opendxp_cache'),
            env:              $request->request->getString('env'),
        );
    }
}

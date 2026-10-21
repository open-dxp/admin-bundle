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

namespace OpenDxp\Bundle\AdminBundle\Security;

use OpenDxp\Http\Request\Host\GeneralHostProviderInterface;
use OpenDxp\Http\Request\Host\GeneralHostResolver;
use OpenDxp\Http\Request\Host\TrustedHosts;
use Symfony\Component\HttpFoundation\Request;

final class TrustedLoginLinkHostResolver implements TrustedLoginLinkHostResolverInterface
{
    public function __construct(
        private readonly GeneralHostResolver $generalHostResolver,
        private readonly TrustedHosts $trustedHosts = new TrustedHosts(),
    ) {
    }

    public function resolve(?Request $request): ?string
    {
        if ($request !== null && $this->trustedHosts->isTrusted($request->getHost())) {
            return $request->getHost();
        }

        return $this->generalHostResolver->resolve([GeneralHostProviderInterface::CONTEXT_SOURCE => $request]);
    }
}

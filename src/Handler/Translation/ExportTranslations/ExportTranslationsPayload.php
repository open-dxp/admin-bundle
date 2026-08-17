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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\ExportTranslations;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\Translation;
use Symfony\Component\HttpFoundation\Request;

final readonly class ExportTranslationsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $domain,
        public readonly ?string $filter,
        public readonly ?string $searchString,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            domain: $request->query->get('domain', Translation::DOMAIN_DEFAULT),
            filter: $request->query->get('filter'),
            searchString: $request->query->get('searchString'),
        );
    }
}

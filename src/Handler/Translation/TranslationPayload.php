<?php

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

declare(strict_types=1);

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\Translation;
use Symfony\Component\HttpFoundation\Request;

final readonly class TranslationPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $domain,
        public readonly bool $hasData,
        public readonly array $data = [],
        public readonly ?array $requestParams = null,
        public readonly ?int $limit = null,
        public readonly ?int $offset = null,
        public readonly ?string $filter = null,
        public readonly ?string $searchString = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $domain = $request->request->getString('domain', Translation::DOMAIN_DEFAULT);
        $hasData = $request->request->has('data');

        if ($hasData) {
            return new static(
                domain: $domain,
                hasData: true,
                data: json_decode($request->request->getString('data'), true) ?? [],
            );
        }

        return new static(
            domain: $domain,
            hasData: false,
            requestParams: [...$request->request->all(), ...$request->query->all()],
            limit: $request->request->getInt('limit', 50),
            offset: $request->request->getInt('start', 0),
            filter: $request->request->has('filter') ? $request->request->getString('filter') : null,
            searchString: $request->request->has('searchString') ? $request->request->getString('searchString') : null,
        );
    }
}

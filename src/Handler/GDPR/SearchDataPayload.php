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

namespace OpenDxp\Bundle\AdminBundle\Handler\GDPR;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SearchDataPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public int $id,
        public string $firstname,
        public string $lastname,
        public string $email,
        public int $start,
        public int $limit,
        public ?string $sort = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $allParams = $request->query->all();

        return new static(
            id:        (int)$allParams['id'],
            firstname: strip_tags($allParams['firstname']),
            lastname:  strip_tags($allParams['lastname']),
            email:     strip_tags($allParams['email']),
            start:     (int)$allParams['start'],
            limit:     (int)$allParams['limit'],
            sort:      $allParams['sort'] ?? null,
        );
    }
}

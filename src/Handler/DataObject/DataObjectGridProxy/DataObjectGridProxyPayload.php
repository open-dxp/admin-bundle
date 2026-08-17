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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\DataObjectGridProxy;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class DataObjectGridProxyPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public array $allParams = [],
        public string $locale = '',
    ) {}

    public static function fromRequest(Request $request): static
    {
        $allParams = [...$request->request->all(), ...$request->query->all()];

        if (!empty($allParams['context'])) {
            $allParams['context'] = json_decode($allParams['context'], true);
        } else {
            $allParams['context'] = [];
        }

        return new static(
            allParams: $allParams,
            locale: $request->getLocale(),
        );
    }
}

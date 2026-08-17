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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\MergeTranslationItems;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\Translation;
use Symfony\Component\HttpFoundation\Request;

final readonly class MergeTranslationItemsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly array $dataList,
        public readonly string $domain,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $dataList = json_decode($request->request->get('data'), true);

        return new static(
            dataList: is_array($dataList) ? $dataList : [],
            domain: $request->request->get('domain', Translation::DOMAIN_DEFAULT),
        );
    }
}

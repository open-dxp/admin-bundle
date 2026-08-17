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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Variants\GetVariants;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class GetVariantsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $objectId = 0,
        public readonly array $allParams = [],
        public readonly string $requestedLanguage = '',
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $allParams = [...$request->request->all(), ...$request->query->all()];
        $languageFromParams = $request->request->getString('language');
        $requestedLanguage = ($languageFromParams !== '' && $languageFromParams !== 'default')
            ? $languageFromParams
            : $request->getLocale();

        return new static(
            objectId: $request->request->getInt('objectId'),
            allParams: $allParams,
            requestedLanguage: $requestedLanguage,
        );
    }
}

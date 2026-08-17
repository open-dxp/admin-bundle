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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetNoteTypes;

final class GetNoteTypesHandler
{
    public function __construct(
        private readonly array $documentNoteTypes,
        private readonly array $assetNoteTypes,
        private readonly array $objectNoteTypes,
    ) {
    }

    public function __invoke(GetNoteTypesPayload $payload): GetNoteTypesResult
    {
        $config = match ($payload->ctype) {
            'document' => $this->documentNoteTypes,
            'asset' => $this->assetNoteTypes,
            'object' => $this->objectNoteTypes,
            default => [],
        };

        $noteTypes = [];
        foreach ($config as $noteType) {
            $noteTypes[] = ['name' => $noteType];
        }

        return new GetNoteTypesResult(noteTypes: $noteTypes);
    }
}

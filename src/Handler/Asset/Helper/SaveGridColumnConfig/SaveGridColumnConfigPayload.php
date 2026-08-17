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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\SaveGridColumnConfig;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveGridColumnConfigPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly int $assetId = 0,
        public readonly ?string $classId = null,
        public readonly ?string $context = null,
        public readonly ?string $searchType = null,
        public readonly ?string $type = null,
        public readonly array $gridConfigData = [],
        public readonly ?array $metadata = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $gridconfig = $request->request->getString('gridconfig');
        $settings = $request->request->getString('settings');

        $decodedGridConfig = $gridconfig ? json_decode($gridconfig, true) : [];
        $decodedMetadata = $settings ? json_decode($settings, true) : null;

        return new static(
            assetId:        $request->request->getInt('id'),
            classId:        $request->request->getString('class_id') ?: null,
            context:        $request->request->getString('context') ?: null,
            searchType:     $request->request->getString('searchType') ?: null,
            type:           $request->request->getString('type') ?: null,
            gridConfigData: is_array($decodedGridConfig) ? $decodedGridConfig : [],
            metadata:       is_array($decodedMetadata) ? $decodedMetadata : null,
        );
    }
}

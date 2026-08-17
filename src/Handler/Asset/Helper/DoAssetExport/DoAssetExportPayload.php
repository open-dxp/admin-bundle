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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Helper\DoAssetExport;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\File;
use Symfony\Component\HttpFoundation\Request;

final readonly class DoAssetExportPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $fileHandle = '',
        public readonly array $ids = [],
        public readonly string $delimiter = ';',
        public readonly string $language = '',
        public readonly string $header = 'title',
        public readonly array $fields = [],
        public readonly bool $addTitles = false,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $settings = json_decode($request->request->getString('settings'), true) ?? [];
        $fields = json_decode($request->request->all('fields')[0] ?? '[]', true) ?? [];

        return new static(
            fileHandle: File::getValidFilename($request->request->getString('fileHandle')),
            ids:        $request->request->all('ids'),
            delimiter:  $settings['delimiter'] ?? ';',
            language:   str_replace('default', '', $request->request->getString('language')),
            header:     $settings['header'] ?? 'title',
            fields:     $fields,
            addTitles:  (bool) $request->request->get('initial'),
        );
    }
}

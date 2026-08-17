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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\Helper\DoDataObjectExport;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\File;
use Symfony\Component\HttpFoundation\Request;

final readonly class DoDataObjectExportPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $fileHandle = '',
        public array $ids = [],
        public string $classId = '',
        public string $delimiter = ';',
        public string $header = 'title',
        public ?string $userTimezone = null,
        public array $allParams = [],
        public string $requestedLanguage = '',
        public array $fields = [],
        public bool $addTitles = false,
        public bool $enableInheritance = false,
        public array $context = [],
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $settings = json_decode($request->request->getString('settings'), true) ?? [];
        $fieldsRaw = $request->request->all('fields');
        $contextFromRequest = $request->request->getString('context') ?: null;
        $context = ['source' => 'opendxp-export'];
        if ($contextFromRequest) {
            $context = [...$context, ...json_decode($contextFromRequest, true)];
        }

        return new static(
            fileHandle: File::getValidFilename($request->request->getString('fileHandle')),
            ids: $request->request->all('ids'),
            classId: $request->request->getString('classId'),
            delimiter: $settings['delimiter'] ?? ';',
            header: $settings['header'] ?? 'title',
            userTimezone: $request->request->getString('userTimezone') ?: null,
            allParams: [...$request->request->all(), ...$request->query->all()],
            requestedLanguage: $request->request->getString('language') ?: $request->getLocale(),
            fields: !empty($fieldsRaw) ? (json_decode($fieldsRaw[0], true) ?? []) : [],
            addTitles: (bool) $request->request->get('initial'),
            enableInheritance: (bool) ($settings['enableInheritance'] ?? false),
            context: $context,
        );
    }
}

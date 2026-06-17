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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\CustomLayout\ImportCustomLayout;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\DataObject;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class ImportCustomLayoutPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $id = null,
        public readonly array $importData = [],
        public readonly bool $nameAlreadyInUse = false,
    ) {}

    public static function fromRequest(Request $request): static
    {
        /** @var UploadedFile $file */
        $file = $request->files->get('Filedata');
        $importData = json_decode(file_get_contents($file->getPathname()), true) ?? [];

        return new static(
            id:               $request->query->getString('id') ?: null,
            importData:       $importData,
            nameAlreadyInUse: isset($importData['name']) && DataObject\ClassDefinition\CustomLayout::getByName($importData['name']) instanceof DataObject\ClassDefinition\CustomLayout,
        );
    }
}

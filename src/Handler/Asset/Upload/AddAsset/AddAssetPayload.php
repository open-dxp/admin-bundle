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

namespace OpenDxp\Bundle\AdminBundle\Handler\Asset\Upload\AddAsset;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class AddAssetPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly ?string $filedataPath = null,
        public readonly ?string $filedataOriginalName = null,
        public readonly ?string $type = null,
        public readonly ?string $filename = null,
        public readonly ?string $data = null,
        public readonly int $parentId = 0,
        public readonly bool $hasParentId = false,
        public readonly ?string $parentPath = null,
        public readonly bool $hasDir = false,
        public readonly ?string $dir = null,
        public readonly ?string $context = null,
        public readonly bool $allowOverwrite = false,
        public readonly ?string $uploadAssetType = null,
    ) {}

    public static function fromRequest(Request $request): static
    {
        $filedataPath = null;
        $filedataOriginalName = null;
        if ($request->files->has('Filedata')) {
            /** @var UploadedFile $file */
            $file = $request->files->get('Filedata');
            $filedataPath = $file->getPathname();
            $filedataOriginalName = $file->getClientOriginalName();
        }

        return new static(
            filedataPath:         $filedataPath,
            filedataOriginalName: $filedataOriginalName,
            type:                 $request->request->getString('type') ?: null,
            filename:             $request->request->getString('filename') ?: null,
            data:                 $request->request->getString('data') ?: null,
            parentId:             $request->query->getInt('parentId'),
            hasParentId:          $request->query->has('parentId'),
            parentPath:           $request->query->get('parentPath'),
            hasDir:               $request->query->has('dir'),
            dir:                  $request->query->get('dir'),
            context:              $request->query->get('context'),
            allowOverwrite:       (bool) $request->query->get('allowOverwrite'),
            uploadAssetType:      $request->query->get('uploadAssetType'),
        );
    }
}

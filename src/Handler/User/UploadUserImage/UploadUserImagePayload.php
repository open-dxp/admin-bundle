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

namespace OpenDxp\Bundle\AdminBundle\Handler\User\UploadUserImage;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Bundle\AdminBundle\Payload\OwnUserAwareInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;

final readonly class UploadUserImagePayload implements ExtJsPayloadInterface, OwnUserAwareInterface
{
    public function __construct(
        public readonly ?int $targetUserId,
        public readonly ?UploadedFile $avatarFile,
    ) {}

    public static function fromRequest(Request $request): static
    {
        return new static(
            targetUserId: $request->query->has('id') ? $request->query->getInt('id') : null,
            avatarFile: $request->files->get('Filedata'),
        );
    }

    public function getOwnUserId(): int
    {
        return $this->targetUserId ?? 0;
    }
}

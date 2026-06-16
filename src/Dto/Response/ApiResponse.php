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

namespace OpenDxp\Bundle\AdminBundle\Dto\Response;

use JsonSerializable;

final readonly class ApiResponse implements JsonSerializable
{
    public function __construct(
        public bool $success,
        public ?string $message = null,
        private readonly array $extra = [],
    ) {
    }

    public static function ok(array $extra = []): self
    {
        return new self(success: true, extra: $extra);
    }

    public static function error(?string $message = null, array $extra = []): self
    {
        return new self(success: false, message: $message, extra: $extra);
    }

    public static function fromBool(bool $success, array $extra = []): self
    {
        return new self(success: $success, extra: $extra);
    }

    public function jsonSerialize(): array
    {
        $data = ['success' => $this->success];
        if ($this->message !== null) {
            $data['message'] = $this->message;
        }

        return array_merge($data, $this->extra);
    }
}

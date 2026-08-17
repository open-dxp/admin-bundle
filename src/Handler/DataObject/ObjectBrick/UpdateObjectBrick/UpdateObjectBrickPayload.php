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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ObjectBrick\UpdateObjectBrick;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use Symfony\Component\HttpFoundation\Request;

final readonly class UpdateObjectBrickPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public readonly string $key = '',
        public readonly string $title = '',
        public readonly string $group = '',
        public readonly bool $isAdd = false,
        public readonly ?array $values = null,
        public readonly ?array $configuration = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        return new static(
            key: $request->request->getString('key'),
            title: $request->request->getString('title'),
            group: $request->request->getString('group'),
            isAdd: $request->request->getString('task') === 'add',
            values: $request->request->has('values') ? (json_decode($request->request->getString('values'), true) ?? null) : null,
            configuration: $request->request->has('configuration') ? (json_decode($request->request->getString('configuration'), true) ?? null) : null,
        );
    }
}

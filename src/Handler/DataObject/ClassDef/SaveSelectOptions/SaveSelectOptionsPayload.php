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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\SaveSelectOptions;

use OpenDxp\Bundle\AdminBundle\Payload\ExtJsPayloadInterface;
use OpenDxp\Model\DataObject\SelectOptions\Config;
use Symfony\Component\HttpFoundation\Request;

final readonly class SaveSelectOptionsPayload implements ExtJsPayloadInterface
{
    public function __construct(
        public string $id = '',
        public string $task = '',
        public ?string $group = null,
        public string $useTraits = '',
        public string $implementsInterfaces = '',
        public ?array $selectOptionsData = null,
    ) {
    }

    public static function fromRequest(Request $request): static
    {
        $rawSelectOptions = $request->request->getString(Config::PROPERTY_SELECT_OPTIONS);

        return new static(
            id: $request->request->getString(Config::PROPERTY_ID),
            task: $request->request->getString('task'),
            group: $request->request->getString(Config::PROPERTY_GROUP) ?: null,
            useTraits: $request->request->getString(Config::PROPERTY_USE_TRAITS),
            implementsInterfaces: $request->request->getString(Config::PROPERTY_IMPLEMENTS_INTERFACES),
            selectOptionsData: $rawSelectOptions !== '' ? json_decode($rawSelectOptions, true) : null,
        );
    }
}

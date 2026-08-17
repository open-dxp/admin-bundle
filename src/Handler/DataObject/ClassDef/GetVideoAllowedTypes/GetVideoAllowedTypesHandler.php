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

namespace OpenDxp\Bundle\AdminBundle\Handler\DataObject\ClassDef\GetVideoAllowedTypes;

use OpenDxp\Model\DataObject;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetVideoAllowedTypesHandler
{
    public function __construct(private readonly TranslatorInterface $translator,)
    {
    }

    public function __invoke(): GetVideoAllowedTypesResult
    {
        $videoDef = new DataObject\ClassDefinition\Data\Video();
        $res = [];

        foreach ($videoDef->getSupportedTypes() as $type) {
            $res[] = [
                'key' => $type,
                'value' => $this->translator->trans($type, [], 'admin'),
            ];
        }

        return new GetVideoAllowedTypesResult(types: $res);
    }
}

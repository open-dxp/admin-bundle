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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element;

use OpenDxp\Model;
use OpenDxp\Model\Property;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetPredefinedPropertiesHandler
{
    public function __construct(
        private readonly TranslatorInterface $translator,
    ) {}

    public function __invoke(?string $type, ?string $query): GetPredefinedPropertiesResult
    {
        $properties = [];
        $allowedTypes = ['asset', 'document', 'object'];

        if (in_array($type, $allowedTypes, true)) {
            $list = new Model\Property\Predefined\Listing();
            $list->setFilter(function (Property\Predefined $predefined) use ($type, $query) {
                if (!str_contains($predefined->getCtype(), $type)) {
                    return false;
                }

                return !($query && stripos($this->translator->trans($predefined->getName(), [], 'admin'), (string) $query) === false);
            });

            foreach ($list->getProperties() as $predefined) {
                $properties[] = $predefined->getObjectVars();
            }
        }

        return new GetPredefinedPropertiesResult(properties: $properties);
    }
}

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

namespace OpenDxp\Bundle\AdminBundle\Handler\Element\GetPredefinedProperties;

use OpenDxp\Model;
use OpenDxp\Model\Property;
use Symfony\Contracts\Translation\TranslatorInterface;

final class GetPredefinedPropertiesHandler
{
    public function __construct(private readonly TranslatorInterface $translator,)
    {
    }

    public function __invoke(GetPredefinedPropertiesPayload $payload): GetPredefinedPropertiesResult
    {
        $properties = [];
        $allowedTypes = ['asset', 'document', 'object'];

        if (in_array($payload->elementType, $allowedTypes, true)) {
            $list = new Model\Property\Predefined\Listing();
            $list->setFilter(function (Property\Predefined $predefined) use ($payload) {
                if (!str_contains($predefined->getCtype(), $payload->elementType)) {
                    return false;
                }

                return !($payload->query && stripos($this->translator->trans($predefined->getName(), [], 'admin'), (string) $payload->query) === false);
            });

            foreach ($list->getProperties() as $predefined) {
                $properties[] = $predefined->getObjectVars();
            }
        }

        return new GetPredefinedPropertiesResult(properties: $properties);
    }
}

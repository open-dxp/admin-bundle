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

namespace OpenDxp\Bundle\AdminBundle\Normalizer;

use OpenDxp\Model\Element\ElementInterface;

final class ElementResponseNormalizer
{
    /** @param iterable<ElementResponseNormalizerInterface> $normalizers */
    public function __construct(private readonly iterable $normalizers) {}

    public function normalize(ElementInterface $element, array &$data, string $handlerClass, array $context = []): void
    {
        foreach ($this->normalizers as $normalizer) {
            if ($normalizer->supports($element, $handlerClass)) {
                $normalizer->normalize($element, $data, $context);
            }
        }
    }
}

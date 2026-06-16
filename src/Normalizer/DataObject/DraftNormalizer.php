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

namespace OpenDxp\Bundle\AdminBundle\Normalizer\DataObject;

use OpenDxp\Bundle\AdminBundle\Handler\DataObject\GetDataObjectHandler;
use OpenDxp\Bundle\AdminBundle\Normalizer\ElementResponseNormalizerInterface;
use OpenDxp\Model\DataObject;
use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Version;

final class DraftNormalizer implements ElementResponseNormalizerInterface
{
    public function supports(ElementInterface $element, string $handlerClass): bool
    {
        return $element instanceof DataObject\Concrete
            && $handlerClass === GetDataObjectHandler::class;
    }

    public function normalize(ElementInterface $element, array &$data, array $context = []): void
    {
        $draftVersion = $context['draftVersion'] ?? null;
        if (!$draftVersion instanceof Version) {
            return;
        }

        $fresh = DataObject\Concrete::getById($element->getId(), ['force' => true]);
        if ($fresh->getModificationDate() < $draftVersion->getDate()) {
            $data['draft'] = [
                'id' => $draftVersion->getId(),
                'modificationDate' => $draftVersion->getDate(),
                'isAutoSave' => $draftVersion->isAutoSave(),
            ];
        }
    }
}

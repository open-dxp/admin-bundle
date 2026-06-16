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

use OpenDxp\Model\Element\ElementInterface;
use OpenDxp\Model\Element\Service;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class GetReplaceAssignmentsBatchJobsHandler
{
    public function __invoke(?int $id, ?string $type, ?string $path): array
    {
        $element = null;

        if ($id) {
            $element = Service::getElementById($type, $id);
        } elseif ($path) {
            $element = Service::getElementByPath($type, $path);
        }

        if (!$element instanceof ElementInterface) {
            throw new NotFoundHttpException();
        }

        return $element->getDependencies()->getRequiredBy();
    }
}

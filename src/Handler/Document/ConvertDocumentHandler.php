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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document;

use OpenDxp\Cache\RuntimeCache;
use OpenDxp\Model\Document;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class ConvertDocumentHandler
{
    public function __invoke(int $id, string $type): void
    {
        $document = Document::getById($id);
        if (!$document) {
            throw new NotFoundHttpException('Document not found');
        }

        $class = '\\OpenDxp\\Model\\Document\\' . ucfirst($type);
        if (!Tool::classExists($class)) {
            return;
        }

        $new = new $class;

        // overwrite internal store to avoid "duplicate full path" error
        RuntimeCache::set('document_' . $document->getId(), $new);

        $props = $document->getObjectVars();
        foreach ($props as $name => $value) {
            if (in_array($name, ['children', 'siblings', 'scheduledTasks', 'controller', 'template'])) {
                continue;
            }
            $new->setValue($name, $value);
        }

        if ($type === 'hardlink' || $type === 'folder') {
            foreach (['name', 'title', 'target', 'exclude', 'class', 'anchor', 'parameters', 'relation', 'accesskey', 'tabindex'] as $propertyName) {
                $new->removeProperty('navigation_' . $propertyName);
            }
        }

        $new->setType($type);
        $new->save();
    }
}

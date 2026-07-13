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

namespace OpenDxp\Bundle\AdminBundle\Handler\Document\Page\CheckPrettyUrl;

use OpenDxp\Bundle\AdminBundle\Exception\AdminOperationFailedException;
use OpenDxp\Bundle\AdminBundle\Handler\Document\Page\CheckPrettyUrl\CheckPrettyUrlPayload;
use OpenDxp\Model\Document;
use OpenDxp\Model\Element;
use OpenDxp\Tool\Frontend;

final class CheckPrettyUrlHandler
{
    public function __invoke(CheckPrettyUrlPayload $payload): void
    {
        $path = rtrim($payload->path, '/');

        if ($path === '') {
            return;
        }

        $messages = [];

        // must start with /
        if (!str_starts_with($path, '/')) {
            $messages[] = 'URL must start with /.';
        }

        if (strlen($path) < 2) {
            $messages[] = 'URL must be at least 2 characters long.';
        }

        if (!Element\Service::isValidPath($path, 'document')) {
            $messages[] = 'URL is invalid.';
        }

        if (empty($messages)) {
            $list = new Document\Listing();
            $list->setCondition('(CONCAT(`path`, `key`) = ? OR id IN (SELECT id from documents_page WHERE prettyUrl = ?))
                AND id != ?', [
                $path, $path, $payload->id,
            ]);

            if ($list->getTotalCount() > 0) {
                $checkDocument = Document::getById($payload->id);
                $checkSite = Frontend::getSiteForDocument($checkDocument);
                $checkSiteId = empty($checkSite) ? 0 : $checkSite->getId();

                foreach ($list as $document) {
                    if (empty($document)) {
                        continue;
                    }

                    $site = Frontend::getSiteForDocument($document);
                    $siteId = empty($site) ? 0 : $site->getId();

                    if ($siteId === $checkSiteId) {
                        $messages[] = 'URL path already exists.';

                        break;
                    }
                }
            }
        }

        if (!empty($messages)) {
            throw new AdminOperationFailedException(implode('<br>', $messages));
        }
    }
}

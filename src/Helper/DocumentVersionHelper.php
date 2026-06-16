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

namespace OpenDxp\Bundle\AdminBundle\Helper;

use OpenDxp\Model\Document\PageSnippet;
use OpenDxp\Model\Version;

final class DocumentVersionHelper
{
    /**
     * Returns the latest draft version of a PageSnippet document if available,
     * otherwise returns the document as-is.
     *
     * @template T of PageSnippet
     *
     * @param T $document
     *
     * @return T
     */
    public static function resolveLatestDraft(PageSnippet $document, ?Version &$draftVersion = null, ?int $userId = null): PageSnippet
    {
        $latestVersion = $document->getLatestVersion($userId);
        if ($latestVersion) {
            $latestDoc = $latestVersion->loadData();
            if ($latestDoc instanceof PageSnippet) {
                $draftVersion = $latestVersion;

                return $latestDoc;
            }
        }

        return $document;
    }
}

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
 * @copyright  Copyright (c) Pimcore GmbH (https://pimcore.com)
 * @copyright  Modification Copyright (c) OpenDXP (https://www.opendxp.io)
 * @license    https://www.gnu.org/licenses/gpl-3.0.html  GNU General Public License version 3 (GPLv3)
 */

namespace OpenDxp\Bundle\AdminBundle\Service\Translation;

use OpenDxp\Model\Translation;
use OpenDxp\Model\Translation\Listing;
use OpenDxp\Model\User;
use OpenDxp\Tool\Admin;

final class AdminSearchTermResolver
{
    public function resolve(string $searchTerm): array
    {
        $terms = [];
        $user = Admin::getCurrentUser();
        if ($user instanceof User) {
            $translationListing = new Listing();
            $translationListing->setDomain(Translation::DOMAIN_ADMIN);
            $translationListing->setCondition(
                $translationListing->quoteIdentifier('language') . ' = ? AND ' .
                $translationListing->quoteIdentifier('text') . ' LIKE ?',
                [$user->getLanguage(), '%' . $searchTerm . '%']
            );
            foreach ($translationListing as $translation) {
                $terms[] = $translation->getKey();
            }
        }

        return $terms;
    }
}

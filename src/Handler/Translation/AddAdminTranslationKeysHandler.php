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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation;

use Exception;
use OpenDxp\Logger;
use OpenDxp\Model\Translation;
use OpenDxp\Tool\Admin as AdminTool;

final class AddAdminTranslationKeysHandler
{
    public function __invoke(array $keys): void
    {
        $availableLanguages = AdminTool::getLanguages();

        foreach ($keys as $translationData) {
            $t = null;

            try {
                $t = Translation::getByKey($translationData, Translation::DOMAIN_ADMIN);
            } catch (Exception $e) {
                Logger::log((string) $e);
            }

            if (!$t instanceof Translation) {
                $t = new Translation();
                $t->setDomain(Translation::DOMAIN_ADMIN);
                $t->setKey($translationData);
                $t->setCreationDate(time());
                $t->setModificationDate(time());

                foreach ($availableLanguages as $lang) {
                    $t->addTranslation($lang, '');
                }

                try {
                    $t->save();
                } catch (Exception $e) {
                    Logger::log((string) $e);
                }
            }
        }
    }
}

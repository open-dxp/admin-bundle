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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\CreateTranslation;

use OpenDxp\Bundle\AdminBundle\Service\AdminUserContextInterface;
use OpenDxp\Model\Translation;
use OpenDxp\Tool;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

final class CreateTranslationHandler
{
    public function __construct(private readonly AdminUserContextInterface $userContext) {}

    public function __invoke(TranslationPayload $payload): CreateTranslationResult
    {
        $data = $payload->data;
        $admin = $payload->domain === Translation::DOMAIN_ADMIN;
        $validLanguages = $admin
            ? Tool\Admin::getLanguages()
            : $this->userContext->getAdminUser()->getAllowedLanguagesForViewingWebsiteTranslations();
        if (Translation::getByKey($data['key'], $payload->domain)) {
            throw new BadRequestHttpException('identifier_already_exists');
        }

        $t = new Translation();
        $t->setDomain($payload->domain);
        $t->setKey($data['key']);
        $t->setCreationDate(time());
        $t->setModificationDate(time());
        $t->setType($data['type'] ?? null);

        foreach ($validLanguages as $lang) {
            $t->addTranslation($lang, '');
        }

        $t->save();

        return new CreateTranslationResult(
            key: $t->getKey(),
            creationDate: $t->getCreationDate(),
            modificationDate: $t->getModificationDate(),
            type: $t->getType(),
            translations: $t->getTranslations(),
        );
    }
}

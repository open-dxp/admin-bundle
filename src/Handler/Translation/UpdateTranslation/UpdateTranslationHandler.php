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

namespace OpenDxp\Bundle\AdminBundle\Handler\Translation\UpdateTranslation;

use OpenDxp\Model\Translation;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

final class UpdateTranslationHandler
{
    public function __invoke(TranslationPayload $payload): UpdateTranslationResult
    {
        $data = $payload->data;

        $t = Translation::getByKey($data['key'], $payload->domain);
        if (!$t instanceof Translation) {
            throw new NotFoundHttpException(sprintf('Translation with key "%s" not found.', $data['key']));
        }

        foreach ($data as $key => $value) {
            $key = preg_replace('/^_/', '', $key, 1);
            if (!in_array($key, ['key', 'type'])) {
                $t->addTranslation($key, $value);
            }
        }

        if ($data['key']) {
            $t->setKey($data['key']);
        }

        if ($data['type']) {
            $t->setType($data['type']);
        }

        $t->setModificationDate(time());
        $t->save();

        return new UpdateTranslationResult(
            key: $t->getKey(),
            creationDate: $t->getCreationDate(),
            modificationDate: $t->getModificationDate(),
            type: $t->getType(),
            translations: $t->getTranslations(),
        );
    }
}
